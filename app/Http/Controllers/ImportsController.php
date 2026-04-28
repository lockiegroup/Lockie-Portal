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

        return view('imports.index', compact('doKA', 'doStock'));
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

            array_shift($rows);

            $messages = [];

            if ($doKA) {
                if ($colDate === false || $colCustomer === false || $colSubTotal === false) {
                    return back()->withErrors(['file' => 'Required columns not found. Expected: Order Date, Customer Code, Sub Total.']);
                }
                $kaCount = $this->processKeyAccounts($rows, $colDate, $colCustomer, $colSubTotal, $colStatus, $ext);
                $messages[] = "Key Accounts: sales updated for {$kaCount} account/year combination(s).";
                ActivityLog::record('imports.sales_ka', "Imported key account sales for {$kaCount} account/year(s)");
            }

            if ($doStock) {
                if ($colProduct !== false && $colDate !== false && $colQty !== false) {
                    $stockResult = $this->processStockWatchlist($rows, $colProduct, $colDate, $colQty);
                    $messages[] = "Stock Watchlist: updated {$stockResult['products']} product(s) across {$stockResult['months']} month(s).";
                    ActivityLog::record('imports.sales_stock', "Imported stock watchlist sales for {$stockResult['products']} product(s)");
                } else {
                    $messages[] = 'Stock Watchlist: skipped (Product Code or Quantity column not found).';
                }
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

        return $count;
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

        if (!empty($monthly)) {
            DB::table('stock_watchlist_sales')->whereIn('product_code', array_keys($monthly))->delete();
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
