<x-layout title="Stock Forecast — Lockie Portal">

<main style="max-width:1400px;margin:0 auto;padding:2rem 1.5rem;">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 4px;">Stock Forecast</h1>
            <p style="font-size:0.875rem;color:#64748b;margin:0;">
                Live stock levels, incoming orders, and reorder recommendations per warehouse.
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
            <span id="sync-status" style="font-size:0.8rem;color:#94a3b8;">
                @if($lastSynced)
                    Last synced {{ \Carbon\Carbon::parse($lastSynced)->diffForHumans() }}
                @else
                    Not yet synced
                @endif
            </span>
            <button id="sync-btn" onclick="runSync()"
                style="display:flex;align-items:center;gap:7px;padding:8px 16px;background:#0f172a;color:white;border:none;border-radius:8px;font-size:0.875rem;font-weight:600;cursor:pointer;transition:background 0.15s;"
                onmouseover="this.style.background='#1e293b'" onmouseout="this.style.background='#0f172a'">
                <svg id="sync-icon" style="width:15px;height:15px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
                Sync from Unleashed
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('stock-forecast.index') }}"
        style="background:white;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-bottom:1.25rem;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">

        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-size:0.75rem;font-weight:600;color:#64748b;margin-bottom:5px;">Warehouse</label>
            <select name="warehouse" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:7px;font-size:0.875rem;color:#1e293b;background:white;">
                <option value="">All warehouses</option>
                @foreach($warehouses as $code => $name)
                    <option value="{{ $code }}" {{ $warehouseFilter === $code ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div style="flex:1;min-width:160px;">
            <label style="display:block;font-size:0.75rem;font-weight:600;color:#64748b;margin-bottom:5px;">Supplier</label>
            <select name="supplier" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:7px;font-size:0.875rem;color:#1e293b;background:white;">
                <option value="">All suppliers</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s }}" {{ $supplierFilter === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>

        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:0.75rem;font-weight:600;color:#64748b;margin-bottom:5px;">Status</label>
            <select name="status" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:7px;font-size:0.875rem;color:#1e293b;background:white;">
                <option value="">All statuses</option>
                <option value="critical"   {{ $statusFilter === 'critical'   ? 'selected' : '' }}>🔴 Critical</option>
                <option value="order_now"  {{ $statusFilter === 'order_now'  ? 'selected' : '' }}>🟠 Order Now</option>
                <option value="order_soon" {{ $statusFilter === 'order_soon' ? 'selected' : '' }}>🟡 Order Soon</option>
                <option value="ok"         {{ $statusFilter === 'ok'         ? 'selected' : '' }}>🟢 OK</option>
            </select>
        </div>

        <div style="flex:2;min-width:200px;">
            <label style="display:block;font-size:0.75rem;font-weight:600;color:#64748b;margin-bottom:5px;">Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Product code or name…"
                style="width:100%;box-sizing:border-box;padding:7px 10px;border:1px solid #cbd5e1;border-radius:7px;font-size:0.875rem;color:#1e293b;">
        </div>

        <div style="display:flex;gap:8px;">
            <button type="submit"
                style="padding:7px 16px;background:#0369a1;color:white;border:none;border-radius:7px;font-size:0.875rem;font-weight:600;cursor:pointer;">
                Filter
            </button>
            @if($warehouseFilter || $supplierFilter || $statusFilter || $search)
            <a href="{{ route('stock-forecast.index') }}"
                style="padding:7px 14px;background:#f1f5f9;color:#475569;border-radius:7px;font-size:0.875rem;font-weight:600;text-decoration:none;">
                Reset
            </a>
            @endif
        </div>
    </form>

    {{-- Results summary --}}
    @if($total > 0)
    <p style="font-size:0.8rem;color:#94a3b8;margin-bottom:10px;">
        Showing {{ $rows->count() }} of {{ number_format($total) }} rows
        @if($lastPage > 1) — Page {{ $page }} of {{ $lastPage }}@endif
    </p>
    @endif

    {{-- Table --}}
    <div style="background:white;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table style="width:100%;border-collapse:collapse;font-size:0.8rem;min-width:960px;">
            <thead>
                <tr style="border-bottom:1px solid #e2e8f0;background:#f8fafc;">
                    <th style="text-align:left;padding:10px 14px;font-weight:600;color:#64748b;white-space:nowrap;">Code</th>
                    <th style="text-align:left;padding:10px 14px;font-weight:600;color:#64748b;">Product</th>
                    <th style="text-align:left;padding:10px 14px;font-weight:600;color:#64748b;white-space:nowrap;">Supplier</th>
                    <th style="text-align:left;padding:10px 14px;font-weight:600;color:#64748b;white-space:nowrap;">Warehouse</th>
                    <th style="text-align:right;padding:10px 14px;font-weight:600;color:#64748b;white-space:nowrap;">On Hand</th>
                    <th style="text-align:right;padding:10px 14px;font-weight:600;color:#64748b;white-space:nowrap;">Incoming</th>
                    <th style="text-align:right;padding:10px 14px;font-weight:600;color:#64748b;white-space:nowrap;">Avg Wk</th>
                    <th style="text-align:right;padding:10px 14px;font-weight:600;color:#64748b;white-space:nowrap;">Wks Left</th>
                    <th style="text-align:left;padding:10px 14px;font-weight:600;color:#64748b;white-space:nowrap;">Reorder By</th>
                    <th style="text-align:center;padding:10px 14px;font-weight:600;color:#64748b;white-space:nowrap;">Status</th>
                    <th style="text-align:center;padding:10px 14px;font-weight:600;color:#64748b;white-space:nowrap;">Lead (wks)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $line)
                @php
                    $statusColors = [
                        'critical'   => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => '🔴 Critical'],
                        'order_now'  => ['bg' => '#fff7ed', 'color' => '#ea580c', 'label' => '🟠 Order Now'],
                        'order_soon' => ['bg' => '#fefce8', 'color' => '#ca8a04', 'label' => '🟡 Order Soon'],
                        'ok'         => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'label' => '🟢 OK'],
                    ];
                    $sc = $statusColors[$line->computed_status] ?? $statusColors['ok'];
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;" class="forecast-row">
                    <td style="padding:9px 14px;font-family:monospace;font-size:0.78rem;color:#334155;white-space:nowrap;">
                        {{ $line->product->product_code }}
                    </td>
                    <td style="padding:9px 14px;color:#1e293b;max-width:220px;">
                        <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $line->product->product_name }}">
                            {{ $line->product->product_name }}
                        </span>
                    </td>
                    <td style="padding:9px 14px;color:#64748b;white-space:nowrap;max-width:140px;">
                        <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $line->product->supplier_name }}">
                            {{ $line->product->supplier_name ?? '—' }}
                        </span>
                    </td>
                    <td style="padding:9px 14px;color:#475569;white-space:nowrap;">{{ $line->warehouse_name }}</td>
                    <td style="padding:9px 14px;text-align:right;font-variant-numeric:tabular-nums;color:#1e293b;white-space:nowrap;">
                        {{ number_format($line->qty_on_hand, 0) }}
                    </td>
                    <td style="padding:9px 14px;text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap;">
                        @if($line->qty_incoming > 0)
                            <span style="color:#0369a1;">+{{ number_format($line->qty_incoming, 0) }}</span>
                            @if($line->po_expected_date)
                                <br><span style="font-size:0.72rem;color:#94a3b8;">{{ $line->po_expected_date->format('d M') }}</span>
                            @endif
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td style="padding:9px 14px;text-align:right;font-variant-numeric:tabular-nums;color:#475569;white-space:nowrap;">
                        @if($line->computed_avg_weekly > 0)
                            {{ number_format($line->computed_avg_weekly, 1) }}
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td style="padding:9px 14px;text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap;">
                        @if($line->computed_weeks_left === 999)
                            <span style="color:#16a34a;">∞</span>
                        @else
                            <span style="font-weight:600;color:{{ $sc['color'] }};">{{ $line->computed_weeks_left }}</span>
                        @endif
                    </td>
                    <td style="padding:9px 14px;white-space:nowrap;color:#475569;">
                        @if($line->computed_weeks_left === 999)
                            <span style="color:#cbd5e1;">—</span>
                        @else
                            {{ $line->computed_reorder_by->format('d M Y') }}
                        @endif
                    </td>
                    <td style="padding:9px 14px;text-align:center;white-space:nowrap;">
                        <span style="display:inline-block;padding:2px 8px;border-radius:9999px;font-size:0.72rem;font-weight:600;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};">
                            {{ $sc['label'] }}
                        </span>
                    </td>
                    <td style="padding:9px 14px;text-align:center;">
                        <input type="number" min="1" max="52"
                            value="{{ $line->lead_time_override ?? '' }}"
                            placeholder="{{ $line->computed_lead_time }}"
                            data-line-id="{{ $line->id }}"
                            title="Leave blank to use supplier default ({{ $line->computed_lead_time }} wks)"
                            style="width:56px;padding:4px 6px;border:1px solid #e2e8f0;border-radius:6px;font-size:0.8rem;text-align:center;color:#334155;"
                            onchange="saveLeadTime(this)">
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" style="padding:48px;text-align:center;color:#94a3b8;">
                        @if($total === 0 && !$warehouseFilter && !$supplierFilter && !$statusFilter && $search === '')
                            No data yet — click <strong>Sync from Unleashed</strong> to pull live stock figures.
                        @else
                            No rows match the current filters.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($lastPage > 1)
    <div style="display:flex;justify-content:center;gap:8px;margin-top:1rem;flex-wrap:wrap;">
        @for($p = 1; $p <= $lastPage; $p++)
            <a href="{{ request()->fullUrlWithQuery(['page' => $p]) }}"
                style="padding:5px 12px;border-radius:6px;font-size:0.8rem;font-weight:500;text-decoration:none;
                    {{ $p === $page ? 'background:#0f172a;color:white;' : 'background:#f1f5f9;color:#475569;' }}">
                {{ $p }}
            </a>
        @endfor
    </div>
    @endif

