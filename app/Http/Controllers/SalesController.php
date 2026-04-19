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

                    $allOrders   = $this->unleashed->paginate('SalesOrders', $params);
                    $creditNotes = $this->unleashed->paginate('CreditNotes', $params);

                    // Invoice Enquiry: fetch invoices by invoice date, then enrich with
                    // warehouse data from the originating sales order (Unleashed confirmed
                    // the /Invoices endpoint has no warehouse field directly).
                    $invoiceItems = $this->unleashed->paginate('Invoices', $params);

                    // Build OrderNumber → warehouse map from a 180-day lookback so older
                    // orders (e.g. SO-22xxx placed months ago) are still matched to invoices.
                    $lookbackStart = Carbon::parse($from)->subDays(180)->toDateString();
                    $lookupOrders  = $this->unleashed->paginate('SalesOrders', [
                        'startDate' => $lookbackStart,
                        'endDate'   => $apiEndDate,
                    ]);
                    $warehouseMap = [];
                    foreach ($lookupOrders as $order) {
                        $num = $order['OrderNumber'] ?? '';
                        if ($num) {
                            $warehouseMap[$num] =
                                $order['Warehouse']['WarehouseName']
                                ?? $order['Warehouse']['WarehouseCode']
                                ?? 'No Warehouse';
                        }
                    }

                    // Inject warehouse into each invoice so groupByWarehouse() can use it
                    $invoices = array_map(function ($inv) use ($warehouseMap) {
                        $num = $inv['OrderNumber'] ?? $inv['SalesOrderNumber'] ?? '';
                        $inv['Warehouse'] = [
                            'WarehouseName' => $warehouseMap[$num] ?? 'No Warehouse',
                        ];
                        return $inv;
                    }, $invoiceItems);

                    // Sales Enquiry = open orders only (Parked, Placed, Backordered, Pick, Ship).
                    // Completed orders belong in Invoice Enquiry, not here.
                    $salesOrders = array_filter(
                        $allOrders,
                        fn($o) => !in_array($o['OrderStatus'] ?? '', ['Cancelled', 'Completed'])
                    );

                    return [
                        $this->groupByWarehouse($salesOrders),
                        $this->groupByWarehouse($creditNotes),
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
