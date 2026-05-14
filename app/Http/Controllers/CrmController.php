<?php

namespace App\Http\Controllers;

use App\Models\KeyAccount;
use App\Models\SalesLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CrmController extends Controller
{
    public function index(Request $request): View
    {
        $warehouse  = $request->input('warehouse');
        $search     = trim($request->input('search', ''));
        $filter     = $request->input('filter'); // dropoff | overdue | null
        $limit      = max(100, (int) $request->input('limit', 100));

        $warehouses = SalesLine::where('sub_total', '>', 0)
            ->whereNotNull('warehouse')->where('warehouse', '!=', '')
            ->distinct()->orderBy('warehouse')->pluck('warehouse');

        // Use the latest order date in the dataset as the reference "today"
        // so overdue calculations aren't skewed when data hasn't been freshly imported
        $range      = DB::table('sales_lines')->selectRaw('MIN(order_date) as min_d, MAX(order_date) as max_d')->first();
        $dataFrom   = $range && $range->min_d ? Carbon::parse($range->min_d) : null;
        $dataTo     = $range && $range->max_d ? Carbon::parse($range->max_d) : null;
        $asOf       = $dataTo ?? now()->startOfDay();

        $now   = $asOf->copy()->startOfDay();
        $curr1 = $now->copy()->subMonths(12);
        $prev1 = $now->copy()->subMonths(24);

        $customers = SalesLine::query()
            ->where('sub_total', '>', 0)
            ->when($warehouse, fn($q) => $q->where('warehouse', $warehouse))
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('customer', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            }))
            ->selectRaw("
                customer_code,
                MAX(customer) as customer,
                MAX(customer_type) as customer_type,
                SUM(CASE WHEN order_date >= ? THEN sub_total ELSE 0 END) as current_total,
                SUM(CASE WHEN order_date >= ? AND order_date < ? THEN sub_total ELSE 0 END) as prev_total,
                SUM(sub_total) as all_time_total,
                MIN(order_date) as first_order_date,
                MAX(order_date) as last_order_date,
                COUNT(DISTINCT DATE(order_date)) as distinct_order_days,
                COUNT(DISTINCT order_no) as order_count
            ", [$curr1, $prev1, $curr1])
            ->groupBy('customer_code')
            ->orderByDesc('current_total')
            ->get();

        $keyAccounts = KeyAccount::with('user')
            ->whereIn('account_code', $customers->pluck('customer_code'))
            ->get()
            ->keyBy('account_code');

        $cutoff = $now->copy()->subDays(90);

        foreach ($customers as $c) {
            $c->key_account    = $keyAccounts->get($c->customer_code);
            $c->key_account_id = $c->key_account?->id;
            $c->last_order     = $c->last_order_date  ? Carbon::parse($c->last_order_date)  : null;
            $c->first_order    = $c->first_order_date ? Carbon::parse($c->first_order_date) : null;

            $prevTotal = (float) $c->prev_total;
            $currTotal = (float) $c->current_total;
            $change    = $prevTotal > 0 ? (($currTotal - $prevTotal) / $prevTotal) * 100 : null;
            $c->pct_change = $change;

            $c->is_dropoff = $prevTotal > 500
                && ($currTotal < $prevTotal * 0.6 || ($c->last_order && $c->last_order->lt($cutoff)));

            // Average order frequency and expected next order
            $c->expected_next = null;
            $c->avg_days      = null;
            if ($c->last_order && $c->first_order && (int) $c->distinct_order_days >= 2) {
                $spanDays        = $c->first_order->diffInDays($c->last_order);
                $avgDays         = round($spanDays / ((int) $c->distinct_order_days - 1));
                $c->avg_days     = $avgDays;
                $c->expected_next = $c->last_order->copy()->addDays($avgDays);
            }

            $c->is_overdue = $c->expected_next && $c->expected_next->lt($asOf) && !$c->last_order->eq($asOf);
        }

        // Apply filter
        if ($filter === 'dropoff') {
            $customers = $customers->where('is_dropoff', true)->values();
        } elseif ($filter === 'overdue') {
            $customers = $customers->where('is_overdue', true)->values();
        }

        $totalCount = $customers->count();
        $customers  = $customers->take($limit)->values();
        $hasMore    = $totalCount > $limit;

        $salesFrom = $dataFrom ? $dataFrom->format('jS M Y') : null;
        $salesTo   = $dataTo   ? $dataTo->format('jS M Y')   : null;

        return view('crm.index', compact('customers', 'warehouses', 'warehouse', 'search', 'filter', 'salesFrom', 'salesTo', 'asOf', 'limit', 'totalCount', 'hasMore'));
    }

    public function show(Request $request, string $customerCode): View
    {
        $warehouse = $request->input('warehouse');

        $lines = SalesLine::where('customer_code', $customerCode)
            ->where('sub_total', '>', 0)
            ->when($warehouse, fn($q) => $q->where('warehouse', $warehouse))
            ->orderByDesc('order_date')
            ->get();

        abort_if($lines->isEmpty(), 404);

        $customer     = $lines->first()->customer;
        $customerType = $lines->first()->customer_type;
        $keyAccount   = KeyAccount::with(['user', 'contacts.user', 'gifts'])->where('account_code', $customerCode)->first();

        $warehouses = SalesLine::where('customer_code', $customerCode)
            ->where('sub_total', '>', 0)
            ->distinct()->orderBy('warehouse')->pluck('warehouse');

        // Yearly + quarterly breakdown
        $byYear = [];
        foreach ($lines as $line) {
            $year = $line->order_date->year;
            $q    = 'q' . ceil($line->order_date->month / 3);
            $byYear[$year][$q]     = ($byYear[$year][$q] ?? 0) + (float) $line->sub_total;
            $byYear[$year]['total'] = ($byYear[$year]['total'] ?? 0) + (float) $line->sub_total;
        }
        krsort($byYear);

        // Top products by spend
        $topProducts = $lines->groupBy('product_code')->map(fn($rows) => [
            'product_code' => $rows->first()->product_code,
            'description'  => $rows->first()->product_group ?: $rows->first()->product_code,
            'total'        => $rows->sum('sub_total'),
            'qty'          => $rows->sum('quantity'),
            'orders'       => $rows->pluck('order_no')->filter()->unique()->count(),
        ])->sortByDesc('total')->take(10)->values();

        // Recent orders
        $recentOrders = $lines->groupBy('order_no')->map(fn($rows) => [
            'order_no'  => $rows->first()->order_no,
            'date'      => $rows->first()->order_date,
            'warehouse' => $rows->first()->warehouse,
            'total'     => $rows->sum('sub_total'),
            'lines'     => $rows->count(),
        ])->sortByDesc(fn($o) => $o['date'])->take(20)->values();

        // KPI totals
        $now         = now()->startOfDay();
        $total12m    = $lines->where('order_date', '>=', $now->copy()->subMonths(12))->sum('sub_total');
        $totalPrev12 = $lines->filter(fn($l) =>
            $l->order_date >= $now->copy()->subMonths(24) && $l->order_date < $now->copy()->subMonths(12)
        )->sum('sub_total');
        $lastOrder   = $lines->max('order_date');

        // Order frequency
        $orderDates  = $lines->pluck('order_date')->map(fn($d) => $d->toDateString())->unique()->sort()->values();
        $expectedNext = null;
        $avgDays      = null;
        if ($orderDates->count() >= 2) {
            $first   = Carbon::parse($orderDates->first());
            $last    = Carbon::parse($orderDates->last());
            $avgDays = round($first->diffInDays($last) / ($orderDates->count() - 1));
            $expectedNext = $last->copy()->addDays($avgDays);
        }

        return view('crm.show', compact(
            'customerCode', 'customer', 'customerType', 'keyAccount',
            'byYear', 'topProducts', 'recentOrders',
            'total12m', 'totalPrev12', 'lastOrder',
            'warehouses', 'warehouse', 'expectedNext', 'avgDays'
        ));
    }
}
