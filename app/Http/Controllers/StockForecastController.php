<?php

namespace App\Http\Controllers;

use App\Models\ForecastLine;
use App\Models\ForecastProduct;
use App\Models\SupplierSetting;
use App\Services\UnleashedService;
use Illuminate\Http\Request;

class StockForecastController extends Controller
{
    private UnleashedService $unleashed;

    public function __construct()
    {
        $this->unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );
    }

    public function index(Request $request)
    {
        $warehouseFilter = $request->input('warehouse');
        $supplierFilter  = $request->input('supplier');
        $statusFilter    = $request->input('status');
        $search          = trim($request->input('search', ''));

        $query = ForecastLine::with('product')
            ->join('forecast_products', 'forecast_lines.product_id', '=', 'forecast_products.id')
            ->when($warehouseFilter, fn($q) => $q->where('forecast_lines.warehouse_code', $warehouseFilter))
            ->when($supplierFilter,  fn($q) => $q->where('forecast_products.supplier_name', $supplierFilter))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('forecast_products.product_code', 'like', "%{$search}%")
                        ->orWhere('forecast_products.product_name', 'like', "%{$search}%");
                });
            })
            ->select('forecast_lines.*')
            ->orderBy('forecast_products.product_code');

        $rows = $query->get();

        $supplierLeadTimes = SupplierSetting::pluck('lead_time_weeks', 'supplier_name')->toArray();

        $rows = $rows->map(function ($line) use ($supplierLeadTimes) {
            $product  = $line->product;
            $leadTime = $line->lead_time_override
                ?? ($supplierLeadTimes[$product->supplier_name ?? ''] ?? 4);

            $avgWeekly = $line->qty_sold_90d / 13;
            $available = (float)$line->qty_on_hand + (float)$line->qty_incoming;

            if ($avgWeekly > 0) {
                $weeksLeft = $available / $avgWeekly;
            } elseif ($available > 0) {
                $weeksLeft = 999;
            } else {
                $weeksLeft = 0;
            }

            $reorderBy = now()->addDays((int)($weeksLeft * 7));

            if ($weeksLeft < $leadTime / 2)          $status = 'critical';
            elseif ($weeksLeft < $leadTime)            $status = 'order_now';
            elseif ($weeksLeft < $leadTime * 1.5)      $status = 'order_soon';
            else                                       $status = 'ok';

            $line->computed_lead_time  = $leadTime;
            $line->computed_avg_weekly = round($avgWeekly, 1);
            $line->computed_weeks_left = $weeksLeft < 999 ? round($weeksLeft, 1) : 999;
            $line->computed_reorder_by = $reorderBy;
            $line->computed_status     = $status;
            return $line;
        });

        if ($statusFilter) {
            $rows = $rows->filter(fn($r) => $r->computed_status === $statusFilter);
        }

        $perPage  = 200;
        $page     = max(1, (int) $request->input('page', 1));
        $total    = $rows->count();
        $rows     = $rows->slice(($page - 1) * $perPage, $perPage)->values();
        $lastPage = max(1, (int) ceil($total / $perPage));

        $warehouses = ForecastLine::distinct()->orderBy('warehouse_name')
            ->pluck('warehouse_name', 'warehouse_code');
        $suppliers  = ForecastProduct::whereNotNull('supplier_name')
            ->distinct()->orderBy('supplier_name')
            ->pluck('supplier_name');
        $lastSynced = ForecastLine::max('last_synced_at');

        return view('stock-forecast.index', compact(
            'rows', 'warehouses', 'suppliers',
            'warehouseFilter', 'supplierFilter', 'statusFilter', 'search',
            'total', 'page', 'lastPage', 'lastSynced'
        ));
    }

    public function sync()
    {
        set_time_limit(300);
        try {
            $count = $this->runSync();
            \App\Models\ActivityLog::record('stock_forecast.sync', "Synced stock forecast: {$count} rows updated");
            return response()->json(['success' => true, 'row_count' => $count]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateLeadTime(Request $request, ForecastLine $line)
    {
        $request->validate(['lead_time_override' => 'nullable|integer|min:1|max:52']);
        $line->update(['lead_time_override' => $request->input('lead_time_override') ?: null]);
        return response()->json(['success' => true]);
    }

    private function runSync(): int
    {
        $now = now();
        $ts  = $now->toDateTimeString();

        // 1. Warehouses
        $warehouses = [];
        foreach ($this->unleashed->get('Warehouses', ['pageSize' => 200, 'pageNumber' => 1])['Items'] ?? [] as $wh) {
            $code = $wh['WarehouseCode'] ?? '';
            if ($code) $warehouses[$code] = $wh['WarehouseName'] ?? $code;
        }

        // 2. Products — bulk upsert, then load code→id map in one query
        $rawProducts = $this->unleashed->fetchProducts();
        $productRows = [];
        foreach ($rawProducts as $p) {
            $guid = $p['Guid'] ?? null;
            $code = $p['ProductCode'] ?? null;
            if (!$guid || !$code) continue;
            $productRows[] = [
                'guid'          => $guid,
                'product_code'  => $code,
                'product_name'  => $p['ProductDescription'] ?? $code,
                'supplier_name' => $p['DefaultSupplier']['SupplierName'] ?? null,
                'created_at'    => $ts,
                'updated_at'    => $ts,
            ];
        }
        foreach (array_chunk($productRows, 500) as $chunk) {
            ForecastProduct::upsert($chunk, ['guid'], ['product_code', 'product_name', 'supplier_name', 'updated_at']);
        }
        $codeToId = ForecastProduct::pluck('id', 'product_code')->toArray();

        // 3. Open POs
        $poData = $this->unleashed->fetchOpenPurchaseOrders();

        // 4. Sales — last 90 days (parallel paginate handles all at once)
        $allSales = $this->unleashed->fetchInvoicedSales($now->copy()->subDays(90)->format('Y-m-d'));

        // 5. Stock on hand — all warehouses fetched in parallel
        $whRequests = [];
        foreach ($warehouses as $code => $name) {
            $whRequests[$code] = ['StockOnHand', ['warehouseCode' => $code]];
        }
        $allStock = $this->unleashed->parallelPaginate($whRequests, 2000);

        // 6. Build forecast_lines rows and bulk upsert
        $lineRows = [];
        foreach ($warehouses as $whCode => $whName) {
            $stockByCode = [];
            foreach ($allStock[$whCode] ?? [] as $item) {
                $c = $item['ProductCode'] ?? null;
                if ($c) $stockByCode[$c] = ($stockByCode[$c] ?? 0.0) + (float) ($item['QtyOnHand'] ?? 0);
            }
            $salesByCode = $allSales[$whCode] ?? [];
            $allCodes    = array_unique(array_merge(array_keys($stockByCode), array_keys($salesByCode)));

            foreach ($allCodes as $code) {
                $productId = $codeToId[$code] ?? null;
                if (!$productId) continue;

                $qty    = $stockByCode[$code] ?? 0.0;
                $sold   = $salesByCode[$code] ?? 0.0;
                $poInfo = $poData[$code] ?? ['qty' => 0.0, 'date' => null];

                if ($qty <= 0 && $poInfo['qty'] <= 0 && $sold <= 0) continue;

                $lineRows[] = [
                    'product_id'       => $productId,
                    'warehouse_code'   => $whCode,
                    'warehouse_name'   => $whName,
                    'qty_on_hand'      => $qty,
                    'qty_incoming'     => $poInfo['qty'],
                    'po_expected_date' => $poInfo['date'],
                    'qty_sold_90d'     => $sold,
                    'last_synced_at'   => $ts,
                ];
            }
        }

        foreach (array_chunk($lineRows, 500) as $chunk) {
            ForecastLine::upsert(
                $chunk,
                ['product_id', 'warehouse_code'],
                ['warehouse_name', 'qty_on_hand', 'qty_incoming', 'po_expected_date', 'qty_sold_90d', 'last_synced_at']
            );
        }

        return count($lineRows);
    }
}
