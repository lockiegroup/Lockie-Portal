<x-layout title="Settlement {{ $settlement->settlement_id }} — Amazon">

<style>
.amz-table { border-collapse:collapse; width:100%; font-size:0.8125rem; }
.amz-table th { background:#f8fafc; padding:0.5rem 0.75rem; font-weight:700; color:#334155; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
.amz-table td { padding:0.5rem 0.75rem; border-bottom:1px solid #f1f5f9; color:#334155; }
.amz-table tr:hover td { background:#f8fafc; }
.amz-num { text-align:right; font-variant-numeric:tabular-nums; }
</style>

<main style="max-width:1400px;margin:0 auto;padding:2rem 1.5rem;">

    {{-- Header --}}
    <div style="margin-bottom:1.25rem;">
        <a href="{{ route('amazon.index') }}" style="font-size:0.8rem;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:8px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Amazon & Xero
        </a>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-size:1.25rem;font-weight:700;color:#1e293b;margin:0;font-family:monospace;">{{ $settlement->settlement_id }}</h1>
                <p style="font-size:0.875rem;color:#64748b;margin:0.25rem 0 0;">
                    {{ $settlement->start_date?->format('d M Y') }} → {{ $settlement->end_date?->format('d M Y') }}
                    &nbsp;·&nbsp; Deposit: <strong>£{{ number_format($settlement->deposit_amount, 2) }}</strong>
                    &nbsp;·&nbsp; Status: <span style="font-weight:600;">{{ ucfirst($settlement->status) }}</span>
                </p>
            </div>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                <button id="lookup-btn" onclick="lookupUnleashed()"
                    style="background:#e0f2fe;color:#0369a1;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                    ⟳ Lookup SO Numbers
                </button>
                <button id="reprocess-btn" onclick="reprocess()"
                    style="background:#fef9c3;color:#854d0e;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                    ↺ Recalculate
                </button>
<a href="{{ route('amazon.settlement.csv', $settlement) }}"
                    style="background:#1e293b;color:#fff;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;text-decoration:none;display:inline-block;">
                    ↓ Download CSV
                </a>
                @if($settlement->status === 'pending')
                <button onclick="postToXero()"
                    style="background:#00b9a5;color:#fff;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                    Post to Xero
                </button>
                @endif
            </div>
        </div>
    </div>

    <div id="status-msg" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:0.5rem;font-size:0.875rem;"></div>

    {{-- Orders table --}}
    <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:1.5rem;">
        <div style="padding:0.875rem 1.25rem;border-bottom:1px solid #f1f5f9;">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0;">Orders ({{ count($orders) }})</h2>
        </div>
        <div style="overflow-x:auto;">
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
                    <td style="font-family:monospace;font-size:0.75rem;color:#475569;">{{ $order['amazon_order_id'] }}</td>
                    <td style="font-size:0.8125rem;">{{ $order['unleashed_order_no'] ?? '—' }}</td>
                    <td style="color:#64748b;">{{ $order['date'] }}</td>
                    <td class="amz-num">£{{ number_format($order['computed_amount'], 2) }}</td>
                </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr style="font-weight:700;background:#f8fafc;border-top:2px solid #e2e8f0;">
                        <td colspan="3" style="padding:0.5rem 0.75rem;color:#64748b;font-size:0.75rem;">Total Orders</td>
                        <td class="amz-num">£{{ number_format(array_sum(array_column(array_values($orders), 'computed_amount')), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Fee summary --}}
    <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);overflow:hidden;">
        <div style="padding:0.875rem 1.25rem;border-bottom:1px solid #f1f5f9;">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0;">Fee Summary</h2>
        </div>
        <div style="overflow-x:auto;">
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
                    <td class="amz-num" style="{{ $fee['gross'] < 0 ? 'color:#dc2626;' : '' }}">£{{ number_format($fee['gross'], 2) }}</td>
                    <td class="amz-num" style="color:#94a3b8;">{{ $fee['count'] }}</td>
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
