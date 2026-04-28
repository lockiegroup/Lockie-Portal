<?php

namespace App\Http\Controllers;

use App\Models\KeyAccountSale;
use App\Models\StockWatchlistItem;
use App\Models\StockWatchlistSubstitution;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportsController extends Controller
{
    public function index(): View
    {
        $user    = auth()->user();
        $doKA    = $user->hasModule('key_accounts') || $user->can('key_accounts_admin');
        $doStock = $user->can('stock_ordering');

        if (!$doKA && !$doStock) {
            abort(403);
        }

        $substitutions = $doStock ? StockWatchlistSubstitution::orderBy('id')->get() : collect();

        return view('imports.index', compact('doKA', 'doStock', 'substitutions'));
    }

    public function storeSubstitution(Request $request): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->can('stock_ordering')) abort(403);

        $data = $request->validate([
            'find'    => ['required', 'string', 'max:100'],
            'replace' => ['required', 'string', 'max:100'],
        ]);

        StockWatchlistSubstitution::create([
            'find'    => strtoupper(trim($data['find'])),
            'replace' => strtoupper(trim($data['replace'])),
        ]);

        ActivityLog::record('imports.substitution_added', "Added substitution rule: {$data['find']} → {$data['replace']}");

        return back()->with('success', 'Substitution rule added.');
    }

    public function destroySubstitution(StockWatchlistSubstitution $substitution): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->can('stock_ordering')) abort(403);

        $substitution->delete();

        return back()->with('success', 'Substitution rule removed.');
    }

    public function storeSales(Request $request): RedirectResponse
    {
        $user    = auth()->user();
        $doKA    = $user->hasModule('key_accounts') || $user->can('key_accounts_admin');
        $doStock = $user->can('stock_ordering');

        if (!$doKA && !$doStock) {
            abort(403);
        }

        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:20480']);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        try {
            $rows = in_array($ext, ['xlsx', 'xls'])
                ? $this->parseSpreadsheet($file->getRealPath())
                : $this->parseCsv($file->getRealPath());

            if (empty($rows)) {
                return back()->withErrors(['file' => 'File appears empty.']);
            }

            $header      = array_map(fn($h) => strtolower(trim((string)($h ?? ''))), $rows[0]);
            $colDate     = array_search('order date', $header);
            $colCustomer = array_search('customer code', $header);
            $colSubTotal = array_search('sub total', $header);
            $colStatus   = array_search('status', $header);
            $colProduct  = array_search('product code', $header);
            $colQty      = array_search('quantity', $header);

            // Build list of required columns based on what this user can process
            $needed = ['order date' => 'Order Date'];
            if ($doKA)    { $needed['customer code'] = 'Customer Code'; $needed['sub total'] = 'Sub Total'; }
            if ($doStock) { $needed['product code']  = 'Product Code';  $needed['quantity']  = 'Quantity'; }

            $missing = array_values(array_filter(
                array_map(fn($key, $label) => array_search($key, $header) === false ? $label : null, array_keys($needed), $needed)
            ));

            if (!empty($missing)) {
                return back()->withErrors(['file' => 'Required columns not found: ' . implode(', ', $missing) . '. Expected: Order Date, Customer Code, Product Code, Quantity, Sub Total.']);
            }

            array_shift($rows);

            $messages = [];

            if ($doKA) {
                $kaCount = $this->processKeyAccounts($rows, $colDate, $colCustomer, $colSubTotal, $colStatus, $ext);
                $messages[] = "Key Accounts: sales updated for {$kaCount} account/year combination(s).";
                ActivityLog::record('imports.sales_ka', "Imported key account sales for {$kaCount} account/year(s)");
            }

            if ($doStock) {
                $stockResult = $this->processStockWatchlist($rows, $colProduct, $colDate, $colQty);
                $messages[] = "Stock Watchlist: updated {$stockResult['products']} product(s) across {$stockResult['months']} month(s).";
                ActivityLog::record('imports.sales_stock', "Imported stock watchlist sales for {$stockResult['products']} product(s)");
            }

            return back()->with('success', implode(' ', $messages));
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Could not read file: ' . $e->getMessage()]);
        }
    }

    private function parseSpreadsheet(string $path): array
    {
        return IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
    }

    private function parseCsv(string $path): array
    {
        $content   = file_get_contents($path);
        $content   = ltrim($content, "\xEF\xBB\xBF");
        $content   = str_replace("\r\n", "\n", str_replace("\r", "\n", $content));
        $lines     = explode("\n", trim($content));
        $delimiter = str_contains($lines[0] ?? '', "\t") ? "\t" : ',';
        return array_map(fn($line) => str_getcsv($line, $delimiter), $lines);
    }

    private function processKeyAccounts(array $rows, int|false $colDate, int|false $colCustomer, int|false $colSubTotal, int|false $colStatus, string $ext): int
    {
        $aggregated = [];

        foreach ($rows as $row) {
            $code     = trim((string)($row[$colCustomer] ?? ''));
            $subtotal = (float)str_replace([',', '£', '$', '€'], '', $row[$colSubTotal] ?? 0);
            $rawDate  = $row[$colDate] ?? null;
            $status   = strtolower(trim((string)($colStatus !== false ? ($row[$colStatus] ?? '') : '')));

            if (empty($code) || $subtotal <= 0) continue;
            if ($status === 'cancelled') continue;

            [$year, $month] = $this->parseDate($rawDate, $ext) ?? [null, null];
            if ($year === null) continue;

            $quarter = 'q' . (int)ceil($month / 3);
            $aggregated[$year][$code] ??= ['total' => 0.0, 'q1' => 0.0, 'q2' => 0.0, 'q3' => 0.0, 'q4' => 0.0];
            $aggregated[$year][$code]['total']  += $subtotal;
            $aggregated[$year][$code][$quarter] += $subtotal;
        }

        $now    = now()->toDateTimeString();
        $userId = auth()->id();

        $insertRows = [];
        foreach ($aggregated as $year => $customers) {
            foreach ($customers as $code => $data) {
                $insertRows[] = array_merge($data, [
                    'account_code' => $code,
                    'year'         => $year,
                    'imported_at'  => $now,
                    'user_id'      => $userId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }

        DB::transaction(function () use ($insertRows) {
            DB::table('key_account_sales')->delete();
            foreach (array_chunk($insertRows, 200) as $chunk) {
                DB::table('key_account_sales')->insert($chunk);
            }
        });

        return count($insertRows);
    }

    private function processStockWatchlist(array $rows, int|false $colProduct, int|false $colDate, int|false $colQty): array
    {
        $substitutions  = StockWatchlistSubstitution::all()->map(fn($s) => [
            'find'    => strtoupper($s->find),
            'replace' => strtoupper($s->replace),
        ])->all();
        $watchlistCodes = StockWatchlistItem::pluck('product_code')->all();
        $monthly        = [];

        foreach ($rows as $row) {
            $code    = strtoupper(trim((string)($row[$colProduct] ?? '')));
            $rawDate = trim((string)($row[$colDate] ?? ''));
            $qty     = (float)str_replace([',', '£', '$', '€'], '', $row[$colQty] ?? 0);

            foreach ($substitutions as $sub) {
                if (str_contains($code, $sub['find'])) {
                    $code = str_replace($sub['find'], $sub['replace'], $code);
                }
            }

            if (!$code || !$rawDate || $qty <= 0) continue;
            if (!in_array($code, $watchlistCodes)) continue;

            $dt = \DateTime::createFromFormat('d/m/Y', $rawDate)
               ?: \DateTime::createFromFormat('Y-m-d', $rawDate)
               ?: (($ts = strtotime($rawDate)) ? (new \DateTime())->setTimestamp($ts) : null);

            if (!$dt) continue;

            $year  = (int)$dt->format('Y');
            $month = (int)$dt->format('n');
            $monthly[$code][$year][$month] = ($monthly[$code][$year][$month] ?? 0) + $qty;
        }

        $insertRows = [];
        foreach ($monthly as $code => $years) {
            foreach ($years as $year => $months) {
                foreach ($months as $month => $qty) {
                    $insertRows[] = ['product_code' => $code, 'year' => $year, 'month' => $month, 'qty_sold' => $qty];
                }
            }
        }

        // Wipe all watchlist sales so stale data from previous imports doesn't linger
        if (!empty($watchlistCodes)) {
            DB::table('stock_watchlist_sales')->whereIn('product_code', $watchlistCodes)->delete();
        }

        foreach (array_chunk($insertRows, 200) as $chunk) {
            DB::table('stock_watchlist_sales')->insert($chunk);
        }

        return ['products' => count($monthly), 'months' => count($insertRows)];
    }

    private function parseDate(mixed $raw, string $ext): ?array
    {
        if ($raw === null || $raw === '') return null;

        if (in_array($ext, ['xlsx', 'xls']) && is_numeric($raw)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($raw);
                return [(int)$dt->format('Y'), (int)$dt->format('n')];
            } catch (\Throwable) {
                return null;
            }
        }

        $dt = \DateTime::createFromFormat('d/m/Y', (string)$raw)
           ?: \DateTime::createFromFormat('Y-m-d', (string)$raw)
           ?: (($ts = strtotime((string)$raw)) ? (new \DateTime())->setTimestamp($ts) : null);

        return $dt ? [(int)$dt->format('Y'), (int)$dt->format('n')] : null;
    }
}
