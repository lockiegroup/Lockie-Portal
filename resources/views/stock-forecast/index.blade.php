<x-layout title="Stock Forecast — Lockie Portal">

<main class="max-w-[1400px] mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 mb-1">Stock Forecast</h1>
            <p class="text-sm text-slate-500 m-0">
                Live stock levels, incoming orders, and reorder recommendations per warehouse.
            </p>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
            <span id="sync-status" class="text-[0.8rem] text-slate-400">
                @if($lastSynced)
                    Last synced {{ \Carbon\Carbon::parse($lastSynced)->diffForHumans() }}
                @else
                    Not yet synced
                @endif
            </span>
            <button id="sync-btn" onclick="runSync()"
                class="flex items-center gap-1.5 px-4 py-2 bg-slate-900 text-white border-none rounded-lg text-sm font-semibold cursor-pointer hover:bg-slate-800 transition-colors">
                <svg id="sync-icon" class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
                Sync from Unleashed
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('stock-forecast.index') }}"
        class="bg-white border border-slate-200 rounded-xl px-4 py-3.5 mb-5 flex flex-wrap gap-2.5 items-end">

        <div class="flex-1 min-w-[160px]">
            <label class="block text-[0.75rem] font-semibold text-slate-500 mb-1.5">Warehouse</label>
            <select name="warehouse" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm text-slate-800 bg-white">
                <option value="">All warehouses</option>
                @foreach($warehouses as $code => $name)
                    <option value="{{ $code }}" {{ $warehouseFilter === $code ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-[160px]">
            <label class="block text-[0.75rem] font-semibold text-slate-500 mb-1.5">Supplier</label>
            <select name="supplier" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm text-slate-800 bg-white">
                <option value="">All suppliers</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s }}" {{ $supplierFilter === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-[140px]">
            <label class="block text-[0.75rem] font-semibold text-slate-500 mb-1.5">Status</label>
            <select name="status" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm text-slate-800 bg-white">
                <option value="">All statuses</option>
                <option value="critical"   {{ $statusFilter === 'critical'   ? 'selected' : '' }}>🔴 Critical</option>
                <option value="order_now"  {{ $statusFilter === 'order_now'  ? 'selected' : '' }}>🟠 Order Now</option>
                <option value="order_soon" {{ $statusFilter === 'order_soon' ? 'selected' : '' }}>🟡 Order Soon</option>
                <option value="ok"         {{ $statusFilter === 'ok'         ? 'selected' : '' }}>🟢 OK</option>
            </select>
        </div>

        <div class="flex-[2] min-w-[200px]">
            <label class="block text-[0.75rem] font-semibold text-slate-500 mb-1.5">Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Product code or name…"
                class="w-full box-border px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm text-slate-800">
        </div>

        <div class="flex gap-2">
            <button type="submit"
                class="px-4 py-1.5 bg-sky-700 text-white border-none rounded-lg text-sm font-semibold cursor-pointer hover:bg-sky-800">
                Filter
            </button>
            @if($warehouseFilter || $supplierFilter || $statusFilter || $search)
            <a href="{{ route('stock-forecast.index') }}"
                class="px-3.5 py-1.5 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold no-underline hover:bg-slate-200">
                Reset
            </a>
            @endif
        </div>
    </form>

    {{-- Results summary --}}
    @if($total > 0)
    <p class="text-[0.8rem] text-slate-400 mb-2.5">
        Showing {{ $rows->count() }} of {{ number_format($total) }} rows
        @if($lastPage > 1) — Page {{ $page }} of {{ $lastPage }}@endif
    </p>
    @endif

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full border-collapse text-[0.8rem]" style="min-width:960px;">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-3.5 py-2.5 font-semibold text-slate-500 whitespace-nowrap">Code</th>
                    <th class="text-left px-3.5 py-2.5 font-semibold text-slate-500">Product</th>
                    <th class="text-left px-3.5 py-2.5 font-semibold text-slate-500 whitespace-nowrap">Supplier</th>
                    <th class="text-left px-3.5 py-2.5 font-semibold text-slate-500 whitespace-nowrap">Warehouse</th>
                    <th class="text-right px-3.5 py-2.5 font-semibold text-slate-500 whitespace-nowrap">On Hand</th>
                    <th class="text-right px-3.5 py-2.5 font-semibold text-slate-500 whitespace-nowrap">Incoming</th>
                    <th class="text-right px-3.5 py-2.5 font-semibold text-slate-500 whitespace-nowrap">Avg Wk</th>
                    <th class="text-right px-3.5 py-2.5 font-semibold text-slate-500 whitespace-nowrap">Wks Left</th>
                    <th class="text-left px-3.5 py-2.5 font-semibold text-slate-500 whitespace-nowrap">Reorder By</th>
                    <th class="text-center px-3.5 py-2.5 font-semibold text-slate-500 whitespace-nowrap">Status</th>
                    <th class="text-center px-3.5 py-2.5 font-semibold text-slate-500 whitespace-nowrap">Lead (wks)</th>
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
                <tr class="border-b border-slate-100 forecast-row hover:bg-slate-50">
                    <td class="px-3.5 py-2 font-mono text-[0.78rem] text-slate-700 whitespace-nowrap">
                        {{ $line->product->product_code }}
                    </td>
                    <td class="px-3.5 py-2 text-slate-800 max-w-[220px]">
                        <span class="block overflow-hidden text-ellipsis whitespace-nowrap" title="{{ $line->product->product_name }}">
                            {{ $line->product->product_name }}
                        </span>
                    </td>
                    <td class="px-3.5 py-2 text-slate-500 whitespace-nowrap max-w-[140px]">
                        <span class="block overflow-hidden text-ellipsis whitespace-nowrap" title="{{ $line->product->supplier_name }}">
                            {{ $line->product->supplier_name ?? '—' }}
                        </span>
                    </td>
                    <td class="px-3.5 py-2 text-slate-600 whitespace-nowrap">{{ $line->warehouse_name }}</td>
                    <td class="px-3.5 py-2 text-right tabular-nums text-slate-800 whitespace-nowrap">
                        {{ number_format($line->qty_on_hand, 0) }}
                    </td>
                    <td class="px-3.5 py-2 text-right tabular-nums whitespace-nowrap">
                        @if($line->qty_incoming > 0)
                            <span class="text-sky-700">+{{ number_format($line->qty_incoming, 0) }}</span>
                            @if($line->po_expected_date)
                                <br><span class="text-[0.72rem] text-slate-400">{{ $line->po_expected_date->format('d M') }}</span>
                            @endif
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-3.5 py-2 text-right tabular-nums text-slate-600 whitespace-nowrap">
                        @if($line->computed_avg_weekly > 0)
                            {{ number_format($line->computed_avg_weekly, 1) }}
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-3.5 py-2 text-right tabular-nums whitespace-nowrap">
                        @if($line->computed_weeks_left === 999)
                            <span class="text-green-600">∞</span>
                        @else
                            <span class="font-semibold" style="color:{{ $sc['color'] }};">{{ $line->computed_weeks_left }}</span>
                        @endif
                    </td>
                    <td class="px-3.5 py-2 whitespace-nowrap text-slate-600">
                        @if($line->computed_weeks_left === 999)
                            <span class="text-slate-300">—</span>
                        @else
                            {{ $line->computed_reorder_by->format('d M Y') }}
                        @endif
                    </td>
                    <td class="px-3.5 py-2 text-center whitespace-nowrap">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[0.72rem] font-semibold" style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }};">
                            {{ $sc['label'] }}
                        </span>
                    </td>
                    <td class="px-3.5 py-2 text-center">
                        <input type="number" min="1" max="52"
                            value="{{ $line->lead_time_override ?? '' }}"
                            placeholder="{{ $line->computed_lead_time }}"
                            data-line-id="{{ $line->id }}"
                            title="Leave blank to use supplier default ({{ $line->computed_lead_time }} wks)"
                            class="w-14 px-1.5 py-1 border border-slate-200 rounded-md text-[0.8rem] text-center text-slate-700"
                            onchange="saveLeadTime(this)">
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="px-4 py-12 text-center text-slate-400">
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
    <div class="flex justify-center gap-2 mt-4 flex-wrap">
        @for($p = 1; $p <= $lastPage; $p++)
            <a href="{{ request()->fullUrlWithQuery(['page' => $p]) }}"
                class="px-3 py-1 rounded-md text-[0.8rem] font-medium no-underline {{ $p === $page ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
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
