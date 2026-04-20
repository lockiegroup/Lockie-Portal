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

                    // SalesOrders suffers the same Unleashed pagination bug as Invoices:
                    // NumberOfPages is based on the total unfiltered count, so any date-filtered
                    // query (even startDate-only) repeats pages. Fetch entirely unfiltered and
                    // PHP-filter by OrderDate — pagination is only accurate for unfiltered queries.
                    $fetched = $this->unleashed->parallelPaginate([
                        'sales'   => ['SalesOrders', []],
                        'credits' => ['CreditNotes', $params],
                    ]);

                    $salesOrders = array_values(array_filter(
                        $fetched['sales'],
                        function ($o) use ($from, $to) {
                            if (($o['OrderStatus'] ?? '') === 'Cancelled') return false;
                            $date = $this->unleashed->parseDate($o['OrderDate'] ?? null);
                            if ($date === null) return false;
                            return $date >= $from && $date <= $to;
                        }
                    ));

                    // Fetch full order details (includes SalesOrderLines) for accurate line totals.
                    // The paginated list endpoint omits line data.
                    $guids   = array_column($salesOrders, 'Guid');
                    $details = $this->unleashed->fetchSalesOrderDetails($guids);
                    $salesOrders = array_map(
                        fn($o) => $details[$o['Guid']] ?? $o,
                        $salesOrders
                    );

                    return [
                        $this->groupByWarehouse($salesOrders),
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

            // Unleashed Sales Enquiry sums line-level totals, not the order SubTotal
            // (order SubTotal is after order-level discounts, line totals are before).
            $lines   = $item['SalesOrderLines'] ?? [];
            $lineSub = array_sum(array_map(fn($l) => (float) ($l['LineTotal'] ?? 0), $lines));
            $lineTax = array_sum(array_map(fn($l) => (float) ($l['LineTax']   ?? 0), $lines));

            $grouped[$name]['count']++;
            $grouped[$name]['sub']   += $lineSub ?: (float) ($item['SubTotal'] ?? 0);
            $grouped[$name]['tax']   += $lineTax ?: (float) ($item['TaxTotal'] ?? 0);
            $grouped[$name]['total'] += ($lineSub + $lineTax) ?: (float) ($item['Total'] ?? 0);
        }

        uasort($grouped, fn($a, $b) => $b['total'] <=> $a['total']);

        return $grouped;
    }
}
