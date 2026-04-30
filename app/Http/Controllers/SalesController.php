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
            // Unleashed API dates are UTC. Convert the user's UK (Europe/London) dates
            // to UTC so BST orders near midnight aren't dropped or duplicated.
            $ukTz    = new \DateTimeZone('Europe/London');
            $utcTz   = new \DateTimeZone('UTC');
            $apiFrom = (new \DateTime("{$from} 00:00:00", $ukTz))->setTimezone($utcTz)->format('Y-m-d');
            $apiTo   = (new \DateTime("{$to} 23:59:59",   $ukTz))->setTimezone($utcTz)->format('Y-m-d');

            [$salesByWarehouse, $creditsByWarehouse] = Cache::remember(
                $cacheKey,
                1800,
                function () use ($apiFrom, $apiTo) {
                    $params = ['startDate' => $apiFrom, 'endDate' => $apiTo];

                    $fetched = $this->unleashed->parallelPaginate([
                        'sales'   => ['SalesOrders', $params],
                        'credits' => ['CreditNotes', $params],
                    ]);

                    $salesOrders = array_values(array_filter(
                        $fetched['sales'],
                        fn($o) => ($o['OrderStatus'] ?? '') !== 'Cancelled'
                    ));

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
            $grouped[$name]['total'] += (float) ($item['Total']    ?? 0);
        }

        uasort($grouped, fn($a, $b) => $b['total'] <=> $a['total']);

        return $grouped;
    }
}
