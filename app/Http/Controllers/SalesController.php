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

            [$salesByWarehouse, $creditsByWarehouse, $counts, $debug] = Cache::remember(
                $cacheKey,
                1800,
                function () use ($apiFrom, $apiTo, $from, $to) {
                    // Two calls needed: Unleashed only returns custom-status orders when
                    // customOrderStatus is passed explicitly (it overrides orderStatus).
                    // Pass each custom status individually — Unleashed does not support
                    // comma-separated values for customOrderStatus reliably.
                    $fixedSales  = $this->unleashed->fetchByDateRange('SalesOrders', [], $apiFrom, $apiTo);
                    $customSales = $this->unleashed->fetchByDateRange('SalesOrders', [
                        'customOrderStatus' => [
                            'Awaiting Proof', 'Call Off', 'Coditherm', 'Hoefon', 'Laser',
                            'PO Placed', 'Proforma', 'SKD', 'Sleeves', 'Waiting Yoseal',
                        ],
                    ], $apiFrom, $apiTo);
                    $allCredits  = $this->unleashed->fetchByDateRange('CreditNotes',  [], $apiFrom, $apiTo);

                    // Merge, dedup by GUID. Custom-status call wins on status name
                    // (Unleashed returns the custom name when customOrderStatus is specified,
                    // but returns "Parked" for the same orders when no filter is used).
                    $seenGuids = [];
                    $allSales  = [];
                    foreach (array_merge($customSales, $fixedSales) as $order) {
                        $guid = $order['Guid'] ?? null;
                        if ($guid !== null && isset($seenGuids[$guid])) continue;
                        if ($guid !== null) $seenGuids[$guid] = true;
                        $allSales[] = $order;
                    }

                    // PHP-side date filter: Unleashed's endDate is inclusive so the API
                    // chunks already cover the full range, but filter to the exact UK date
                    // range to prevent any boundary-day overshoot appearing in totals.
                    $ukTz = new \DateTimeZone('Europe/London');
                    $filterDate = function (array $records, string $field) use ($from, $to, $ukTz): array {
                        return array_values(array_filter($records, function ($rec) use ($from, $to, $ukTz, $field) {
                            $raw = $rec[$field] ?? null;
                            if (!$raw) return true;
                            try {
                                if (preg_match('#/Date\((\d+)#', $raw, $m)) {
                                    $dt = (new \DateTime('@' . (int) ($m[1] / 1000)))->setTimezone($ukTz);
                                } else {
                                    $dt = (new \DateTime($raw))->setTimezone($ukTz);
                                }
                                $d = $dt->format('Y-m-d');
                                return $d >= $from && $d <= $to;
                            } catch (\Throwable) {
                                return true;
                            }
                        }));
                    };
                    $allSales   = $filterDate($allSales,  'OrderDate');
                    $allCredits = $filterDate($allCredits, 'OrderDate');

                    // Count orders and subtotals by status before filtering
                    $statusBreakdown = [];
                    foreach ($allSales as $o) {
                        $s = $o['OrderStatus'] ?? 'Unknown';
                        $statusBreakdown[$s] = ($statusBreakdown[$s] ?? 0) + 1;
                    }
                    $statusSubTotals = [];
                    foreach ($allSales as $o) {
                        $s = $o['OrderStatus'] ?? 'Unknown';
                        $statusSubTotals[$s] = ($statusSubTotals[$s] ?? 0.0) + (float) ($o['SubTotal'] ?? 0);
                    }

                    $salesOrders = array_values(array_filter(
                        $allSales,
                        fn($o) => strcasecmp($o['OrderStatus'] ?? '', 'Deleted') !== 0
                    ));

                    $rawSubTotal = array_sum(array_column($salesOrders, 'SubTotal'));

                    return [
                        $this->groupByWarehouse($salesOrders),
                        $this->groupByWarehouse($allCredits),
                        [
                            'sales'       => count($salesOrders),
                            'salesRaw'    => count($allSales),
                            'salesFixed'  => count($fixedSales),
                            'salesCustom' => count($customSales),
                            'credits'     => count($allCredits),
                        ],
                        [
                            'statuses'       => $statusBreakdown,
                            'statusSubTotals' => $statusSubTotals,
                            'rawSubTotal'    => $rawSubTotal,
                            'apiFrom'        => $apiFrom,
                            'apiTo'          => $apiTo,
                        ],
                    ];
                }
            );

            return response()->json([
                'success'            => true,
                'salesByWarehouse'   => $salesByWarehouse,
                'creditsByWarehouse' => $creditsByWarehouse,
                'counts'             => $counts,
                'debug'              => $debug,
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
