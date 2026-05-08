<?php

namespace App\Http\Controllers;

use App\Models\CollarProduct;
use App\Models\CollarStockAdjustment;
use App\Models\CollarWorksOrder;
use App\Models\CollarWorksOrderLine;
use App\Models\StockWatchlistStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollarController extends Controller
{
    public function index()
    {
        $products = CollarProduct::orderBy('position')->orderBy('description')->get();

        // Fetch made stock from Unleashed sync for products with a product_code
        $codes     = $products->pluck('product_code')->filter()->values()->all();
        $stockMap  = StockWatchlistStock::whereIn('product_code', $codes)
                        ->get()->keyBy('product_code');

        // Sales by year from sales_lines
        $salesData = [];
        if (!empty($codes)) {
            $rows = DB::table('sales_lines')
                ->selectRaw("product_code, YEAR(order_date) AS yr, SUM(quantity) AS qty")
                ->whereIn('product_code', $codes)
                ->groupBy('product_code', 'yr')
                ->get();
            foreach ($rows as $row) {
                $salesData[$row->product_code][$row->yr] = (int) $row->qty;
            }
        }

        $years = [date('Y') - 2, date('Y') - 1, date('Y')];

        $worksOrders = CollarWorksOrder::orderBy('period', 'desc')->limit(10)->get();

        return view('collars.index', compact('products', 'stockMap', 'salesData', 'years', 'worksOrders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_code'            => 'nullable|string|max:100',
            'description'             => 'required|string|max:255',
            'reel_width'              => 'nullable|string|max:50',
            'is_stock_line'           => 'boolean',
            'cut_blank_moq'           => 'nullable|integer|min:0',
            'cut_blank_reorder_level' => 'nullable|integer|min:0',
            'made_moq'                => 'nullable|integer|min:0',
            'made_reorder_level'      => 'nullable|integer|min:0',
        ]);
        $data['position'] = (CollarProduct::max('position') ?? 0) + 1;
        $product = CollarProduct::create($data);
        return response()->json($product);
    }

    public function update(Request $request, CollarProduct $collar)
    {
        $data = $request->validate([
            'product_code'            => 'nullable|string|max:100',
            'description'             => 'sometimes|required|string|max:255',
            'reel_width'              => 'nullable|string|max:50',
            'is_stock_line'           => 'sometimes|boolean',
            'cut_blank_moq'           => 'nullable|integer|min:0',
            'cut_blank_reorder_level' => 'nullable|integer|min:0',
            'made_moq'                => 'nullable|integer|min:0',
            'made_reorder_level'      => 'nullable|integer|min:0',
        ]);
        $collar->update($data);
        return response()->json(['ok' => true]);
    }

    public function destroy(CollarProduct $collar)
    {
        $collar->delete();
        return response()->json(['ok' => true]);
    }

    public function adjust(Request $request, CollarProduct $collar)
    {
        $data = $request->validate([
            'type' => 'required|in:cut_blank,made',
            'qty'  => 'required|numeric|not_in:0',
            'note' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();

        CollarStockAdjustment::create([
            'collar_product_id' => $collar->id,
            'type'              => $data['type'],
            'qty'               => $data['qty'],
            'note'              => $data['note'] ?? null,
            'created_by'        => $user?->name ?? $user?->email,
            'created_at'        => now(),
        ]);

        if ($data['type'] === 'cut_blank') {
            $collar->increment('cut_blank_stock', $data['qty']);
        }

        return response()->json([
            'ok'               => true,
            'cut_blank_stock'  => (float) $collar->fresh()->cut_blank_stock,
        ]);
    }

    public function adjustments(CollarProduct $collar)
    {
        $log = $collar->adjustments()->orderBy('created_at', 'desc')->limit(50)->get();
        return response()->json($log);
    }

    public function importCsv(Request $request)
    {
        $request->validate(['file' => 'required|file|max:5120']);
        $content = file_get_contents($request->file('file')->getRealPath());
        $content = ltrim($content, "\xEF\xBB\xBF");
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines   = explode("\n", trim($content));

        $delimiter = str_contains($lines[0] ?? '', "\t") ? "\t" : ',';
        $headers   = array_map('strtolower', array_map('trim', str_getcsv($lines[0], $delimiter)));
        $colMap    = array_flip($headers);

        $col = fn(string $key) => $colMap[$key] ?? null;
        $get = fn(array $row, ?int $idx) => $idx !== null ? trim($row[$idx] ?? '') : '';

        $imported = 0;
        $position = (CollarProduct::max('position') ?? 0) + 1;

        foreach (array_slice($lines, 1) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $row  = str_getcsv($line, $delimiter);
            $desc = $get($row, $col('description')) ?: $get($row, $col('desc')) ?: ($row[1] ?? '');
            if (!$desc) continue;

            CollarProduct::create([
                'product_code'            => strtoupper($get($row, $col('product code'))) ?: null,
                'description'             => $desc,
                'reel_width'              => $get($row, $col('reel width')) ?: null,
                'is_stock_line'           => strtolower($get($row, $col('stock line'))) === 'true' || $get($row, $col('stock line')) === '1',
                'cut_blank_stock'         => (float) ($get($row, $col('cut blank stock')) ?: 0),
                'cut_blank_moq'           => (int) ($get($row, $col('cut blank moq')) ?: 0) ?: null,
                'cut_blank_reorder_level' => (int) ($get($row, $col('cut blank reorder')) ?: 0) ?: null,
                'made_moq'                => (int) ($get($row, $col('made moq')) ?: 0) ?: null,
                'made_reorder_level'      => (int) ($get($row, $col('made reorder')) ?: 0) ?: null,
                'position'                => $position++,
            ]);
            $imported++;
        }

        return response()->json(['ok' => true, 'imported' => $imported]);
    }

    // Works Orders
    public function worksOrderCreate(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'period'      => 'required|date',
            'notes'       => 'nullable|string',
            'lines'       => 'required|array|min:1',
            'lines.*.collar_product_id' => 'required|exists:collar_products,id',
            'lines.*.type'              => 'required|in:cut_blank,made',
            'lines.*.qty'               => 'required|integer|min:1',
            'lines.*.note'              => 'nullable|string|max:255',
        ]);

        $user  = auth()->user();
        $order = CollarWorksOrder::create([
            'title'      => $data['title'],
            'period'     => $data['period'],
            'notes'      => $data['notes'] ?? null,
            'created_by' => $user?->name ?? $user?->email,
        ]);

        foreach ($data['lines'] as $line) {
            CollarWorksOrderLine::create([
                'works_order_id'    => $order->id,
                'collar_product_id' => $line['collar_product_id'],
                'type'              => $line['type'],
                'qty'               => $line['qty'],
                'note'              => $line['note'] ?? null,
            ]);
        }

        return response()->json(['ok' => true, 'id' => $order->id]);
    }

    public function worksOrderShow(CollarWorksOrder $worksOrder)
    {
        $worksOrder->load(['lines.product']);
        return view('collars.works-order', compact('worksOrder'));
    }

    public function worksOrderDestroy(CollarWorksOrder $worksOrder)
    {
        $worksOrder->delete();
        return response()->json(['ok' => true]);
    }
}