</main>

<script>
function runSync() {
    const btn  = document.getElementById('sync-btn');
    const icon = document.getElementById('sync-icon');
    const status = document.getElementById('sync-status');

    btn.disabled = true;
    btn.style.opacity = '0.6';
    btn.style.cursor  = 'not-allowed';
    status.textContent = 'Syncing…';

    icon.style.animation = 'spin 1s linear infinite';

    fetch('{{ route('stock-forecast.sync') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    })
    .then(r => {
        if (!r.ok && r.status !== 500) {
            throw new Error('HTTP ' + r.status);
        }
        return r.json();
    })
    .then(data => {
        if (data.success) {
            status.textContent = 'Synced just now (' + data.row_count + ' rows)';
            setTimeout(() => window.location.reload(), 800);
        } else {
            status.textContent = 'Sync failed: ' + (data.error ?? 'Unknown error');
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor  = 'pointer';
        }
        icon.style.animation = '';
    })
    .catch(err => {
        status.textContent = 'Sync failed: ' + (err.message || 'check connection');
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor  = 'pointer';
        icon.style.animation = '';
    });
}

function saveLeadTime(input) {
    const lineId = input.dataset.lineId;
    const value  = input.value.trim();

    fetch(`/stock-forecast/lines/${lineId}/lead-time`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ lead_time_override: value === '' ? null : parseInt(value, 10) }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            input.style.borderColor = '#16a34a';
            setTimeout(() => { input.style.borderColor = '#e2e8f0'; }, 1500);
        }
    });
}
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

</x-layout>
