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

class KeyAccountController extends Controller
{
    public function index(): View
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        $accounts = $isAdmin
            ? KeyAccount::with(['user', 'contacts', 'gifts'])->orderBy('name')->get()
            : KeyAccount::with(['contacts', 'gifts'])->where('user_id', $user->id)->orderBy('name')->get();

        $years         = [now()->year - 1, now()->year];
        $customerCodes = $accounts->pluck('account_code')->all();

        $salesByYear = [];
        if (!empty($customerCodes)) {
            $rows = KeyAccountSale::whereIn('account_code', $customerCodes)
                ->whereIn('year', $years)
                ->get();
            foreach ($rows as $row) {
                $salesByYear[$row->year][$row->account_code] = [
                    'total' => (float) $row->total,
                    'q1'    => (float) $row->q1,
                    'q2'    => (float) $row->q2,
                    'q3'    => (float) $row->q3,
                    'q4'    => (float) $row->q4,
                ];
            }
        }

        return view('key-accounts.index', compact('accounts', 'salesByYear', 'years', 'isAdmin'));
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
                    $dt    = ExcelDate::excelToDateTimeObject($rawDate);
                    $year  = (int) $dt->format('Y');
                    $month = (int) $dt->format('n');
                } else {
                    $ts = strtotime((string) $rawDate);
                    if (!$ts) continue;
                    $year  = (int) date('Y', $ts);
                    $month = (int) date('n', $ts);
                }

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
            array_shift($rows); // remove header

            $user    = auth()->user();
            $isAdmin = $user->can('key_accounts_admin');

            $imported = 0;
            $skipped  = 0;

            foreach ($rows as $row) {
                $code        = trim((string) ($row[0] ?? ''));
                $recipient   = trim((string) ($row[1] ?? ''));
                $rawDate     = $row[2] ?? null;
                $description = trim((string) ($row[3] ?? ''));

                if (empty($code) || empty($recipient) || empty($description)) {
                    $skipped++;
                    continue;
                }

                // Parse date — handles Excel serial number, date string, or /Date()/ format
                if (is_numeric($rawDate)) {
                    try {
                        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate)->format('Y-m-d');
                    } catch (\Throwable) {
                        $skipped++;
                        continue;
                    }
                } else {
                    $parsed = strtotime((string) $rawDate);
                    if (!$parsed) { $skipped++; continue; }
                    $date = date('Y-m-d', $parsed);
                }

                $account = KeyAccount::where('account_code', $code)->first();
                if (!$account || (!$isAdmin && $account->user_id !== $user->id)) {
                    $skipped++;
                    continue;
                }

                $account->gifts()->create([
                    'recipient'   => $recipient,
                    'gifted_at'   => $date,
                    'description' => $description,
                ]);
                $imported++;
            }

            ActivityLog::record('key_accounts.gifts_import', "Imported {$imported} gift(s)");

            return back()->with('success', "Imported {$imported} gift(s). Skipped {$skipped} rows.");
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Could not read file: ' . $e->getMessage()]);
        }
    }
}
