<?php

namespace App\Http\Controllers;

use App\Models\StockWatchlistSubstitution;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

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

        $salesFrom = $salesTo = null;
        $range = DB::table('sales_lines')
            ->selectRaw('MIN(COALESCE(completed_date, order_date)) as min_d, MAX(COALESCE(completed_date, order_date)) as max_d')
            ->first();
        if ($range && $range->min_d) {
            $salesFrom = Carbon::parse($range->min_d)->format('jS M Y');
            $salesTo   = Carbon::parse($range->max_d)->format('jS M Y');
        }

        return view('imports.index', compact('doKA', 'doStock', 'substitutions', 'salesFrom', 'salesTo'));
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

            $header = array_map(fn($h) => strtolower(trim((string)($h ?? ''))), $rows[0]);

            $colOrderNo       = array_search('order no.',     $header);
            $colOrderDate     = array_search('order date',    $header);
            $colRequiredDate  = array_search('required date', $header);
            $colCompletedDate = array_search('completed date',$header);
            $colWarehouse     = array_search('warehouse',     $header);
            $colCustomer      = array_search('customer code', $header);
            $colCustomerName  = array_search('customer',      $header);
            $colCustomerType  = array_search('customer type', $header);
            $colProduct       = array_search('product code',  $header);
            $colProductGroup  = array_search('product group', $header);
            $colStatus        = array_search('status',        $header);
            $colQty           = array_search('quantity',      $header);
            $colSubTotal      = array_search('sub total',     $header);

            $missing = [];
            foreach (['order date' => 'Order Date', 'customer code' => 'Customer Code', 'product code' => 'Product Code', 'quantity' => 'Quantity', 'sub total' => 'Sub Total'] as $key => $label) {
                if (array_search($key, $header) === false) $missing[] = $label;
            }
            if (!empty($missing)) {
                return back()->withErrors(['file' => 'Required columns not found: ' . implode(', ', $missing) . '. Expected: Order No., Order Date, Required Date, Completed Date, Warehouse, Customer Code, Customer, Customer Type, Product Code, Product Group, Status, Quantity, Sub Total.']);
            }

            $substitutions = StockWatchlistSubstitution::all()->map(fn($s) => [
                'find'    => strtoupper($s->find),
                'replace' => strtoupper($s->replace),
            ])->all();

            array_shift($rows);

            $insertRows = [];
            $now        = now()->toDateTimeString();

            foreach ($rows as $row) {
                $status = strtolower(trim((string)($colStatus !== false ? ($row[$colStatus] ?? '') : '')));
                if ($status === 'cancelled') continue;

                $orderDate = $this->parseDate($row[$colOrderDate] ?? null, $ext);
                if ($orderDate === null) continue;

                $productCode = strtoupper(substr(trim((string)($colProduct !== false ? ($row[$colProduct] ?? '') : '')), 0, 100));
                foreach ($substitutions as $sub) {
                    if ($productCode && str_contains($productCode, $sub['find'])) {
                        $productCode = str_replace($sub['find'], $sub['replace'], $productCode);
                    }
                }

                $insertRows[] = [
                    'order_no'        => $colOrderNo !== false      ? (substr(trim((string)($row[$colOrderNo] ?? '')), 0, 50) ?: null) : null,
                    'order_date'      => $orderDate->format('Y-m-d'),
                    'required_date'   => $colRequiredDate  !== false ? ($this->parseDate($row[$colRequiredDate]  ?? null, $ext)?->format('Y-m-d')) : null,
                    'completed_date'  => $colCompletedDate !== false ? ($this->parseDate($row[$colCompletedDate] ?? null, $ext)?->format('Y-m-d')) : null,
                    'warehouse'       => $colWarehouse     !== false ? (substr(trim((string)($row[$colWarehouse] ?? '')), 0, 100) ?: null) : null,
                    'customer_code'   => $colCustomer      !== false ? (substr(trim((string)($row[$colCustomer] ?? '')), 0, 100) ?: null) : null,
                    'customer'        => $colCustomerName  !== false ? (substr(trim((string)($row[$colCustomerName] ?? '')), 0, 255) ?: null) : null,
                    'customer_type'   => $colCustomerType  !== false ? (substr(trim((string)($row[$colCustomerType] ?? '')), 0, 100) ?: null) : null,
                    'product_code'    => $productCode ?: null,
                    'product_group'   => $colProductGroup  !== false ? (substr(trim((string)($row[$colProductGroup] ?? '')), 0, 100) ?: null) : null,
                    'status'          => $status ? substr($status, 0, 50) : null,
                    'quantity'        => max(0, (float)str_replace([',', '£', '$', '€'], '', $colQty !== false ? ($row[$colQty] ?? 0) : 0)),
                    'sub_total'       => max(0, (float)str_replace([',', '£', '$', '€'], '', $colSubTotal !== false ? ($row[$colSubTotal] ?? 0) : 0)),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            DB::transaction(function () use ($insertRows) {
                DB::table('sales_lines')->delete();
                foreach (array_chunk($insertRows, 500) as $chunk) {
                    DB::table('sales_lines')->insert($chunk);
                }
            });

            $count = count($insertRows);
            ActivityLog::record('imports.sales', "Imported {$count} sales line(s)");

            return back()->with('success', "Imported {$count} sales line(s) into master sales table.");
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Could not read file: ' . $e->getMessage()]);
        }
    }

    private function parseSpreadsheet(string $path): array
    {
        return IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);
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

    private function parseDate(mixed $raw, string $ext): ?\DateTime
    {
        if ($raw === null || $raw === '') return null;

        if (in_array($ext, ['xlsx', 'xls']) && is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject($raw);
            } catch (\Throwable) {
                return null;
            }
        }

        return \DateTime::createFromFormat('d/m/Y', (string)$raw)
            ?: \DateTime::createFromFormat('Y-m-d', (string)$raw)
            ?: (($ts = strtotime((string)$raw)) ? (new \DateTime())->setTimestamp($ts) : null);
    }
}
