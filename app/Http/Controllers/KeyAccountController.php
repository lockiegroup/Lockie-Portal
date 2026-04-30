<?php

namespace App\Http\Controllers;

use App\Models\KeyAccount;
use App\Models\KeyAccountContact;
use App\Models\KeyAccountGift;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KeyAccountController extends Controller
{
    public function index(): View
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        $accounts = $isAdmin
            ? KeyAccount::with(['user', 'contacts', 'gifts'])->whereNotNull('user_id')->orderBy('sort_order')->orderBy('name')->get()
            : KeyAccount::with(['contacts', 'gifts'])->where('user_id', $user->id)->orderBy('sort_order')->orderBy('name')->get();

        $currentYear   = (int) now()->year;
        $customerCodes = $accounts->pluck('account_code')->all();

        // Session-stored date range, defaulting to start of 2 years ago → today
        $defaultFrom = now()->subYears(2)->startOfYear()->format('Y-m-d');
        $defaultTo   = now()->format('Y-m-d');
        $filterFrom  = session('ka_sales_from', $defaultFrom);
        $filterTo    = session('ka_sales_to',   $defaultTo);

        $salesByYear = [];
        $dataYears   = [];

        if (!empty($customerCodes)) {
            $rows = DB::table('sales_lines')
                ->selectRaw("
                    customer_code,
                    YEAR(COALESCE(completed_date, order_date)) AS year,
                    SUM(sub_total) AS total,
                    SUM(CASE WHEN MONTH(COALESCE(completed_date, order_date)) BETWEEN 1  AND 3  THEN sub_total ELSE 0 END) AS q1,
                    SUM(CASE WHEN MONTH(COALESCE(completed_date, order_date)) BETWEEN 4  AND 6  THEN sub_total ELSE 0 END) AS q2,
                    SUM(CASE WHEN MONTH(COALESCE(completed_date, order_date)) BETWEEN 7  AND 9  THEN sub_total ELSE 0 END) AS q3,
                    SUM(CASE WHEN MONTH(COALESCE(completed_date, order_date)) BETWEEN 10 AND 12 THEN sub_total ELSE 0 END) AS q4
                ")
                ->whereIn('customer_code', $customerCodes)
                ->whereRaw('COALESCE(completed_date, order_date) BETWEEN ? AND ?', [$filterFrom, $filterTo])
                ->where('sub_total', '>', 0)
                ->groupByRaw('customer_code, YEAR(COALESCE(completed_date, order_date))')
                ->get();

            foreach ($rows as $row) {
                $year = (int) $row->year;
                $dataYears[] = $year;
                $salesByYear[$year][$row->customer_code] = [
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

        return view('key-accounts.index', compact('accounts', 'salesByYear', 'dataYears', 'currentYear', 'isAdmin', 'filterFrom', 'filterTo'));
    }

    public function setDateFilter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sales_from' => ['required', 'date'],
            'sales_to'   => ['required', 'date', 'after_or_equal:sales_from'],
        ]);
        session(['ka_sales_from' => $data['sales_from'], 'ka_sales_to' => $data['sales_to']]);
        return redirect()->route('key-accounts.index');
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

    public function importGifts(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:5120']);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
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
