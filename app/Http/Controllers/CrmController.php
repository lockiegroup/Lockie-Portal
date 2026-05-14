<?php

namespace App\Http\Controllers;

use App\Models\KeyAccount;
use App\Models\SalesLine;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmController extends Controller
{
    public function index(Request $request): View
    {
        $warehouse = $request->input('warehouse');
        $search    = trim($request->input('search', ''));

        $warehouses = SalesLine::where('sub_total', '>', 0)
            ->whereNotNull('warehouse')
            ->where('warehouse', '!=', '')
            ->distinct()
            ->orderBy('warehouse')
            ->pluck('warehouse');

        $now      = now()->startOfDay();
        $curr1    = $now->copy()->subMonths(12);
        $prev1    = $now->copy()->subMonths(24);

        $query = SalesLine::query()
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
                MAX(order_date) as last_order_date,
                COUNT(DISTINCT order_no) as order_count
            ", [$curr1, $prev1, $curr1])
            ->groupBy('customer_code')
            ->orderByDesc('current_total');

        $customers = $query->get();

        // Index KeyAccount records so we can link through
        $keyAccounts = KeyAccount::whereIn('account_code', $customers->pluck('customer_code'))
            ->pluck('id', 'account_code');

        // Dropoff: active in prev period, current spend down >40% or no order in 90 days
        $cutoff = $now->copy()->subDays(90);
        foreach ($customers as $c) {
            $c->key_account_id = $keyAccounts->get($c->customer_code);
            $c->last_order     = $c->last_order_date ? \Carbon\Carbon::parse($c->last_order_date) : null;
            $prevTotal         = (float) $c->prev_total;
            $currTotal         = (float) $c->current_total;
            $change            = $prevTotal > 0 ? (($currTotal - $prevTotal) / $prevTotal) * 100 : null;
            $c->pct_change     = $change;
            $c->is_dropoff     = $prevTotal > 500
                && ($currTotal < $prevTotal * 0.6 || ($c->last_order && $c->last_order->lt($cutoff)));
        }

        return view('crm.index', compact('customers', 'warehouses', 'warehouse', 'search'));
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
        $keyAccount   = KeyAccount::where('account_code', $customerCode)->first();

        $warehouses = SalesLine::where('customer_code', $customerCode)
            ->where('sub_total', '>', 0)
            ->distinct()
            ->orderBy('warehouse')
            ->pluck('warehouse');

        // Yearly + quarterly breakdown
        $byYear = [];
        foreach ($lines as $line) {
            $year = $line->order_date->year;
            $q    = 'q' . ceil($line->order_date->month / 3);
            $byYear[$year][$q] = ($byYear[$year][$q] ?? 0) + (float) $line->sub_total;
            $byYear[$year]['total'] = ($byYear[$year]['total'] ?? 0) + (float) $line->sub_total;
        }
        krsort($byYear);

        // Top products by spend
        $topProducts = $lines->groupBy('product_code')->map(function ($rows) {
            return [
                'product_code' => $rows->first()->product_code,
                'description'  => $rows->first()->product_group ?: $rows->first()->product_code,
                'total'        => $rows->sum('sub_total'),
                'qty'          => $rows->sum('quantity'),
                'orders'       => $rows->pluck('order_no')->filter()->unique()->count(),
            ];
        })->sortByDesc('total')->take(10)->values();

        // Recent orders
        $recentOrders = $lines->groupBy('order_no')->map(function ($rows) {
            return [
                'order_no'   => $rows->first()->order_no,
                'date'       => $rows->first()->order_date,
                'warehouse'  => $rows->first()->warehouse,
                'total'      => $rows->sum('sub_total'),
                'lines'      => $rows->count(),
            ];
        })->sortByDesc(fn($o) => $o['date'])->take(20)->values();

        // Totals for header
        $now         = now()->startOfDay();
        $total12m    = $lines->where('order_date', '>=', $now->copy()->subMonths(12))->sum('sub_total');
        $totalPrev12 = $lines->filter(fn($l) =>
            $l->order_date >= $now->copy()->subMonths(24) && $l->order_date < $now->copy()->subMonths(12)
        )->sum('sub_total');
        $lastOrder   = $lines->max('order_date');

        return view('crm.show', compact(
            'customerCode', 'customer', 'customerType', 'keyAccount',
            'byYear', 'topProducts', 'recentOrders',
            'total12m', 'totalPrev12', 'lastOrder',
            'warehouses', 'warehouse'
        ));
    }
}
