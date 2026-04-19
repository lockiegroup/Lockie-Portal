<?php

namespace App\Http\Controllers;

use App\Services\UnleashedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SalesController extends Controller
{
    private UnleashedService $unleashed;

    public function __construct()
    {
        $this->unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key')
        );
    }

    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        return view('sales.index', compact('from', 'to'));
    }

    public function data(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $cacheKey = "unleashed_sales_{$from}_{$to}";

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        try {
            [$salesByWarehouse, $creditsByWarehouse, $invoicesByWarehouse] = Cache::remember(
                $cacheKey,
                1800,
                function () use ($from, $to) {
                    $apiEndDate = Carbon::parse($to)->addDay()->toDateString();
                    $params     = ['startDate' => $from, 'endDate' => $apiEndDate];

                    // Fetch Sales Orders, Credit Notes, and Invoices in parallel
                    $fetched = $this->unleashed->parallelPaginate([
                        'sales'    => ['SalesOrders', $params],
                        'credits'  => ['CreditNotes', $params],
                        'invoices' => ['Invoices', array_merge($params, ['invoiceStatus' => 'Completed'])],
                    ]);

                    // Resolve warehouse for each invoice by fetching only the specific
                    // sales orders referenced. Results cached per order for 7 days so
                    // repeat searches don't re-fetch the same orders.
                    $orderNums = array_values(array_unique(array_filter(array_map(
                        fn($inv) => $inv['OrderNumber'] ?? $inv['SalesOrderNumber'] ?? '',
                        $fetched['invoices']
                    ))));

                    $warehouseMap = [];
                    $uncached     = [];
                    foreach ($orderNums as $num) {
                        $cached = Cache::get("unleashed_wh_{$num}");
                        if ($cached !== null) {
                            $warehouseMap[$num] = $cached;
                        } else {
                            $uncached[] = $num;
                        }
                    }
                    if (!empty($uncached)) {
                        $resolved = $this->unleashed->fetchWarehousesByOrderNumber($uncached);
                        foreach ($resolved as $num => $wh) {
                            $warehouseMap[$num] = $wh;
                            Cache::put("unleashed_wh_{$num}", $wh, 604800);
                        }
                    }

                    // Inject warehouse into each invoice
                    $invoices = array_map(function ($inv) use ($warehouseMap) {
                        $num = $inv['OrderNumber'] ?? $inv['SalesOrderNumber'] ?? '';
                        $inv['Warehouse'] = ['WarehouseName' => $warehouseMap[$num] ?? 'No Warehouse'];
                        return $inv;
                    }, $fetched['invoices']);

                    // Sales Enquiry: open orders only — Completed go to Invoice Enquiry
                    $salesOrders = array_filter(
                        $fetched['sales'],
                        fn($o) => !in_array($o['OrderStatus'] ?? '', ['Cancelled', 'Completed'])
                    );

                    return [
                        $this->groupByWarehouse($salesOrders),
                        $this->groupByWarehouse($fetched['credits']),
                        $this->groupByWarehouse($invoices),
                    ];
                }
            );

            return response()->json([
                'success'             => true,
                'salesByWarehouse'    => $salesByWarehouse,
                'creditsByWarehouse'  => $creditsByWarehouse,
                'invoicesByWarehouse' => $invoicesByWarehouse,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => get_class($e) . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    private function groupByWarehouse(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $name = $item['Warehouse']['WarehouseName']
                ?? $item['Warehouse']['WarehouseCode']
                ?? $item['WarehouseName']
                ?? $item['WarehouseCode']
                ?? 'No Warehouse';

            if (!isset($grouped[$name])) {
                $grouped[$name] = ['count' => 0, 'sub' => 0.0, 'tax' => 0.0, 'total' => 0.0];
            }

            $grouped[$name]['count']++;
            $grouped[$name]['sub']   += (float) ($item['SubTotal'] ?? 0);
            $grouped[$name]['tax']   += (float) ($item['TaxTotal'] ?? 0);
            $grouped[$name]['total'] += (float) ($item['Total'] ?? 0);
        }

        uasort($grouped, fn($a, $b) => $b['total'] <=> $a['total']);

        return $grouped;
    }
}
