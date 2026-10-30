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

            // Year totals
            $yearTotals = [];
            foreach ($byYearMonth as $year => $months) {
                $yearTotals[$year] = array_sum($months);
            }

            // Warehouses for filter
            $warehouses = DB::table('sales_lines')
                ->distinct()
                ->orderBy('warehouse')
                ->pluck('warehouse')
                ->filter()
                ->values();

            return response()->json([
                'success'    => true,
                'data'       => $byYearMonth,
                'yearTotals' => $yearTotals,
                'warehouses' => $warehouses,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
