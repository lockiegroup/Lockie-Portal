<?php

namespace App\Http\Controllers;

use App\Models\KeyAccount;
use App\Models\KeyAccountContact;
use App\Models\KeyAccountGift;
use App\Models\KeyAccountSale;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;

class KeyAccountController extends Controller
{
    public function index(): View
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        $accounts = $isAdmin
            ? KeyAccount::with(['user', 'contacts', 'gifts'])->whereNotNull('user_id')->orderBy('name')->get()
            : KeyAccount::with(['contacts', 'gifts'])->where('user_id', $user->id)->orderBy('name')->get();

        $currentYear   = (int) now()->year;
        $customerCodes = $accounts->pluck('account_code')->all();

        $salesByYear = [];
        $dataYears   = [];
        if (!empty($customerCodes)) {
            $rows = KeyAccountSale::whereIn('account_code', $customerCodes)->get();
            foreach ($rows as $row) {
                $dataYears[] = (int) $row->year;
                $salesByYear[$row->year][$row->account_code] = [
                    'total' => (float) $row->total,
                    'q1'    => (float) $row->q1,
                    'q2'    => (float) $row->q2,
                    'q3'    => (float) $row->q3,
                    'q4'    => (float) $row->q4,
                ];
            }
        }

        $dataYears = $dataYears ? array_values(array_unique($dataYears)) : [$currentYear];
        sort($dataYears);

