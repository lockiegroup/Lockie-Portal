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
            [$salesByWarehouse, $creditsByWarehouse] = Cache::remember(
                $cacheKey,
                1800,
                function () use ($from, $to) {
                    $apiEndDate = Carbon::parse($to)->addDay()->toDateString();
                    $params     = ['startDate' => $from, 'endDate' => $apiEndDate];

                    // Unleashed's own Sales Enquiry sums invoice values by invoice date.
                    // Fetch all invoices unfiltered (pagination is accurate for unfiltered
                    // queries), then PHP-filter to Completed invoices with InvoiceDate in range.
                    $fetched = $this->unleashed->parallelPaginate([
                        'invoices' => ['Invoices', ['startDate' => '2020-01-01']],
                        'credits'  => ['CreditNotes', $params],
                    ]);

                    // De-duplicate and filter invoices by InvoiceDate in range
                    $uniqueInvoices = [];
                    foreach ($fetched['invoices'] as $inv) {
                        $uniqueInvoices[$inv['InvoiceNumber']] = $inv;
                    }
                    $invoices = array_values(array_filter(
                        array_values($uniqueInvoices),
                        function ($inv) use ($from, $to) {
                            if (($inv['InvoiceStatus'] ?? '') !== 'Completed') return false;
                            $date = $this->unleashed->parseDate($inv['InvoiceDate'] ?? null);
                            if ($date === null) return false;
                            return $date >= $from && $date <= $to;
                        }
                    ));

                    // Resolve warehouse for each invoice via its sales order
                    $orderNums = array_values(array_unique(array_filter(array_map(
                        fn($inv) => $inv['OrderNumber'] ?? $inv['SalesOrderNumber'] ?? '',
                        $invoices
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

                    $invoices = array_map(function ($inv) use ($warehouseMap) {
                        $num = $inv['OrderNumber'] ?? $inv['SalesOrderNumber'] ?? '';
                        $inv['Warehouse'] = ['WarehouseName' => $warehouseMap[$num] ?? 'No Warehouse'];
                        return $inv;
                    }, $invoices);

                    return [
                        $this->groupByWarehouse($invoices),
                        $this->groupByWarehouse($fetched['credits']),
                    ];
                }
            );

            return response()->json([
                'success'            => true,
                'salesByWarehouse'   => $salesByWarehouse,
                'creditsByWarehouse' => $creditsByWarehouse,
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
