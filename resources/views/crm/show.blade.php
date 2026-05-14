<x-layout title="{{ $customer }} — CRM — Lockie Portal">
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    {{-- Back --}}
    <div style="margin-bottom:1.5rem;">
        <a href="{{ route('crm.index', ['warehouse' => $warehouse]) }}"
           style="font-size:0.875rem;color:#94a3b8;text-decoration:none;">&#8592; Customer Insights</a>
    </div>

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:2rem;">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $customer ?: $customerCode }}</h1>
            <p style="font-size:0.875rem;color:#94a3b8;margin-top:3px;">
                {{ $customerCode }}
                @if($customerType) &bull; {{ $customerType }} @endif
                @if($keyAccount)
                    &bull; <a href="{{ route('key-accounts.show', $keyAccount) }}" style="color:#6366f1;text-decoration:none;">View Key Account &rarr;</a>
                @endif
            </p>
        </div>

        {{-- Warehouse filter --}}
        @if($warehouses->count() > 1)
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <select name="warehouse" onchange="this.form.submit()"
                style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#1e293b;background:#fff;">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $w)
                    <option value="{{ $w }}" {{ $warehouse === $w ? 'selected' : '' }}>{{ $w }}</option>
                @endforeach
            </select>
        </form>
        @endif
    </div>

    {{-- KPI strip --}}
    @php
        $pct = $totalPrev12 > 0 ? (($total12m - $totalPrev12) / $totalPrev12) * 100 : null;
        $pctColour = ($pct !== null && $pct >= 5) ? '#16a34a' : (($pct !== null && $pct <= -5) ? '#dc2626' : '#64748b');
        $lastOrderDate = $lastOrder ? \Carbon\Carbon::parse($lastOrder) : null;
        $daysSince = $lastOrderDate ? $lastOrderDate->diffInDays(now()) : null;
    @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:2.5rem;">

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Last 12 Months</p>
            <p style="font-size:1.625rem;font-weight:700;color:#1e293b;line-height:1;">£{{ number_format($total12m, 0) }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Prev 12 Months</p>
            <p style="font-size:1.625rem;font-weight:700;color:#64748b;line-height:1;">
                {{ $totalPrev12 > 0 ? '£' . number_format($totalPrev12, 0) : '—' }}
            </p>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Year-on-Year</p>
            <p style="font-size:1.625rem;font-weight:700;color:{{ $pctColour }};line-height:1;">
                @if($pct !== null)
                    {{ $pct > 0 ? '+' : '' }}{{ number_format($pct, 1) }}%
                @else
                    —
                @endif
            </p>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Last Order</p>
            @if($lastOrderDate)
                <p style="font-size:1rem;font-weight:600;color:{{ $daysSince > 180 ? '#dc2626' : ($daysSince > 90 ? '#d97706' : '#1e293b') }};line-height:1.3;">
                    {{ $lastOrderDate->format('d M Y') }}
                </p>
                <p style="font-size:0.75rem;color:#94a3b8;margin-top:2px;">{{ $lastOrderDate->diffForHumans() }}</p>
            @else
                <p style="font-size:1rem;color:#94a3b8;">—</p>
            @endif
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            @php
                $nextDaysUntil = $expectedNext ? now()->diffInDays($expectedNext, false) : null;
                $nextColour = ($nextDaysUntil !== null && $nextDaysUntil < 0) ? '#dc2626'
                    : (($nextDaysUntil !== null && $nextDaysUntil <= 14) ? '#d97706' : '#1e293b');
            @endphp
            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Expected Next Order</p>
            @if($expectedNext)
                <p style="font-size:1rem;font-weight:600;color:{{ $nextColour }};line-height:1.3;">
                    {{ $expectedNext->format('d M Y') }}
                </p>
                <p style="font-size:0.75rem;color:#94a3b8;margin-top:2px;">
                    @if($nextDaysUntil < 0)
                        {{ abs((int) $nextDaysUntil) }}d overdue
                    @else
                        in {{ (int) $nextDaysUntil }}d
                    @endif
                    &bull; orders every ~{{ $avgDays }}d
                </p>
            @else
                <p style="font-size:0.875rem;color:#94a3b8;">Not enough data</p>
            @endif
        </div>

    </div>

    {{-- Yearly breakdown --}}
    <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.875rem;">Annual Spend</h2>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:2.5rem;">
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th style="padding:9px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Year</th>
                    <th style="padding:9px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Q1</th>
                    <th style="padding:9px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Q2</th>
                    <th style="padding:9px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Q3</th>
                    <th style="padding:9px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Q4</th>
                    <th style="padding:9px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byYear as $year => $qs)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px 16px;font-weight:600;color:#1e293b;">{{ $year }}</td>
                        @foreach(['q1','q2','q3','q4'] as $q)
                            <td style="padding:10px 16px;text-align:right;color:{{ isset($qs[$q]) && $qs[$q] > 0 ? '#334155' : '#cbd5e1' }};">
                                {{ isset($qs[$q]) && $qs[$q] > 0 ? '£' . number_format($qs[$q], 0) : '—' }}
                            </td>
                        @endforeach
                        <td style="padding:10px 16px;text-align:right;font-weight:600;color:#1e293b;">
                            £{{ number_format($qs['total'] ?? 0, 0) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:2.5rem;">

        {{-- Top products --}}
        <div>
            <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.875rem;">Top Products</h2>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                    <tbody>
                        @foreach($topProducts as $p)
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:10px 14px;">
                                    <p style="font-weight:600;color:#1e293b;">{{ $p['product_code'] }}</p>
                                    @if($p['description'] && $p['description'] !== $p['product_code'])
                                        <p style="font-size:0.75rem;color:#94a3b8;">{{ $p['description'] }}</p>
                                    @endif
                                </td>
                                <td style="padding:10px 14px;text-align:right;font-weight:600;color:#334155;">
                                    £{{ number_format($p['total'], 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent orders --}}
        <div>
            <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.875rem;">Recent Orders</h2>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                    <tbody>
                        @foreach($recentOrders as $o)
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:10px 14px;">
                                    <p style="font-weight:600;color:#1e293b;">{{ $o['order_no'] ?: '—' }}</p>
                                    <p style="font-size:0.75rem;color:#94a3b8;">
                                        {{ \Carbon\Carbon::parse($o['date'])->format('d M Y') }}
                                        @if($o['warehouse']) &bull; {{ $o['warehouse'] }} @endif
                                    </p>
                                </td>
                                <td style="padding:10px 14px;text-align:right;font-weight:600;color:#334155;">
                                    £{{ number_format($o['total'], 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</main>
</x-layout>
