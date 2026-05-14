<x-layout title="Customer Insights — Lockie Portal">
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div style="margin-bottom:1.75rem;">
        <h1 class="text-2xl font-bold text-slate-800">Customer Insights</h1>
        <p class="text-slate-500 mt-1 text-sm">Ranked by spend in the last 12 months vs the prior 12 months.</p>
    </div>

    {{-- Filters --}}
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:1.25rem;">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search customer or code…"
            style="flex:1;min-width:180px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#1e293b;background:#fff;outline:none;">

        <select name="warehouse"
            style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#1e293b;background:#fff;">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $w)
                <option value="{{ $w }}" {{ $warehouse === $w ? 'selected' : '' }}>{{ $w }}</option>
            @endforeach
        </select>

        <button type="submit"
            style="padding:8px 16px;background:#1e293b;color:#fff;border:none;border-radius:8px;font-size:0.875rem;font-weight:500;cursor:pointer;">
            Filter
        </button>

        @if($search || $warehouse || $filter)
            <a href="{{ route('crm.index') }}"
               style="padding:8px 14px;background:#f1f5f9;color:#64748b;border-radius:8px;font-size:0.875rem;text-decoration:none;">
                Clear
            </a>
        @endif
    </form>

    {{-- Quick filter tabs --}}
    <div style="display:flex;gap:8px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <a href="{{ route('crm.index', array_filter(['warehouse' => $warehouse, 'search' => $search])) }}"
           style="padding:6px 14px;border-radius:999px;font-size:0.8125rem;font-weight:500;text-decoration:none;
                  {{ !$filter ? 'background:#1e293b;color:#fff;' : 'background:#f1f5f9;color:#64748b;' }}">
            All customers
        </a>
        <a href="{{ route('crm.index', array_filter(['filter' => 'dropoff', 'warehouse' => $warehouse, 'search' => $search])) }}"
           style="padding:6px 14px;border-radius:999px;font-size:0.8125rem;font-weight:500;text-decoration:none;
                  {{ $filter === 'dropoff' ? 'background:#dc2626;color:#fff;' : 'background:#fef2f2;color:#dc2626;' }}">
            ↘ Dropping off
        </a>
        <a href="{{ route('crm.index', array_filter(['filter' => 'overdue', 'warehouse' => $warehouse, 'search' => $search])) }}"
           style="padding:6px 14px;border-radius:999px;font-size:0.8125rem;font-weight:500;text-decoration:none;
                  {{ $filter === 'overdue' ? 'background:#d97706;color:#fff;' : 'background:#fffbeb;color:#d97706;' }}">
            ⏱ Overdue for order
        </a>
    </div>

    {{-- Table --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;min-width:900px;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th style="padding:10px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Customer</th>
                    <th style="padding:10px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Last 12m</th>
                    <th style="padding:10px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Prev 12m</th>
                    <th style="padding:10px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Change</th>
                    <th style="padding:10px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Last Order</th>
                    <th style="padding:10px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Expected Next</th>
                    <th style="padding:10px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                    @php
                        $pct       = $c->pct_change;
                        $hasGrown  = $pct !== null && $pct >= 5;
                        $hasDrop   = $pct !== null && $pct <= -5;
                        $pctColour = $hasGrown ? '#16a34a' : ($hasDrop ? '#dc2626' : '#94a3b8');
                        $pctBg     = $hasGrown ? '#f0fdf4' : ($hasDrop ? '#fef2f2' : '#f8fafc');
                        $daysSince = $c->last_order ? $c->last_order->diffInDays(now()) : null;

                        // Expected next order colour
                        $nextColour = '#64748b';
                        $nextBg     = 'transparent';
                        if ($c->expected_next) {
                            $daysUntil = now()->diffInDays($c->expected_next, false); // negative = overdue
                            if ($daysUntil < 0)        { $nextColour = '#dc2626'; $nextBg = '#fef2f2'; }
                            elseif ($daysUntil <= 14)  { $nextColour = '#d97706'; $nextBg = '#fffbeb'; }
                        }
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9;" class="crm-row">
                        <td style="padding:11px 16px;">
                            <div style="display:flex;align-items:center;gap:9px;">
                                @if($c->is_dropoff)
                                    <span title="Dropping off" style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#fef2f2;flex-shrink:0;">
                                        <svg style="width:11px;height:11px;color:#dc2626;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                                    </span>
                                @endif
                                <div>
                                    <a href="{{ route('crm.show', ['customerCode' => $c->customer_code, 'warehouse' => $warehouse]) }}"
                                       style="font-weight:600;color:#1e293b;text-decoration:none;">
                                        {{ $c->customer ?: $c->customer_code }}
                                    </a>
                                    <div style="font-size:0.75rem;color:#94a3b8;margin-top:1px;">
                                        {{ $c->customer_code }}
                                        @if($c->customer_type) &bull; {{ $c->customer_type }} @endif
                                        @if($c->key_account_id)
                                            &bull; <a href="{{ route('key-accounts.show', $c->key_account_id) }}" style="color:#6366f1;text-decoration:none;">Key Account</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:11px 16px;text-align:right;font-weight:600;color:#1e293b;">
                            £{{ number_format($c->current_total, 0) }}
                        </td>
                        <td style="padding:11px 16px;text-align:right;color:#64748b;">
                            {{ $c->prev_total > 0 ? '£' . number_format($c->prev_total, 0) : '—' }}
                        </td>
                        <td style="padding:11px 16px;text-align:right;">
                            @if($pct !== null && abs($pct) >= 1)
                                <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:600;color:{{ $pctColour }};background:{{ $pctBg }};">
                                    {{ $pct > 0 ? '+' : '' }}{{ number_format($pct, 0) }}%
                                </span>
                            @else
                                <span style="color:#cbd5e1;font-size:0.75rem;">—</span>
                            @endif
                        </td>
                        <td style="padding:11px 16px;">
                            @if($c->last_order)
                                <span style="color:{{ $daysSince > 180 ? '#dc2626' : ($daysSince > 90 ? '#d97706' : '#64748b') }};font-size:0.875rem;">
                                    {{ $c->last_order->format('d M Y') }}
                                </span>
                                <div style="font-size:0.75rem;color:#94a3b8;">{{ $c->last_order->diffForHumans() }}</div>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td style="padding:11px 16px;">
                            @if($c->expected_next)
                                <div style="display:inline-block;padding:3px 9px;border-radius:6px;background:{{ $nextBg !== 'transparent' ? $nextBg : 'transparent' }};">
                                    <span style="font-size:0.875rem;color:{{ $nextColour }};font-weight:{{ $c->is_overdue ? '600' : '400' }};">
                                        {{ $c->expected_next->format('d M Y') }}
                                    </span>
                                    <div style="font-size:0.75rem;color:{{ $nextColour }};opacity:0.8;">
                                        @if($c->is_overdue)
                                            {{ abs((int) now()->diffInDays($c->expected_next, false)) }}d overdue
                                        @else
                                            in {{ now()->diffInDays($c->expected_next) }}d
                                        @endif
                                        &bull; every ~{{ $c->avg_days }}d
                                    </div>
                                </div>
                            @else
                                <span style="color:#cbd5e1;font-size:0.75rem;">Not enough data</span>
                            @endif
                        </td>
                        <td style="padding:11px 16px;">
                            <a href="{{ route('crm.show', ['customerCode' => $c->customer_code, 'warehouse' => $warehouse]) }}"
                               style="font-size:0.75rem;color:#94a3b8;text-decoration:none;white-space:nowrap;">View &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:3rem;text-align:center;color:#94a3b8;">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div style="margin-top:0.875rem;font-size:0.75rem;color:#94a3b8;text-align:right;">
        {{ $customers->count() }} customer{{ $customers->count() !== 1 ? 's' : '' }}
        @if($warehouse) in {{ $warehouse }} @endif
        @if($filter) &bull; filtered: {{ $filter }} @endif
    </div>

</main>
<style>.crm-row:hover { background:#f8fafc; }</style>
</x-layout>
