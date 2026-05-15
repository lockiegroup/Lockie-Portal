<?php

namespace App\Http\Controllers;

use App\Models\ReminderEntry;
use App\Models\ReminderPhone;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RemindersController extends Controller
{
    public function index(Request $request): View
    {
        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);

        $entries = ReminderEntry::with('calledBy')
            ->where('year', $year)
            ->where('month', $month)
            ->orderByRaw("CASE status
                WHEN 'order_placed'       THEN 1
                WHEN 'unable_to_contact'  THEN 2
                WHEN 'using_spares'       THEN 3
                WHEN 'moved_stock'        THEN 4
                WHEN 'lost_price'         THEN 5
                WHEN 'lost_quality'       THEN 6
                WHEN 'amalgamated'        THEN 7
                WHEN 'parish_giving'      THEN 8
                WHEN 'no_longer_required' THEN 9
                WHEN 'pending'            THEN 10
                ELSE 11 END")
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get(['id', 'name']);

        $totalCount   = $entries->count();
        $orderedCount = $entries->where('status', 'order_placed')->count();
        $pendingCount = $totalCount - $orderedCount;

        $years = collect(range(now()->year - 1, now()->year + 1));

        return view('reminders.index', compact(
            'entries', 'users', 'year', 'month',
            'totalCount', 'orderedCount', 'pendingCount', 'years'
        ));
    }

    public function overview(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);

        $rows = DB::table('reminder_entries')
            ->where('year', $year)
            ->selectRaw('month, status, COUNT(*) as cnt')
            ->groupBy('month', 'status')
            ->orderBy('month')
            ->get();

        // Pivot: month → [status → count]
        $byMonth = [];
        foreach ($rows as $row) {
            $byMonth[(int)$row->month][$row->status] = (int)$row->cnt;
        }

        $availableYears = DB::table('reminder_entries')
            ->distinct()->orderBy('year')->pluck('year');

        $statuses = ReminderEntry::STATUSES;

        return view('reminders.overview', compact('year', 'byMonth', 'availableYears', 'statuses'));
    }

    public function clearMonth(Request $request): RedirectResponse
    {
        $request->validate([
            'year'  => 'required|integer|min:2000|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year  = (int) $request->input('year');
        $month = (int) $request->input('month');

        $deleted = DB::table('reminder_entries')
            ->where('year', $year)
            ->where('month', $month)
            ->delete();

        ActivityLog::record('reminders.clear', "Cleared {$deleted} entries for " . date('F Y', mktime(0, 0, 0, $month, 1, $year)));

        return redirect()->route('reminders.index', ['year' => $year, 'month' => $month])
            ->with('success', "Cleared {$deleted} entries for " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . '. You can now re-import.');
    }

    public function importEntries(Request $request): RedirectResponse
    {
        $request->validate([
            'file'  => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'year'  => 'required|integer|min:2000|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year  = (int) $request->input('year');
        $month = (int) $request->input('month');
        $file  = $request->file('file');
        $ext   = strtolower($file->getClientOriginalExtension());

        try {
            $rows = in_array($ext, ['xlsx', 'xls'])
                ? $this->parseSpreadsheet($file->getRealPath())
                : $this->parseCsvFile($file->getRealPath());

            if (empty($rows)) {
                return back()->withErrors(['file' => 'File appears empty.'])->withInput();
            }

            $header = array_map(fn($h) => strtolower(trim((string)($h ?? ''))), $rows[0]);

            $col = fn(string ...$names) => collect($names)
                ->map(fn($n) => array_search($n, $header))
                ->first(fn($v) => $v !== false);

            $colAccount     = $col('account');
            $colName        = $col('name');
            $colAdd1        = $col('add1');
            $colPostcode    = $col('post-code', 'postcode', 'post code');
            $colDocNo       = $col('doc-no', 'doc no', 'docno');
            $colOrderValue  = $col('rder-value', 'order-value', 'order value', 'ordervalue');
            $colEmail       = $col('email');
            $colEnvSets     = $col('env-sets', 'env sets', 'envsets');
            $colBoxCol      = $col('box-col', 'box col', 'boxcol', 'box colour', 'box color');
            $colEnvCol      = $col('env-col', 'env col', 'envcol', 'env colour', 'env color');
            $colDescription = $col('description2', 'description', 'desc');

            if ($colAccount === false) {
                return back()->withErrors(['file' => 'Could not find ACCOUNT column. Expected Datafile Sales Enquiry export format.'])->withInput();
            }

            $phones = ReminderPhone::pluck('phone', 'account_code');

            $now     = now()->toDateTimeString();
            $upserted = 0;

            array_shift($rows);
            foreach ($rows as $row) {
                $accountCode = strtoupper(trim((string)($row[$colAccount] ?? '')));
                if (!$accountCode) continue;

                $data = [
                    'name'        => $colName        !== false ? (substr(trim((string)($row[$colName]        ?? '')), 0, 255) ?: null) : null,
                    'add1'        => $colAdd1        !== false ? (substr(trim((string)($row[$colAdd1]        ?? '')), 0, 255) ?: null) : null,
                    'postcode'    => $colPostcode    !== false ? (substr(trim((string)($row[$colPostcode]    ?? '')), 0, 20)  ?: null) : null,
                    'doc_no'      => $colDocNo       !== false ? (substr(trim((string)($row[$colDocNo]       ?? '')), 0, 50)  ?: null) : null,
                    'order_value' => $colOrderValue  !== false ? ((float) str_replace([',', '£', '$', '€'], '', $row[$colOrderValue] ?? 0)) : null,
                    'email'       => $colEmail       !== false ? (substr(trim((string)($row[$colEmail]       ?? '')), 0, 255) ?: null) : null,
                    'env_sets'    => $colEnvSets     !== false ? ((float) str_replace([','], '', $row[$colEnvSets]    ?? 0)) : null,
                    'box_colour'  => $colBoxCol      !== false ? (substr(trim((string)($row[$colBoxCol]      ?? '')), 0, 50)  ?: null) : null,
                    'env_colour'  => $colEnvCol      !== false ? (substr(trim((string)($row[$colEnvCol]      ?? '')), 0, 50)  ?: null) : null,
                    'description' => $colDescription !== false ? (substr(trim((string)($row[$colDescription] ?? '')), 0, 255) ?: null) : null,
                    'phone'       => $phones->get($accountCode),
                    'updated_at'  => $now,
                ];

                DB::table('reminder_entries')->updateOrInsert(
                    ['year' => $year, 'month' => $month, 'account_code' => $accountCode],
                    array_merge($data, ['created_at' => $now])
                );
                $upserted++;
            }

            ActivityLog::record('reminders.import', "Imported {$upserted} reminder entries for " . date('F Y', mktime(0, 0, 0, $month, 1, $year)));

            return redirect()->route('reminders.index', ['year' => $year, 'month' => $month])
                ->with('success', "Imported {$upserted} entries for " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . '.');
        } catch (\Throwable $e) {
            \Log::error('reminders.import:error', ['message' => substr($e->getMessage(), 0, 500)]);
            return back()->withErrors(['file' => 'Import failed: ' . substr($e->getMessage(), 0, 200)])->withInput();
        }
    }

    public function importPhones(Request $request): RedirectResponse
    {
        $request->validate([
            'file'  => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'year'  => 'required|integer',
            'month' => 'required|integer',
        ]);

        $year  = (int) $request->input('year');
        $month = (int) $request->input('month');
        $file  = $request->file('file');
        $ext   = strtolower($file->getClientOriginalExtension());

        try {
            $rows = in_array($ext, ['xlsx', 'xls'])
                ? $this->parseSpreadsheet($file->getRealPath())
                : $this->parseCsvFile($file->getRealPath());

            if (empty($rows)) {
                return back()->withErrors(['phones_file' => 'File appears empty.'])->withInput();
            }

            // Detect and skip header row, also locate columns
            $firstRow  = array_map(fn($v) => strtolower(trim((string)($v ?? ''))), $rows[0]);
            $hasHeader = str_contains($firstRow[0] ?? '', 'account') || str_contains($firstRow[0] ?? '', 'code') || str_contains($firstRow[0] ?? '', 'stock');

            $acctCol  = 0;
            $telCol   = 1;
            $mobCol   = null;

            if ($hasHeader) {
                foreach ($firstRow as $i => $h) {
                    if (str_contains($h, 'telephone') || $h === 'tel') $telCol = $i;
                    if (str_contains($h, 'mobile') || $h === 'mob')    $mobCol = $i;
                }
                array_shift($rows);
            }

            $now   = now()->toDateTimeString();
            $count = 0;

            foreach ($rows as $row) {
                $accountCode = strtoupper(trim((string)($row[$acctCol] ?? '')));
                if (!$accountCode) continue;

                $telephone = trim((string)($row[$telCol] ?? ''));
                $mobile    = $mobCol !== null ? trim((string)($row[$mobCol] ?? '')) : '';

                // Merge: combine both numbers if both present, separated by " / "
                $parts = array_filter([$telephone, $mobile], fn($v) => $v !== '');
                $phone = implode(' / ', $parts);

                // Store even if phone is empty so we clear old numbers
                ReminderPhone::updateOrCreate(
                    ['account_code' => $accountCode],
                    ['phone' => $phone ?: null]
                );

                // Update ALL reminder_entries for this account across all months
                DB::table('reminder_entries')
                    ->where('account_code', $accountCode)
                    ->update(['phone' => $phone ?: null, 'updated_at' => $now]);

                if ($phone) $count++;
            }

            ActivityLog::record('reminders.import_phones', "Imported {$count} phone number(s) (applied to all months)");

            return redirect()->route('reminders.index', ['year' => $year, 'month' => $month])
                ->with('success', "Updated {$count} phone number(s) across all months.");
        } catch (\Throwable $e) {
            return back()->withErrors(['phones_file' => 'Phone import failed: ' . substr($e->getMessage(), 0, 200)])->withInput();
        }
    }

    public function importOrders(Request $request): RedirectResponse
    {
        $request->validate([
            'file'  => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'year'  => 'required|integer',
            'month' => 'required|integer',
        ]);

        $year  = (int) $request->input('year');
        $month = (int) $request->input('month');
        $file  = $request->file('file');
        $ext   = strtolower($file->getClientOriginalExtension());

        try {
            $rows = in_array($ext, ['xlsx', 'xls'])
                ? $this->parseSpreadsheet($file->getRealPath())
                : $this->parseCsvFile($file->getRealPath());

            if (empty($rows)) {
                return back()->withErrors(['orders_file' => 'File appears empty.'])->withInput();
            }

            // Find ACCOUNT column from header (handles Datafile order export format)
            $header  = array_map(fn($v) => strtolower(trim((string)($v ?? ''))), $rows[0]);
            $acctCol = null;
            foreach ($header as $i => $h) {
                if ($h === 'account' || $h === 'account-code' || $h === 'accountcode' || $h === 'stock-code') {
                    $acctCol = $i;
                    break;
                }
            }
            if ($acctCol !== null) {
                array_shift($rows);
            } else {
                $acctCol = 0;
            }

            $now          = now()->toDateTimeString();
            $accountCodes = [];

            foreach ($rows as $row) {
                $accountCode = strtoupper(trim((string)($row[$acctCol] ?? '')));
                if ($accountCode) $accountCodes[] = $accountCode;
            }

            $accountCodes = array_unique($accountCodes);

            // Apply globally — mark has_ordered across all months for these accounts
            $matched = 0;
            foreach (array_chunk($accountCodes, 500) as $chunk) {
                $matched += DB::table('reminder_entries')
                    ->whereIn('account_code', $chunk)
                    ->where('has_ordered', false)
                    ->update(['has_ordered' => true, 'updated_at' => $now]);
            }

            ActivityLog::record('reminders.import_orders', "Marked {$matched} reminder entries as ordered from " . count($accountCodes) . " account codes (all months)");

            return redirect()->route('reminders.index', ['year' => $year, 'month' => $month])
                ->with('success', "Marked {$matched} entries as ordered across all months.");
        } catch (\Throwable $e) {
            return back()->withErrors(['orders_file' => 'Orders import failed: ' . substr($e->getMessage(), 0, 200)])->withInput();
        }
    }

    public function poll(Request $request): JsonResponse
    {
        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);

        $entries = DB::table('reminder_entries')
            ->leftJoin('users', 'users.id', '=', 'reminder_entries.called_by_user_id')
            ->where('reminder_entries.year', $year)
            ->where('reminder_entries.month', $month)
            ->select(
                'reminder_entries.id',
                'reminder_entries.status',
                'reminder_entries.called_by_user_id',
                'users.name as called_by_name',
                'reminder_entries.called_date',
                'reminder_entries.call_notes',
                'reminder_entries.has_ordered',
                'reminder_entries.updated_at'
            )
            ->get()
            ->keyBy('id');

        return response()->json($entries);
    }

    public function update(Request $request, ReminderEntry $entry): JsonResponse
    {
        $data = $request->validate([
            'status'            => ['sometimes', Rule::in(array_keys(ReminderEntry::STATUSES))],
            'called_by_user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'called_date'       => ['sometimes', 'nullable', 'date'],
            'call_notes'        => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $entry->update($data);

        return response()->json(['ok' => true, 'row_bg' => $entry->rowBg()]);
    }

    public function export(Request $request)
    {
        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);

        $entries = ReminderEntry::with('calledBy')
            ->where('year', $year)
            ->where('month', $month)
            ->where('has_ordered', false)
            ->whereNotIn('status', ReminderEntry::CLOSED_STATUSES)
            ->orderBy('name')
            ->get();

        $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
        $filename  = "reminders-{$year}-{$monthName}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($entries) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, [
                'Account', 'Name', 'Address', 'Postcode', 'Doc No', 'Order Value',
                'Email', 'Env Sets', 'Box Colour', 'Env Colour', 'Description',
                'Phone', 'Status', 'Called By', 'Called Date', 'Call Notes',
            ]);
            foreach ($entries as $e) {
                fputcsv($fh, [
                    $e->account_code,
                    $e->name,
                    $e->add1,
                    $e->postcode,
                    $e->doc_no,
                    $e->order_value,
                    $e->email,
                    $e->env_sets,
                    $e->box_colour,
                    $e->env_colour,
                    $e->description,
                    $e->phone,
                    ReminderEntry::STATUSES[$e->status] ?? $e->status,
                    $e->calledBy?->name,
                    $e->called_date?->format('d/m/Y'),
                    $e->call_notes,
                ]);
            }
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function parseSpreadsheet(string $path): array
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        return $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);
    }

    private function parseCsvFile(string $path): array
    {
        $content = file_get_contents($path);
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        } elseif (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $content   = str_replace(["\r\n", "\r"], "\n", $content);
        $lines     = explode("\n", trim($content));
        $delimiter = str_contains($lines[0] ?? '', "\t") ? "\t" : ',';
        return array_map(fn($line) => str_getcsv($line, $delimiter), $lines);
    }
}
