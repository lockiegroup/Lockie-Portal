<?php

namespace App\Http\Controllers;

use App\Models\KeyAccount;
use App\Models\KeyAccountContact;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CrmController extends Controller
{
    public function index(Request $request): View
    {
        $warehouse = $request->input('warehouse');
        $search    = trim($request->input('search', ''));
        $filter    = $request->input('filter');
        $limit     = max(100, (int) $request->input('limit', 100));

        $warehouses = DB::table('sales_lines')
            ->where('sub_total', '>', 0)
            ->whereNotNull('warehouse')->where('warehouse', '!=', '')
            ->distinct()->orderBy('warehouse')->pluck('warehouse');

        $range    = DB::table('sales_lines')->selectRaw('MIN(order_date) as min_d, MAX(order_date) as max_d')->first();
        $dataFrom = $range && $range->min_d ? Carbon::parse($range->min_d) : null;
        $dataTo   = $range && $range->max_d ? Carbon::parse($range->max_d) : null;
        $asOf     = $dataTo ?? now()->startOfDay();

        $now    = $asOf->copy()->startOfDay();
        $cutoff = $now->copy()->subDays(90)->toDateString();
        $curr1  = $now->copy()->subMonths(12)->toDateString();
        $prev1  = $now->copy()->subMonths(24)->toDateString();

        $query = DB::table('sales_lines')
            ->where('sub_total', '>', 0)
            ->selectRaw("
                customer_code,
                MAX(customer) as customer,
                MAX(customer_type) as customer_type,
                SUM(CASE WHEN order_date >= ? THEN sub_total ELSE 0 END) as current_total,
                SUM(CASE WHEN order_date >= ? AND order_date < ? THEN sub_total ELSE 0 END) as prev_total,
                MIN(order_date) as first_order_date,
                MAX(order_date) as last_order_date,
                COUNT(DISTINCT DATE(order_date)) as distinct_order_days
            ", [$curr1, $prev1, $curr1])
            ->groupBy('customer_code')
            ->orderByRaw('SUM(CASE WHEN order_date >= ? THEN sub_total ELSE 0 END) DESC', [$curr1]);

        if ($warehouse) {
            $query->where('warehouse', $warehouse);
        }

        $customers = $query->get();

        if ($search) {
            $customers = $customers->filter(fn($c) =>
                str_contains(strtolower($c->customer ?? ''), strtolower($search)) ||
                str_contains(strtolower($c->customer_code ?? ''), strtolower($search))
            )->values();
        }

        $keyAccounts = KeyAccount::with('user')
            ->whereIn('account_code', $customers->pluck('customer_code'))
            ->get()
            ->keyBy('account_code');

        foreach ($customers as $c) {
            $c->key_account    = $keyAccounts->get($c->customer_code);
            $c->key_account_id = $c->key_account?->id;
            $c->last_order     = $c->last_order_date  ? Carbon::parse($c->last_order_date)  : null;
            $c->first_order    = $c->first_order_date ? Carbon::parse($c->first_order_date) : null;

            $prevTotal     = (float) $c->prev_total;
            $currTotal     = (float) $c->current_total;
            $c->pct_change = $prevTotal > 0 ? (($currTotal - $prevTotal) / $prevTotal) * 100 : null;

            $c->is_dropoff = $prevTotal > 500
                && ($currTotal < $prevTotal * 0.6 || ($c->last_order && $c->last_order_date < $cutoff));

            $c->expected_next = null;
            $c->avg_days      = null;
            if ($c->last_order && $c->first_order && (int) $c->distinct_order_days >= 2) {
                $span             = $c->first_order->diffInDays($c->last_order);
                $avg              = round($span / ((int) $c->distinct_order_days - 1));
                $c->avg_days      = $avg;
                $c->expected_next = $c->last_order->copy()->addDays($avg);
            }

            $c->is_overdue = $c->expected_next
                && $c->expected_next->lt($asOf)
                && $c->last_order_date < $asOf->toDateString();
        }

        if ($filter === 'dropoff') {
            $customers = $customers->filter(fn($c) => $c->is_dropoff)->values();
        } elseif ($filter === 'overdue') {
            $customers = $customers->filter(fn($c) => $c->is_overdue)->values();
        }

        $totalCount = $customers->count();
        $customers  = $customers->take($limit)->values();
        $hasMore    = $totalCount > $limit;

        $salesFrom = $dataFrom ? $dataFrom->format('jS M Y') : null;
        $salesTo   = $dataTo   ? $dataTo->format('jS M Y')   : null;

        return view('crm.index', compact(
            'customers', 'warehouses', 'warehouse', 'search', 'filter',
            'salesFrom', 'salesTo', 'asOf', 'limit', 'totalCount', 'hasMore'
        ));
    }

    public function show(Request $request, string $customerCode): View
    {
        $warehouse = $request->input('warehouse');

        $check = DB::table('sales_lines')
            ->where('customer_code', $customerCode)
            ->where('sub_total', '>', 0)
            ->selectRaw('MAX(customer) as customer, MAX(customer_type) as customer_type, COUNT(*) as cnt')
            ->first();

        abort_if(!$check || $check->cnt == 0, 404);

        $customer     = $check->customer;
        $customerType = $check->customer_type;
        $keyAccount   = KeyAccount::with(['user', 'contacts.user', 'gifts'])->where('account_code', $customerCode)->first();

        $warehouses = DB::table('sales_lines')
            ->where('customer_code', $customerCode)
            ->where('sub_total', '>', 0)
            ->distinct()->orderBy('warehouse')->pluck('warehouse');

        $range = DB::table('sales_lines')->selectRaw('MIN(order_date) as min_d, MAX(order_date) as max_d')->first();
        $asOf  = $range && $range->max_d ? Carbon::parse($range->max_d) : now()->startOfDay();
        $cut12 = $asOf->copy()->subMonths(12)->toDateString();
        $cut24 = $asOf->copy()->subMonths(24)->toDateString();

        $kpiQ = DB::table('sales_lines')
            ->where('customer_code', $customerCode)
            ->where('sub_total', '>', 0)
            ->selectRaw("
                SUM(CASE WHEN order_date >= ? THEN sub_total ELSE 0 END) as total12m,
                SUM(CASE WHEN order_date >= ? AND order_date < ? THEN sub_total ELSE 0 END) as prev12m,
                MAX(order_date) as last_order_date,
                MIN(order_date) as first_order_date,
                COUNT(DISTINCT DATE(order_date)) as distinct_order_days
            ", [$cut12, $cut24, $cut12]);
        if ($warehouse) {
            $kpiQ->where('warehouse', $warehouse);
        }
        $kpi = $kpiQ->first();

        $total12m    = (float) ($kpi->total12m ?? 0);
        $totalPrev12 = (float) ($kpi->prev12m  ?? 0);
        $lastOrder   = $kpi->last_order_date  ? Carbon::parse($kpi->last_order_date)  : null;
        $firstOrder  = $kpi->first_order_date ? Carbon::parse($kpi->first_order_date) : null;

        $expectedNext = null;
        $avgDays      = null;
        if ($lastOrder && $firstOrder && (int) $kpi->distinct_order_days >= 2) {
            $avgDays      = round($firstOrder->diffInDays($lastOrder) / ((int) $kpi->distinct_order_days - 1));
            $expectedNext = $lastOrder->copy()->addDays($avgDays);
        }

        $qtrQ = DB::table('sales_lines')
            ->where('customer_code', $customerCode)
            ->where('sub_total', '>', 0)
            ->selectRaw("
                YEAR(order_date) as yr,
                SUM(CASE WHEN MONTH(order_date) BETWEEN 1  AND 3  THEN sub_total ELSE 0 END) as q1,
                SUM(CASE WHEN MONTH(order_date) BETWEEN 4  AND 6  THEN sub_total ELSE 0 END) as q2,
                SUM(CASE WHEN MONTH(order_date) BETWEEN 7  AND 9  THEN sub_total ELSE 0 END) as q3,
                SUM(CASE WHEN MONTH(order_date) BETWEEN 10 AND 12 THEN sub_total ELSE 0 END) as q4,
                SUM(sub_total) as total
            ")
            ->groupByRaw('YEAR(order_date)')
            ->orderByRaw('YEAR(order_date) DESC');
        if ($warehouse) {
            $qtrQ->where('warehouse', $warehouse);
        }

        $byYear = [];
        foreach ($qtrQ->get() as $row) {
            $byYear[(int) $row->yr] = [
                'q1'    => (float) $row->q1,
                'q2'    => (float) $row->q2,
                'q3'    => (float) $row->q3,
                'q4'    => (float) $row->q4,
                'total' => (float) $row->total,
            ];
        }

        $prodQ = DB::table('sales_lines')
            ->where('customer_code', $customerCode)
            ->where('sub_total', '>', 0)
            ->selectRaw("
                product_code,
                MAX(product_group) as description,
                SUM(sub_total) as total,
                SUM(quantity) as qty,
                COUNT(DISTINCT order_no) as orders
            ")
            ->groupBy('product_code')
            ->orderByRaw('SUM(sub_total) DESC')
            ->limit(10);
        if ($warehouse) {
            $prodQ->where('warehouse', $warehouse);
        }

        $topProducts = $prodQ->get()->map(fn($r) => [
            'product_code' => $r->product_code,
            'description'  => $r->description ?: $r->product_code,
            'total'        => (float) $r->total,
            'qty'          => (float) $r->qty,
            'orders'       => (int) $r->orders,
        ])->values();

        $ordQ = DB::table('sales_lines')
            ->where('customer_code', $customerCode)
            ->where('sub_total', '>', 0)
            ->selectRaw("
                order_no,
                MAX(order_date) as order_dt,
                MAX(warehouse) as warehouse,
                SUM(sub_total) as total,
                COUNT(*) as lines
            ")
            ->groupBy('order_no')
            ->orderByRaw('MAX(order_date) DESC')
            ->limit(10);
        if ($warehouse) {
            $ordQ->where('warehouse', $warehouse);
        }

        $recentOrders = $ordQ->get()->map(fn($r) => [
            'order_no'  => $r->order_no,
            'date'      => Carbon::parse($r->order_dt),
            'warehouse' => $r->warehouse,
            'total'     => (float) $r->total,
            'lines'     => (int) $r->lines,
        ])->values();

        return view('crm.show', compact(
            'customerCode', 'customer', 'customerType', 'keyAccount',
            'byYear', 'topProducts', 'recentOrders',
            'total12m', 'totalPrev12', 'lastOrder',
            'warehouses', 'warehouse', 'expectedNext', 'avgDays'
        ));
    }

    // ── CRM-owned Key Account stubs ───────────────────────────────────────────

    private function upsertKeyAccount(string $customerCode): KeyAccount
    {
        $name = DB::table('sales_lines')->where('customer_code', $customerCode)->whereNotNull('customer')->value('customer') ?? $customerCode;

        return KeyAccount::firstOrCreate(
            ['account_code' => $customerCode],
            ['name' => $name, 'type' => 'key']
        );
    }

    public function updateNotes(Request $request, string $customerCode): RedirectResponse
    {
        $keyAccount = $this->upsertKeyAccount($customerCode);
        $keyAccount->update(['notes' => $request->input('notes')]);

        return back()->with('crm_success', 'Notes saved.');
    }

    public function storeContact(Request $request, string $customerCode): RedirectResponse
    {
        $data = $request->validate([
            'contacted_at' => ['required', 'date'],
            'note'         => ['required', 'string', 'max:2000'],
        ]);

        $keyAccount = $this->upsertKeyAccount($customerCode);
        $keyAccount->contacts()->create([
            'user_id'      => auth()->id(),
            'contacted_at' => $data['contacted_at'],
            'note'         => $data['note'],
        ]);

        return back()->with('crm_success', 'Contact logged.');
    }

    public function destroyContact(string $customerCode, KeyAccountContact $contact): RedirectResponse
    {
        $contact->delete();

        return back()->with('crm_success', 'Contact entry removed.');
    }
}
