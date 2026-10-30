<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerInsightsController extends Controller
{
    public function index()
    {
        return view('insights.customers');
    }

    public function data(Request $request): JsonResponse
    {
        $warehouse = $request->input('warehouse');

        try {
            $query = DB::table('sales_lines')
                ->where('status', '!=', 'deleted')
                ->whereNotNull('customer_code')
                ->whereNotNull('order_date');

            if ($warehouse) {
                $query->where('warehouse', $warehouse);
            }

            // First order date per customer
            $firstOrders = (clone $query)
                ->select('customer_code', DB::raw('MIN(order_date) as first_order_date'))
                ->groupBy('customer_code')
                ->get();

            // Group by year → month
            $byYearMonth = [];
            foreach ($firstOrders as $row) {
                $parts = explode('-', $row->first_order_date);
                $year  = (int) $parts[0];
                $month = (int) $parts[1];
                $byYearMonth[$year][$month] = ($byYearMonth[$year][$month] ?? 0) + 1;
            }
            ksort($byYearMonth);

            // Year totals (new customers)
            $yearTotals = [];
            foreach ($byYearMonth as $year => $months) {
                $yearTotals[$year] = array_sum($months);
            }

            // Total orders by month/year
            $ordersQuery = DB::table('sales_lines')
                ->where('status', '!=', 'deleted')
                ->whereNotNull('order_no')
                ->whereNotNull('order_date');

            if ($warehouse) {
                $ordersQuery->where('warehouse', $warehouse);
            }

            $orderRows = $ordersQuery
                ->select(
                    DB::raw('YEAR(order_date) as year'),
                    DB::raw('MONTH(order_date) as month'),
                    DB::raw('COUNT(DISTINCT order_no) as cnt')
                )
                ->groupBy(DB::raw('YEAR(order_date)'), DB::raw('MONTH(order_date)'))
                ->get();

            $ordersByYearMonth = [];
            $orderYearTotals   = [];
            foreach ($orderRows as $row) {
                $ordersByYearMonth[$row->year][$row->month] = (int) $row->cnt;
                $orderYearTotals[$row->year] = ($orderYearTotals[$row->year] ?? 0) + (int) $row->cnt;
            }
            ksort($ordersByYearMonth);

            // Warehouses for filter
            $warehouses = DB::table('sales_lines')
                ->distinct()
                ->orderBy('warehouse')
                ->pluck('warehouse')
                ->filter()
                ->values();

            return response()->json([
                'success'         => true,
                'data'            => $byYearMonth,
                'yearTotals'      => $yearTotals,
                'ordersData'      => $ordersByYearMonth,
                'orderYearTotals' => $orderYearTotals,
                'warehouses'      => $warehouses,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
