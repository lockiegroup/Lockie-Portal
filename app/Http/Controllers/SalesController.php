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
                    $apiEndDate    = Carbon::parse($to)->addDay()->toDateString();
                    $params        = ['startDate' => $from, 'endDate' => $apiEndDate];
                    $lookbackStart = Carbon::parse($from)->subDays(90)->toDateString();

                    $fetched = $this->unleashed->parallelPaginate([
                        'sales'   => ['SalesOrders', $params],
                        'credits' => ['CreditNotes', $params],
                        'invoices' => ['SalesOrders', [
                            'startDate'   => $lookbackStart,
                            'endDate'     => $apiEndDate,
                            'orderStatus' => 'Completed',
                        ]],
                    ]);

                    $invoices = array_filter($fetched['invoices'], function ($o) use ($from, $to) {
                        if (empty($o['CompletedDate'])) return false;
                        $dateStr = $o['CompletedDate'];
                        if (preg_match('/\/Date\((\d+)(?:[+-]\d{4})?\)\//', $dateStr, $m)) {
                            $completed = date('Y-m-d', (int)$m[1] / 1000);
                        } else {
                            $completed = substr($dateStr, 0, 10);
                        }
                        return $completed >= $from && $completed <= $to;
                    });

                    $salesOrders = array_filter(
                        $fetched['sales'],
                        fn($o) => ($o['OrderStatus'] ?? '') !== 'Cancelled'
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
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
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
            $grouped[$name]['sub']   += (float) ($item['BCSubTotal'] ?? $item['SubTotal'] ?? 0);
            $grouped[$name]['tax']   += (float) ($item['BCTaxTotal'] ?? $item['TaxTotal'] ?? 0);
            $grouped[$name]['total'] += (float) ($item['BCTotal']    ?? $item['Total']    ?? 0);
        }

        uasort($grouped, fn($a, $b) => $b['total'] <=> $a['total']);

        return $grouped;
    }
}