        return view('key-accounts.index', compact('accounts', 'salesByYear', 'dataYears', 'currentYear', 'isAdmin'));
    }

    public function show(KeyAccount $keyAccount): View
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        if (!$isAdmin && $keyAccount->user_id !== $user->id) {
            abort(403);
        }

        $keyAccount->load(['contacts.user', 'gifts']);

        return view('key-accounts.show', compact('keyAccount', 'isAdmin'));
    }

    public function storeContact(Request $request, KeyAccount $keyAccount): RedirectResponse
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        if (!$isAdmin && $keyAccount->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'contacted_at' => ['required', 'date'],
            'note'         => ['required', 'string', 'max:2000'],
        ]);

        $keyAccount->contacts()->create([
            'user_id'      => $user->id,
            'contacted_at' => $data['contacted_at'],
            'note'         => $data['note'],
        ]);

        ActivityLog::record('key_accounts.contact', "Logged contact for {$keyAccount->account_code}");

        return back()->with('success', 'Contact logged.');
    }

    public function destroyContact(KeyAccount $keyAccount, KeyAccountContact $contact): RedirectResponse
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        if (!$isAdmin && $keyAccount->user_id !== $user->id) {
            abort(403);
        }

        $contact->delete();

        return back()->with('success', 'Contact entry removed.');
    }

    public function updateNotes(Request $request, KeyAccount $keyAccount): RedirectResponse
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        if (!$isAdmin && $keyAccount->user_id !== $user->id) {
            abort(403);
        }

        $keyAccount->update(['notes' => $request->input('notes')]);

        return back()->with('success', 'Notes saved.');
    }

    public function importSales(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:20480']);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

            if (empty($rows)) {
                return back()->withErrors(['sales_file' => 'File appears empty.']);
            }

            // Detect columns from header row
            $header      = array_map(fn($h) => strtolower(trim((string) ($h ?? ''))), $rows[0]);
            $colDate     = array_search('order date', $header);
            $colCustomer = array_search('customer code', $header);
            $colSubTotal = array_search('sub total', $header);
            $colStatus   = array_search('status', $header);

            if ($colDate === false || $colCustomer === false || $colSubTotal === false) {
                return back()->withErrors(['sales_file' => 'Required columns not found. Expected: Order Date, Customer Code, Sub Total.']);
            }

            array_shift($rows);

            // Aggregate: [year][customerCode] => [total, q1..q4]
            $aggregated = [];

            foreach ($rows as $row) {
                $code     = trim((string) ($row[$colCustomer] ?? ''));
                $subtotal = (float) ($row[$colSubTotal] ?? 0);
                $rawDate  = $row[$colDate] ?? null;
                $status   = strtolower(trim((string) ($colStatus !== false ? ($row[$colStatus] ?? '') : '')));

                if (empty($code) || $subtotal <= 0) continue;
                if ($status === 'cancelled') continue;

                if (is_numeric($rawDate)) {
                    $dt = ExcelDate::excelToDateTimeObject($rawDate);
                } else {
                    $dt = \DateTime::createFromFormat('d/m/Y', (string) $rawDate)
                       ?: \DateTime::createFromFormat('Y-m-d', (string) $rawDate);
                    if (!$dt) continue;
                }
                $year  = (int) $dt->format('Y');
                $month = (int) $dt->format('n');

                $quarter = 'q' . (int) ceil($month / 3);
                $aggregated[$year][$code] ??= ['total' => 0.0, 'q1' => 0.0, 'q2' => 0.0, 'q3' => 0.0, 'q4' => 0.0];
                $aggregated[$year][$code]['total']   += $subtotal;
                $aggregated[$year][$code][$quarter]  += $subtotal;
            }

            $now    = now();
            $userId = auth()->id();
            $count  = 0;

            foreach ($aggregated as $year => $customers) {
                foreach ($customers as $code => $data) {
                    KeyAccountSale::updateOrCreate(
                        ['account_code' => $code, 'year' => $year],
                        array_merge($data, ['imported_at' => $now, 'user_id' => $userId])
                    );
                    $count++;
                }
            }

            ActivityLog::record('key_accounts.sales_import', "Imported sales for {$count} account/year(s)");

            return back()->with('success', "Sales imported for {$count} account/year combination(s).");
        } catch (\Throwable $e) {
            return back()->withErrors(['sales_file' => 'Could not read file: ' . $e->getMessage()]);
        }
    }

    public function importGifts(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:5120']);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            array_shift($rows);

            $user    = auth()->user();
            $isAdmin = $user->can('key_accounts_admin');

            $pending = [];
            $skipped = 0;

            foreach ($rows as $row) {
                $code        = trim((string) ($row[0] ?? ''));
                $recipient   = trim((string) ($row[1] ?? ''));
                $rawDate     = $row[2] ?? null;
                $description = trim((string) ($row[3] ?? ''));

                if (empty($code) || empty($recipient) || empty($description)) {
                    $skipped++;
                    continue;
                }

                if (is_numeric($rawDate)) {
                    try {
                        $date = ExcelDate::excelToDateTimeObject($rawDate)->format('Y-m-d');
                    } catch (\Throwable) {
                        $skipped++;
                        continue;
                    }
                } else {
                    $dt = \DateTime::createFromFormat('d/m/Y', (string) $rawDate)
                       ?: \DateTime::createFromFormat('Y-m-d', (string) $rawDate);
                    if (!$dt) { $skipped++; continue; }
                    $date = $dt->format('Y-m-d');
                }

                $pending[$code][] = ['recipient' => $recipient, 'gifted_at' => $date, 'description' => $description];
            }

            if (empty($pending)) {
                return back()->with('success', "No gifts imported. Skipped {$skipped} rows.");
            }

            $codes    = array_keys($pending);
            $accounts = KeyAccount::withTrashed()->whereIn('account_code', $codes)->get()->keyBy('account_code');

            // Ensure an account row exists for every code
            foreach ($codes as $code) {
                if (!isset($accounts[$code])) {
                    $accounts[$code] = KeyAccount::create([
                        'account_code' => $code,
                        'name'         => $code,
                        'type'         => 'key',
                        'user_id'      => null,
                    ]);
                } elseif ($accounts[$code]->trashed()) {
                    $accounts[$code]->restore();
                }
            }

            $now        = now()->toDateTimeString();
            $insertRows = [];
            $imported   = 0;

            foreach ($codes as $code) {
                $account = $accounts[$code] ?? null;
                if (!$account) continue;

                if (!$isAdmin && $account->user_id !== null && $account->user_id !== $user->id) {
                    $skipped += count($pending[$code]);
                    continue;
                }

                // Full replace: wipe existing gifts for this account then re-insert
                DB::table('key_account_gifts')->where('key_account_id', $account->id)->delete();

                foreach ($pending[$code] as $gift) {
                    $insertRows[] = array_merge($gift, [
                        'key_account_id' => $account->id,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]);
                    $imported++;
                }
            }

            foreach (array_chunk($insertRows, 200) as $chunk) {
                DB::table('key_account_gifts')->insert($chunk);
            }

            ActivityLog::record('key_accounts.gifts_import', "Imported {$imported} gift(s)");

            return back()->with('success', "Imported {$imported} gift(s). Skipped {$skipped} rows.");
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Could not read file: ' . $e->getMessage()]);
        }
    }

    public function exportGifts(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        $accounts = $isAdmin
            ? KeyAccount::with(['gifts' => fn($q) => $q->orderBy('gifted_at')])->orderBy('account_code')->get()
            : KeyAccount::with(['gifts' => fn($q) => $q->orderBy('gifted_at')])->where('user_id', $user->id)->orderBy('account_code')->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->fromArray(['Account Code', 'Recipient', 'Date', 'Gift Description'], null, 'A1');

        $rowNum = 2;
        foreach ($accounts as $account) {
            foreach ($account->gifts as $gift) {
                $sheet->fromArray([
                    $account->account_code,
                    $gift->recipient,
                    $gift->gifted_at->format('d/m/Y'),
                    $gift->description,
                ], null, "A{$rowNum}");
                $rowNum++;
            }
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'gifts_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmpFile);

        ActivityLog::record('key_accounts.gifts_export', "Exported gifts (" . ($rowNum - 2) . " row(s))");

        return response()->download(
            $tmpFile,
            'gifts-export-' . now()->format('Y-m-d') . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }
}
