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

                    // CreditNotes pagination works fine with date filter.
                    // SalesOrders suffers the same Unleashed bug as Invoices: NumberOfPages is
                    // calculated from the total unfiltered count, so filtered queries repeat the
                    // same page. Fetch all orders (no date filter) and PHP-filter by OrderDate.
                    $fetched = $this->unleashed->parallelPaginate([
                        'sales'   => ['SalesOrders', ['startDate' => '2020-01-01']],
                        'credits' => ['CreditNotes', $params],
                    ]);

                    $salesOrders = array_filter(
                        $fetched['sales'],
                        function ($o) use ($from, $to) {
                            if (($o['OrderStatus'] ?? '') === 'Cancelled') return false;
                            if (!preg_match('/\/Date\((\d+)/', $o['OrderDate'] ?? '', $m)) return false;
                            $date = Carbon::createFromTimestamp((int) $m[1] / 1000)->utc()->toDateString();
                            return $date >= $from && $date <= $to;
                        }
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

            $grouped[$name]['count']++;
            $grouped[$name]['sub']   += (float) ($item['SubTotal'] ?? 0);
            $grouped[$name]['tax']   += (float) ($item['TaxTotal'] ?? 0);
            $grouped[$name]['total'] += (float) ($item['Total'] ?? 0);
        }

        uasort($grouped, fn($a, $b) => $b['total'] <=> $a['total']);

        return $grouped;
    }
}
