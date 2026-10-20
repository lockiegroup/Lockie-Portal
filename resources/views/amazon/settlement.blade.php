<x-layout title="Settlement {{ $settlement->settlement_id }} — Amazon">

<style>
.amz-table { border-collapse:collapse; width:100%; font-size:0.8125rem; }
.amz-table th { background:#f8fafc; padding:0.5rem 0.75rem; font-weight:700; color:#334155; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
.amz-table td { padding:0.5rem 0.75rem; border-bottom:1px solid #f1f5f9; color:#334155; }
.amz-table tr:hover td { background:#f8fafc; }
.amz-num { text-align:right; font-variant-numeric:tabular-nums; }
</style>

<main class="max-w-[1400px] mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="mb-5">
        <a href="{{ route('amazon.index') }}" class="text-[0.8rem] text-slate-500 no-underline inline-flex items-center gap-1 mb-2 hover:text-slate-700">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Amazon & Xero
        </a>
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-[1.25rem] font-bold text-slate-800 m-0 font-mono">{{ $settlement->settlement_id }}</h1>
                <p class="text-sm text-slate-500 mt-1 m-0">
                    {{ $settlement->start_date?->format('d M Y') }} → {{ $settlement->end_date?->format('d M Y') }}
                    &nbsp;·&nbsp; Deposit: <strong>£{{ number_format($settlement->deposit_amount, 2) }}</strong>
                    &nbsp;·&nbsp; Status: <span class="font-semibold">{{ ucfirst($settlement->status) }}</span>
                </p>
            </div>
            <div class="flex gap-2 flex-wrap items-center">
                <button id="lookup-btn" onclick="lookupUnleashed()"
                    class="bg-sky-100 text-sky-700 border-none rounded-lg px-4 py-2 text-sm font-semibold cursor-pointer hover:bg-sky-200">
                    ⟳ Lookup SO Numbers
                </button>
                <button id="reprocess-btn" onclick="reprocess()"
                    class="bg-yellow-100 text-yellow-800 border-none rounded-lg px-4 py-2 text-sm font-semibold cursor-pointer hover:bg-yellow-200">
                    ↺ Recalculate
                </button>
                <a href="{{ route('amazon.settlement.csv', $settlement) }}"
                    class="bg-slate-800 text-white rounded-lg px-4 py-2 text-sm font-semibold no-underline inline-block hover:bg-slate-700">
                    ↓ Download CSV
                </a>
                @if($settlement->status === 'pending')
                <button onclick="postToXero()"
                    class="bg-teal-500 text-white border-none rounded-lg px-4 py-2 text-sm font-semibold cursor-pointer hover:bg-teal-600">
                    Post to Xero
                </button>
                @endif
            </div>
        </div>
    </div>

    <div id="status-msg" style="display:none;" class="mb-4 px-4 py-3 rounded-lg text-sm"></div>

    {{-- Orders table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-3.5 border-b border-slate-100">
            <h2 class="text-[0.9375rem] font-bold text-slate-800 m-0">Orders ({{ count($orders) }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="amz-table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Amazon Order ID</th>
                        <th style="text-align:left;">Unleashed Order No</th>
                        <th style="text-align:left;">Date</th>
                        <th class="amz-num">Amount £</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($orders as $order)
                <tr>
                    <td class="font-mono text-[0.75rem] text-slate-600">{{ $order['amazon_order_id'] }}</td>
                    <td class="text-[0.8125rem]">{{ $order['unleashed_order_no'] ?? '—' }}</td>
                    <td class="text-slate-500">{{ $order['date'] }}</td>
                    <td class="amz-num">£{{ number_format($order['computed_amount'], 2) }}</td>
                </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold bg-slate-50 border-t-2 border-slate-200">
                        <td colspan="3" class="px-3 py-2 text-slate-500 text-[0.75rem]">Total Orders</td>
                        <td class="amz-num">£{{ number_format(array_sum(array_column(array_values($orders), 'computed_amount')), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Fee summary --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100">
            <h2 class="text-[0.9375rem] font-bold text-slate-800 m-0">Fee Summary</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="amz-table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Account Code</th>
                        <th class="amz-num">Gross (incl. VAT)</th>
                        <th class="amz-num">Lines</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($feeSummary as $fee)
                <tr>
                    <td>{{ $fee['account_code'] }}</td>
                    <td class="amz-num {{ $fee['gross'] < 0 ? 'text-red-600' : '' }}">£{{ number_format($fee['gross'], 2) }}</td>
                    <td class="amz-num text-slate-400">{{ $fee['count'] }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
const csrfToken      = '{{ csrf_token() }}';
const settlementId   = '{{ $settlement->settlement_id }}';
const reprocessUrl   = `/amazon/settlements/${settlementId}/reprocess`;
const lookupUrl      = `/amazon/settlements/${settlementId}/lookup-unleashed`;

async function lookupUnleashed() {
    const btn = document.getElementById('lookup-btn');
    const msg = document.getElementById('status-msg');
    btn.disabled = true;
    btn.textContent = 'Looking up…';

    try {
        const res  = await fetch(lookupUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.ok) {
            msg.style.cssText = 'display:block;background:#dcfce7;border:1px solid #86efac;color:#166534;padding:0.75rem 1rem;border-radius:0.5rem;font-size:0.875rem;margin-bottom:1rem;';
            msg.textContent = `Matched ${data.matched} order(s) to Unleashed SO numbers.`;
            if (data.matched > 0) location.reload();
        } else {
            msg.style.cssText = 'display:block;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:0.75rem 1rem;border-radius:0.5rem;font-size:0.875rem;margin-bottom:1rem;';
            msg.textContent = 'Lookup failed: ' + data.message;
        }
    } catch (e) {
        msg.style.cssText = 'display:block;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:0.75rem 1rem;border-radius:0.5rem;font-size:0.875rem;margin-bottom:1rem;';
        msg.textContent = 'Request failed: ' + e.message;
    }
    btn.disabled = false;
    btn.textContent = '⟳ Lookup SO Numbers';
}

async function reprocess() {
    if (!confirm('Recalculate all order amounts from the original Amazon data? This will reload the page when done.')) return;
    const btn = document.getElementById('reprocess-btn');
    const msg = document.getElementById('status-msg');
    btn.disabled = true;
    btn.textContent = 'Recalculating…';

    try {
        const res  = await fetch(reprocessUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.ok) {
            location.reload();
        } else {
            msg.style.cssText = 'display:block;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:0.75rem 1rem;border-radius:0.5rem;font-size:0.875rem;margin-bottom:1rem;';
            msg.textContent = 'Recalculate failed: ' + data.message;
        }
    } catch (e) {
        msg.style.cssText = 'display:block;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:0.75rem 1rem;border-radius:0.5rem;font-size:0.875rem;margin-bottom:1rem;';
        msg.textContent = 'Request failed: ' + e.message;
    }
    btn.disabled = false;
    btn.textContent = '↺ Recalculate';
}

async function postToXero() {
    if (!confirm('Post this settlement to Xero bank feed?')) return;
    const res  = await fetch(`/amazon/xero/post/${settlementId}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (data.success) {
        alert('Posted to Xero successfully.');
        location.reload();
    } else {
        alert('Xero post failed: ' + data.message);
    }
}
</script>

</x-layout>
