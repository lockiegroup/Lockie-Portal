<x-layout title="Amazon Profit Report — Lockie Portal">

<main style="max-width:1400px;margin:0 auto;padding:2rem 1.5rem;">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:1.5rem;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">Amazon Profit Report</h1>
            <p style="font-size:0.875rem;color:#64748b;margin:0.25rem 0 0;">By product type and fulfilment channel.</p>
        </div>
        <a href="{{ route('amazon.index') }}" style="background:#f1f5f9;color:#475569;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;text-decoration:none;">
            ← Back
        </a>
    </div>

    {{-- Filters --}}
    <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <label style="font-size:0.875rem;color:#64748b;font-weight:500;">From:</label>
            <input type="date" id="filter-start" style="border:1px solid #e2e8f0;border-radius:0.375rem;padding:0.25rem 0.5rem;font-size:0.875rem;color:#334155;">
        </div>
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <label style="font-size:0.875rem;color:#64748b;font-weight:500;">To:</label>
            <input type="date" id="filter-end" style="border:1px solid #e2e8f0;border-radius:0.375rem;padding:0.25rem 0.5rem;font-size:0.875rem;color:#334155;">
        </div>
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <label style="font-size:0.875rem;color:#64748b;font-weight:500;">Channel:</label>
            <select id="filter-channel" style="border:1px solid #e2e8f0;border-radius:0.375rem;padding:0.25rem 0.5rem;font-size:0.875rem;color:#334155;">
                <option value="">FBM + FBA</option>
                <option value="FBM">FBM only</option>
                <option value="FBA">FBA only</option>
            </select>
        </div>
        <button onclick="loadReport()" style="background:#1e293b;color:#fff;border:none;border-radius:0.5rem;padding:0.375rem 0.875rem;font-size:0.875rem;cursor:pointer;font-weight:600;">
            Apply
        </button>
        <div style="flex:1;"></div>
        <button onclick="clearFilters()" style="background:#f1f5f9;color:#475569;border:none;border-radius:0.375rem;padding:0.375rem 0.875rem;font-size:0.8rem;cursor:pointer;font-weight:500;">
            Clear
        </button>
    </div>

    {{-- Summary Cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1.25rem;">
            <p style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">Revenue</p>
            <p id="card-revenue" style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">—</p>
        </div>
        <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1.25rem;">
            <p style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">Gross Profit</p>
            <p id="card-profit" style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">—</p>
        </div>
        <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1.25rem;">
            <p style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">Margin %</p>
            <p id="card-margin" style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">—</p>
        </div>
        <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1.25rem;">
            <p style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">Ad Spend (net)</p>
            <p id="card-adspend" style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">—</p>
        </div>
        <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1.25rem;">
            <p style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">ROAS</p>
            <p id="card-roas" style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">—</p>
        </div>
    </div>

    {{-- Breakdown Table --}}
    <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;">
            <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">By Product Type</h2>
        </div>
        <div style="overflow-x:auto;">
            <table style="border-collapse:collapse;width:100%;font-size:0.8125rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="text-align:left;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Product Type</th>
                        <th style="text-align:center;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Channel</th>
                        <th style="text-align:right;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Revenue</th>
                        <th style="text-align:right;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Returns</th>
                        <th style="text-align:right;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Referral Fees</th>
                        <th style="text-align:right;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">FBA Fees</th>
                        <th style="text-align:right;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Ad Spend</th>
                        <th style="text-align:right;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Gross Profit</th>
                        <th style="text-align:right;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Margin %</th>
                    </tr>
                </thead>
                <tbody id="profit-body">
                    <tr><td colspan="9" style="text-align:center;padding:2rem;color:#94a3b8;font-size:0.875rem;">Loading…</td></tr>
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
