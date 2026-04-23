<x-layout title="Stock Overview — Lockie Portal">

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:2rem;">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Stock Overview</h1>
                <p class="text-sm text-slate-500 mt-1">Total cost value by warehouse, synced from Unleashed.</p>
            </div>
            <button id="stock-refresh" onclick="loadStock(true)"
                style="background:#f1f5f9;color:#64748b;font-size:0.75rem;padding:5px 12px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;display:inline-flex;align-items:center;gap:5px;white-space:nowrap;">
                <svg style="width:13px;height:13px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
                Refresh
            </button>
        </div>

        {{-- Warehouse table --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" style="margin-bottom:2rem;">
            <div id="stock-results" style="padding:2rem;text-align:center;color:#94a3b8;font-size:0.875rem;">Loading…</div>
        </div>

        {{-- 12-month per-warehouse chart --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200" style="padding:1.5rem;">
            <div style="margin-bottom:1.25rem;">
                <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Stock Value by Warehouse — Rolling 12 Months</h2>
                <p style="font-size:0.75rem;color:#cbd5e1;margin-top:3px;">First of each month snapshot from Unleashed.</p>
            </div>
            @php $hasHistory = collect($warehouseValues)->flatMap(fn($v) => $v)->filter(fn($v) => $v !== null)->isNotEmpty(); @endphp
            @if($hasHistory)
                {{-- Legend --}}
                <div id="chart-legend" style="display:flex;flex-wrap:wrap;gap:8px 16px;margin-bottom:1rem;"></div>
                <div style="position:relative;height:300px;">
                    <canvas id="stock-chart"></canvas>
                </div>
            @else
                <div style="padding:3rem 0;text-align:center;color:#94a3b8;font-size:0.875rem;">
                    <p style="margin-bottom:0.5rem;">No historical data yet.</p>
                    <p style="font-size:0.8125rem;color:#cbd5e1;">Run <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-family:monospace;">php artisan stock:backfill</code> on the server to populate the last 12 months, or data will accumulate automatically day by day.</p>
                </div>
            @endif
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <script>
    const fmtGbp = n => '£' + new Intl.NumberFormat('en-GB', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(n);

    function loadStock(refresh = false) {
        document.getElementById('stock-results').innerHTML = '<div style="padding:2rem;text-align:center;color:#94a3b8;font-size:0.875rem;">Loading…</div>';
        let url = '/stock/data';
        if (refresh) url += '?refresh=1';
        fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    document.getElementById('stock-results').innerHTML =
                        `<div style="padding:1.5rem;color:#dc2626;font-size:0.875rem;">${data.error || 'Error loading stock data'}</div>`;
                    return;
                }
                const rows = Object.entries(data.stockByWarehouse);
                const total = rows.reduce((s, [, v]) => s + v.totalCost, 0);
                const rowsHtml = rows.map(([name, v]) => `
                    <tr onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="padding:12px 24px;font-size:0.875rem;color:#334155;border-top:1px solid #f1f5f9;">${name}</td>
                        <td style="padding:12px 24px;font-size:0.875rem;font-weight:600;color:#1e293b;text-align:right;border-top:1px solid #f1f5f9;">${fmtGbp(v.totalCost)}</td>
                    </tr>`).join('');
                document.getElementById('stock-results').innerHTML = `
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                <th style="padding:10px 24px;text-align:left;font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Warehouse</th>
                                <th style="padding:10px 24px;text-align:right;font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Stock Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                            <tr style="border-top:2px solid #e2e8f0;">
                                <td style="padding:12px 24px;font-size:0.875rem;font-weight:700;color:#1e293b;">Total</td>
                                <td style="padding:12px 24px;font-size:0.9375rem;font-weight:700;color:#1e293b;text-align:right;">${fmtGbp(total)}</td>
                            </tr>
                        </tbody>
                    </table>`;
            })
            .catch(() => {
                document.getElementById('stock-results').innerHTML =
                    '<div style="padding:1.5rem;color:#dc2626;font-size:0.875rem;">Failed to load stock data.</div>';
            });
    }

    loadStock();

    @if($hasHistory)
    (function () {
        const COLOURS = ['#0ea5e9','#8b5cf6','#f59e0b','#10b981','#f43f5e','#6366f1','#ec4899','#14b8a6','#f97316','#84cc16'];

        const labels  = @json($chartLabels);
        const rawData = @json($warehouseValues); // { warehouseName: [v1, v2, ...], ... }

        const entries   = Object.entries(rawData);
        const datasets  = entries.map(([name, values], i) => {
            const colour = COLOURS[i % COLOURS.length];
            // Forward-fill nulls so line is continuous
            let last = null;
            const filled = values.map(v => {
                if (v !== null && v > 0) { last = v; return v; }
                return last ?? null;
            });
            return {
                label: name,
                data: filled,
                borderColor: colour,
                backgroundColor: colour + '14',
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: colour,
                fill: false,
                tension: 0.3,
            };
        });

        // Custom legend
        const legendEl = document.getElementById('chart-legend');
        datasets.forEach(ds => {
            const item = document.createElement('div');
            item.style.cssText = 'display:flex;align-items:center;gap:6px;font-size:0.75rem;color:#475569;cursor:pointer;';
            item.innerHTML = `<span style="display:inline-block;width:24px;height:3px;background:${ds.borderColor};border-radius:2px;flex-shrink:0;"></span>${ds.label}`;
            item.addEventListener('click', () => {
                const meta = chart.getDatasetMeta(datasets.indexOf(ds));
                meta.hidden = !meta.hidden;
                item.style.opacity = meta.hidden ? '0.4' : '1';
                chart.update();
            });
            legendEl.appendChild(item);
        });

        const ctx   = document.getElementById('stock-chart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.dataset.label + ': ' + fmtGbp(ctx.parsed.y)
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 11 }, color: '#94a3b8' }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { size: 11 }, color: '#94a3b8',
                            callback: v => '£' + new Intl.NumberFormat('en-GB', {notation:'compact',maximumFractionDigits:0}).format(v)
                        }
                    }
                }
            }
        });
    })();
    @endif
    </script>

</x-layout>
