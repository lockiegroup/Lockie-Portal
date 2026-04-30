<?php

namespace App\Http\Controllers;

use App\Models\StockWatchlistCategory;
use App\Models\StockWatchlistItem;
use App\Models\StockWatchlistStock;
use App\Models\StockWatchlistSubstitution;
use App\Services\StockWatchlistSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockWatchlistController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $categories   = StockWatchlistCategory::with(['items' => fn($q) => $q->orderBy('position')])->orderBy('position')->get();
        $productCodes = $categories->flatMap(fn($c) => $c->items->pluck('product_code'))->unique()->values()->all();

        // Session-stored date range, defaulting to start of 2 years ago → today
        $defaultFrom = now()->subYears(2)->startOfYear()->format('Y-m-d');
        $defaultTo   = now()->format('Y-m-d');
        $filterFrom  = session('stock_sales_from', $defaultFrom);
        $filterTo    = session('stock_sales_to',   $defaultTo);

        $stockMap = StockWatchlistStock::whereIn('product_code', $productCodes)->get()->keyBy('product_code');

        $salesMap = [];
        if (!empty($productCodes)) {
            DB::table('sales_lines')
                ->selectRaw("
                    product_code,
                    YEAR(order_date)  AS year,
                    MONTH(order_date) AS month,
                    SUM(quantity) AS qty_sold
                ")
                ->whereIn('product_code', $productCodes)
                ->whereRaw('order_date BETWEEN ? AND ?', [$filterFrom, $filterTo])
                ->where('quantity', '>', 0)
                ->groupByRaw('product_code, YEAR(order_date), MONTH(order_date)')
                ->get()
                ->each(function ($s) use (&$salesMap) {
                    $salesMap[$s->product_code][(int)$s->year][(int)$s->month] = (float)$s->qty_sold;
                });
        }

        // Years present in the filtered data
        $years = [];
        if (!empty($productCodes)) {
            $years = DB::table('sales_lines')
                ->selectRaw('DISTINCT YEAR(order_date) AS yr')
                ->whereIn('product_code', $productCodes)
                ->whereRaw('order_date BETWEEN ? AND ?', [$filterFrom, $filterTo])
                ->where('quantity', '>', 0)
                ->orderBy('yr')
                ->pluck('yr')
                ->map(fn($y) => (int)$y)
                ->all();
        }
        if (empty($years)) {
            $years = [(int)$now->year];
        }

        $categories->each(function ($cat) use ($stockMap, $salesMap, $years, $now) {
            $leadDays = max(1, (int)($cat->lead_time_days ?? 30));
            $cat->items->each(function ($item) use ($stockMap, $salesMap, $years, $now, $leadDays) {
                $code  = $item->product_code;
                $stock = $stockMap[$code] ?? null;
                $item->stock = $stock;

                // Yearly totals for display columns
                $yearly = [];
                foreach ($years as $yr) {
                    $total = 0;
                    foreach ($salesMap[$code][$yr] ?? [] as $qty) {
                        $total += $qty;
                    }
                    $yearly[$yr] = $total;
                }
                $item->yearly = $yearly;

                // Rolling 24-month average (previous 24 complete months)
                $totalQty = 0;
                $cutoff   = $now->copy()->startOfMonth();
                for ($i = 1; $i <= 24; $i++) {
                    $dt        = $cutoff->copy()->subMonths($i);
                    $totalQty += $salesMap[$code][$dt->year][$dt->month] ?? 0;
                }
                $item->avg_monthly = $totalQty / 24;

                // Required = avg_daily * lead_days - available
                $onHand    = $stock ? (float)$stock->qty_on_hand : 0;
                $allocated = $stock ? (float)$stock->qty_allocated : 0;
                $onOrder   = $stock ? (float)$stock->qty_on_order : 0;
                $available = ($onHand - $allocated) + $onOrder;
                $needed    = ($item->avg_monthly / 30.4375) * $leadDays;
                $item->required_qty = max(0, (int)ceil($needed - $available));
            });
        });

        $syncedAt      = StockWatchlistStock::max('synced_at');
        $substitutions = StockWatchlistSubstitution::orderBy('id')->get();

        $salesFrom = $salesTo = null;
        $range = DB::table('sales_lines')->selectRaw('MIN(order_date) as min_d, MAX(order_date) as max_d')->first();
        if ($range && $range->min_d) {
            $salesFrom = Carbon::parse($range->min_d)->format('jS M Y');
            $salesTo   = Carbon::parse($range->max_d)->format('jS M Y');
        }

        return view('stock-watchlist.index', compact('categories', 'years', 'syncedAt', 'filterFrom', 'filterTo', 'substitutions', 'salesFrom', 'salesTo'));
    }

    public function setDateFilter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sales_from' => ['required', 'date'],
            'sales_to'   => ['required', 'date', 'after_or_equal:sales_from'],
        ]);
        session(['stock_sales_from' => $data['sales_from'], 'stock_sales_to' => $data['sales_to']]);
        return redirect()->route('stock-watchlist.index');
    }

    public function sync()
    {
        set_time_limit(300);
        try {
            $result = (new StockWatchlistSyncService())->run();
            return response()->json(['ok' => true, 'products' => $result['products'], 'debug' => $result['stock']]);
        } catch (\Throwable $e) {
            \Log::error('StockWatchlist sync failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $pos  = (StockWatchlistCategory::max('position') ?? 0) + 1;
        $cat  = StockWatchlistCategory::create(['name' => $data['name'], 'position' => $pos]);
        return response()->json($cat);
    }

    public function updateCategory(Request $request, StockWatchlistCategory $category)
    {
        $data = $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'lead_time_days' => 'sometimes|integer|min:1|max:3650',
            'currency'       => 'sometimes|string|max:5',
        ]);
        $category->update($data);
        return response()->json($category);
    }

    public function downloadItems(StockWatchlistCategory $category)
    {
        $items = $category->items()->orderBy('position')->get();
        $csv   = "Product Code,Price\n";
        foreach ($items as $item) {
            $price = $item->unit_price > 0 ? number_format((float)$item->unit_price, 2, '.', '') : '';
            $csv  .= "\"{$item->product_code}\",\"{$price}\"\n";
        }
        $filename = preg_replace('/[^a-z0-9]+/', '-', strtolower($category->name)) . '-products.csv';
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function importItems(Request $request, StockWatchlistCategory $category)
    {
        $request->validate(['file' => 'required|file|max:5120']);

        $content = file_get_contents($request->file('file')->getRealPath());
        $content = ltrim($content, "\xEF\xBB\xBF");
        $content = str_replace("\r\n", "\n", str_replace("\r", "\n", $content));
        $lines   = explode("\n", trim($content));

        $firstLine = $lines[0] ?? '';
        $delimiter = str_contains($firstLine, "\t") ? "\t" : ',';
        $headers   = array_map('trim', str_getcsv($firstLine, $delimiter));
        $colMap    = array_flip(array_map('strtolower', $headers));
        $codeCol   = $colMap['product code'] ?? $colMap['product_code'] ?? 0;
        $priceCol  = $colMap['price'] ?? null;

        $added    = 0;
        $updated  = 0;
        $position = 1;

        foreach (array_slice($lines, 1) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $row  = str_getcsv($line, $delimiter);
            $code = strtoupper(trim($row[$codeCol] ?? ''));
            if (!$code) continue;

            $price = ($priceCol !== null && isset($row[$priceCol]))
                ? (float) preg_replace('/[^0-9.]/', '', $row[$priceCol])
                : null;

            $existing = StockWatchlistItem::where('product_code', $code)
                ->where('category_id', $category->id)
                ->first();

            if ($existing) {
                $updateData = ['position' => $position];
                if ($price !== null && $price > 0) $updateData['unit_price'] = $price;
                $existing->update($updateData);
                $updated++;
            } elseif (!StockWatchlistItem::where('product_code', $code)->exists()) {
                $data = ['product_code' => $code, 'position' => $position];
                if ($price !== null && $price > 0) $data['unit_price'] = $price;
                $category->items()->create($data);
                $added++;
            }

            $position++;
        }

        return response()->json(['ok' => true, 'added' => $added, 'updated' => $updated]);
    }

    public function destroyCategory(StockWatchlistCategory $category)
    {
        $category->items()->delete();
        $category->delete();
        return response()->json(['ok' => true]);
    }

    public function storeItem(Request $request, StockWatchlistCategory $category)
    {
        $data = $request->validate(['product_code' => 'required|string|max:100']);
        $code = strtoupper(trim($data['product_code']));

        if (StockWatchlistItem::where('product_code', $code)->exists()) {
            return response()->json(['error' => 'Product code already in watchlist'], 422);
        }

        $pos  = (StockWatchlistItem::where('category_id', $category->id)->max('position') ?? 0) + 1;
        $item = $category->items()->create(['product_code' => $code, 'position' => $pos]);

        return response()->json($item);
    }

    public function reorderItems(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        foreach ($ids as $position => $id) {
            StockWatchlistItem::where('id', $id)->update(['position' => $position + 1]);
        }
        return response()->json(['ok' => true]);
    }

    public function updateItem(Request $request, StockWatchlistItem $item)
    {
        $data = $request->validate([
            'unit_price'   => 'nullable|numeric|min:0',
            'to_order_qty' => 'nullable|numeric|min:0',
            'info'         => 'nullable|string|max:1000',
            'discontinued' => 'nullable|boolean',
        ]);

        $item->update(array_filter($data, fn($v) => $v !== null));
        return response()->json(['ok' => true]);
    }

    public function storeSubstitution(Request $request)
    {
        $data = $request->validate(['find' => 'required|string|max:100', 'replace' => 'required|string|max:100']);
        $sub  = StockWatchlistSubstitution::create([
            'find'    => strtoupper(trim($data['find'])),
            'replace' => strtoupper(trim($data['replace'])),
        ]);
        return response()->json($sub);
    }

    public function destroySubstitution(StockWatchlistSubstitution $substitution)
    {
        $substitution->delete();
        return response()->json(['ok' => true]);
    }

    public function clearOrders()
    {
        StockWatchlistItem::query()->update(['to_order_qty' => null]);
        return response()->json(['ok' => true]);
    }

    public function destroyItem(StockWatchlistItem $item)
    {
        $item->delete();
        return response()->json(['ok' => true]);
    }
}
