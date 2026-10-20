<x-layout title="Amazon Profit Report — Lockie Portal">

<main class="max-w-[1400px] mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 m-0">Amazon Profit Report</h1>
            <p class="text-sm text-slate-500 mt-1 m-0">By product type and fulfilment channel.</p>
        </div>
        <a href="{{ route('amazon.index') }}" class="bg-slate-100 text-slate-600 rounded-lg px-4 py-2 text-sm font-semibold no-underline hover:bg-slate-200">
            ← Back
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm px-5 py-4 mb-5 flex items-center flex-wrap gap-4">
        <div class="flex items-center gap-2">
            <label class="text-sm text-slate-500 font-medium">From:</label>
            <input type="date" id="filter-start" class="border border-slate-200 rounded-md px-2 py-1 text-sm text-slate-700">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-slate-500 font-medium">To:</label>
            <input type="date" id="filter-end" class="border border-slate-200 rounded-md px-2 py-1 text-sm text-slate-700">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-slate-500 font-medium">Channel:</label>
            <select id="filter-channel" class="border border-slate-200 rounded-md px-2 py-1 text-sm text-slate-700">
                <option value="">FBM + FBA</option>
                <option value="FBM">FBM only</option>
                <option value="FBA">FBA only</option>
            </select>
        </div>
        <button onclick="loadReport()" class="bg-slate-800 text-white border-none rounded-lg px-3.5 py-1.5 text-sm cursor-pointer font-semibold hover:bg-slate-700">
            Apply
        </button>
        <div class="flex-1"></div>
        <button onclick="clearFilters()" class="bg-slate-100 text-slate-600 border-none rounded-md px-3.5 py-1.5 text-[0.8rem] cursor-pointer font-medium hover:bg-slate-200">
            Clear
        </button>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 mb-6" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
        <div class="bg-white rounded-xl shadow-sm px-5 py-5">
            <p class="text-[0.75rem] font-semibold text-slate-500 uppercase tracking-wide mb-2 m-0">Revenue</p>
            <p id="card-revenue" class="text-2xl font-bold text-slate-800 m-0">—</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm px-5 py-5">
            <p class="text-[0.75rem] font-semibold text-slate-500 uppercase tracking-wide mb-2 m-0">Gross Profit</p>
            <p id="card-profit" class="text-2xl font-bold text-slate-800 m-0">—</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm px-5 py-5">
            <p class="text-[0.75rem] font-semibold text-slate-500 uppercase tracking-wide mb-2 m-0">Margin %</p>
            <p id="card-margin" class="text-2xl font-bold text-slate-800 m-0">—</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm px-5 py-5">
            <p class="text-[0.75rem] font-semibold text-slate-500 uppercase tracking-wide mb-2 m-0">Ad Spend (net)</p>
            <p id="card-adspend" class="text-2xl font-bold text-slate-800 m-0">—</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm px-5 py-5">
            <p class="text-[0.75rem] font-semibold text-slate-500 uppercase tracking-wide mb-2 m-0">ROAS</p>
            <p id="card-roas" class="text-2xl font-bold text-slate-800 m-0">—</p>
        </div>
    </div>

    {{-- Breakdown Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-800 m-0">By Product Type</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="border-collapse w-full text-[0.8125rem]">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Product Type</th>
                        <th class="text-center px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Channel</th>
                        <th class="text-right px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Revenue</th>
                        <th class="text-right px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Returns</th>
                        <th class="text-right px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Referral Fees</th>
                        <th class="text-right px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">FBA Fees</th>
                        <th class="text-right px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Ad Spend</th>
                        <th class="text-right px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Gross Profit</th>
                        <th class="text-right px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Margin %</th>
                    </tr>
                </thead>
                <tbody id="profit-body">
                    <tr><td colspan="9" class="text-center px-4 py-8 text-slate-400 text-sm">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
const fmt = n => '£' + parseFloat(n).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const pct = n => parseFloat(n).toFixed(1) + '%';

async function loadReport() {
    const start   = document.getElementById('filter-start').value;
    const end     = document.getElementById('filter-end').value;
    const channel = document.getElementById('filter-channel').value;
    const tbody   = document.getElementById('profit-body');

    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:#94a3b8;">Loading…</td></tr>';

    const params = new URLSearchParams();
    if (start)   params.set('start',   start);
    if (end)     params.set('end',     end);
    if (channel) params.set('channel', channel);

    const res  = await fetch('{{ route('amazon.profit') }}?' + params);
    const data = await res.json();

    const s = data.summary;
    document.getElementById('card-revenue').textContent  = fmt(s.gross_sales);
    document.getElementById('card-profit').textContent   = fmt(s.gross_profit);
    document.getElementById('card-margin').textContent   = pct(s.margin_pct);
    document.getElementById('card-adspend').textContent  = fmt(s.ad_spend_net);
    document.getElementById('card-roas').textContent     = parseFloat(s.roas).toFixed(2) + 'x';

    tbody.innerHTML = '';

    if (!data.snapshots || data.snapshots.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:#94a3b8;">No data for selected filters.</td></tr>';
        return;
    }

    data.snapshots.forEach(row => {
        const profitColor  = parseFloat(row.gross_profit) >= 0 ? '#16a34a' : '#dc2626';
        const channelStyle = row.fulfillment_channel === 'FBA'
            ? 'background:#dbeafe;color:#1e40af;'
            : 'background:#f3e8ff;color:#6b21a8;';

        tbody.innerHTML += `
        <tr style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
            <td style="padding:0.625rem 1rem;color:#334155;font-weight:500;">${row.product_type ?? '—'}</td>
            <td style="padding:0.625rem 1rem;text-align:center;">
                <span style="padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:700;${channelStyle}">${row.fulfillment_channel}</span>
            </td>
            <td style="padding:0.625rem 1rem;text-align:right;color:#1e293b;">${fmt(row.gross_sales)}</td>
            <td style="padding:0.625rem 1rem;text-align:right;color:#dc2626;">${fmt(row.returns)}</td>
            <td style="padding:0.625rem 1rem;text-align:right;color:#64748b;">${fmt(row.referral_fees_net)}</td>
            <td style="padding:0.625rem 1rem;text-align:right;color:#64748b;">${fmt(row.fba_fees_net)}</td>
            <td style="padding:0.625rem 1rem;text-align:right;color:#64748b;">${fmt(row.ad_spend_net)}</td>
            <td style="padding:0.625rem 1rem;text-align:right;font-weight:700;color:${profitColor};">${fmt(row.gross_profit)}</td>
            <td style="padding:0.625rem 1rem;text-align:right;font-weight:600;color:${profitColor};">${pct(row.gross_margin_pct)}</td>
        </tr>`;
    });
}

function clearFilters() {
    document.getElementById('filter-start').value   = '';
    document.getElementById('filter-end').value     = '';
    document.getElementById('filter-channel').value = '';
    loadReport();
}

loadReport();
</script>

</x-layout>
