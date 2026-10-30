<x-layout title="Customer Insights — Lockie Portal">

    <main class="max-w-7xl mx-auto px-6 py-10">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Customer Insights</h1>
            <p class="text-slate-500 mt-1">New customers acquired per month, compared year on year.</p>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-8 flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-[0.72rem] font-semibold text-slate-500 mb-1">Division (Warehouse)</label>
                <select id="warehouse-filter"
                    class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 outline-none min-w-[200px] focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    <option value="">All divisions</option>
                </select>
            </div>
            <button onclick="loadData()"
                class="px-5 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Apply
            </button>
        </div>

        {{-- Loading state --}}
        <div id="loading" class="flex items-center justify-center py-24">
            <div class="text-center">
                <div class="inline-block w-10 h-10 border-4 border-slate-200 border-t-sky-500 rounded-full animate-spin mb-4"></div>
                <p class="text-slate-500 text-sm">Loading customer data…</p>
            </div>
        </div>

        {{-- Content --}}
        <div id="content" class="hidden">

            {{-- Year summary cards --}}
            <div id="year-cards" class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8"></div>

            {{-- Chart --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-semibold text-slate-800">New Customers per Month</h2>
                    <div id="chart-legend" class="flex flex-wrap gap-4 text-xs text-slate-500"></div>
                </div>
                <div class="relative" style="height:340px;">
                    <canvas id="chart"></canvas>
                </div>
            </div>

            {{-- Monthly breakdown table --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="font-semibold text-slate-800">Monthly Breakdown</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="breakdown-table">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wide">
                                <th class="px-6 py-3 text-left font-medium">Month</th>
                            </tr>
                        </thead>
                        <tbody id="breakdown-body"></tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Error --}}
        <div id="error" class="hidden bg-red-50 border border-red-200 text-red-700 rounded-xl px-6 py-4 text-sm"></div>

    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        const YEAR_COLORS = [
            { line: '#94a3b8', fill: 'rgba(148,163,184,0.1)' },
            { line: '#60a5fa', fill: 'rgba(96,165,250,0.1)'  },
            { line: '#34d399', fill: 'rgba(52,211,153,0.1)'  },
            { line: '#f59e0b', fill: 'rgba(245,158,11,0.1)'  },
            { line: '#a78bfa', fill: 'rgba(167,139,250,0.1)' },
        ];

        let chart = null;

        function fmtInt(n) {
            return new Intl.NumberFormat('en-GB').format(n);
        }

        function loadData() {
            const warehouse = document.getElementById('warehouse-filter').value;

            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('content').classList.add('hidden');
            document.getElementById('error').classList.add('hidden');

            let url = '/insights/customers/data';
            if (warehouse) url += '?warehouse=' + encodeURIComponent(warehouse);

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(res => {
                    document.getElementById('loading').classList.add('hidden');
                    if (!res.success) {
                        document.getElementById('error').textContent = res.error || 'Unknown error.';
                        document.getElementById('error').classList.remove('hidden');
                        return;
                    }
                    render(res);
                    document.getElementById('content').classList.remove('hidden');
                })
                .catch(err => {
                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('error').textContent = err.message;
                    document.getElementById('error').classList.remove('hidden');
                });
        }

        function populateWarehouses(warehouses) {
            const sel = document.getElementById('warehouse-filter');
            const current = sel.value;
            // Only populate once
            if (sel.options.length > 1) return;
            warehouses.forEach(w => {
                const opt = document.createElement('option');
                opt.value = w;
                opt.textContent = w;
                sel.appendChild(opt);
            });
            if (current) sel.value = current;
        }

        function render(res) {
            populateWarehouses(res.warehouses || []);

            const data       = res.data || {};
            const yearTotals = res.yearTotals || {};
            const years      = Object.keys(data).map(Number).sort();

            // ── Year summary cards ─────────────────────────────────────────────
            const cardsEl = document.getElementById('year-cards');
            cardsEl.innerHTML = '';

            // Make sure we always show at least the last 3 complete years + current
            const currentYear = new Date().getFullYear();
            const displayYears = [...new Set([...years, currentYear])].sort().slice(-4);

            displayYears.forEach((year, i) => {
                const total    = yearTotals[year] ?? 0;
                const prevYear = year - 1;
                const prev     = yearTotals[prevYear] ?? null;
                const color    = YEAR_COLORS[i % YEAR_COLORS.length];

                let changeHtml = '';
                if (prev !== null && prev > 0) {
                    const pct   = ((total - prev) / prev * 100).toFixed(1);
                    const up    = total >= prev;
                    const arrow = up ? '↑' : '↓';
                    const cls   = up ? 'text-emerald-600' : 'text-red-500';
                    changeHtml  = `<span class="${cls} text-xs font-semibold">${arrow} ${Math.abs(pct)}% vs ${prevYear}</span>`;
                }

                cardsEl.innerHTML += `
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-2.5 h-2.5 rounded-full" style="background:${color.line}"></div>
                            <p class="text-slate-500 text-sm font-medium">${year}</p>
                        </div>
                        <p class="text-2xl font-bold text-slate-800">${fmtInt(total)}</p>
                        <p class="text-slate-400 text-xs mt-1">new customers ${changeHtml}</p>
                    </div>`;
            });

            // ── Chart ──────────────────────────────────────────────────────────
            const datasets = years.map((year, i) => {
                const color = YEAR_COLORS[i % YEAR_COLORS.length];
                const monthData = data[year] || {};
                return {
                    label: String(year),
                    data: Array.from({ length: 12 }, (_, m) => monthData[m + 1] ?? 0),
                    borderColor: color.line,
                    backgroundColor: color.fill,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3,
                    fill: false,
                };
            });

            const legendEl = document.getElementById('chart-legend');
            legendEl.innerHTML = years.map((year, i) => {
                const color = YEAR_COLORS[i % YEAR_COLORS.length];
                return `<span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-0.5 rounded" style="background:${color.line}"></span>${year}
                </span>`;
            }).join('');

            if (chart) chart.destroy();
            chart = new Chart(document.getElementById('chart'), {
                type: 'line',
                data: { labels: MONTHS, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: items => items[0].label,
                                label: item => ` ${item.dataset.label}: ${fmtInt(item.raw)} new customers`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { color: '#f1f5f9' },
                            ticks: { color: '#94a3b8', font: { size: 12 } },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 12 },
                                stepSize: 1,
                                callback: v => Number.isInteger(v) ? v : null,
                            },
                        },
                    },
                },
            });

            // ── Breakdown table ────────────────────────────────────────────────
            const thead = document.querySelector('#breakdown-table thead tr');
            thead.innerHTML = '<th class="px-6 py-3 text-left font-medium">Month</th>';
            years.forEach(year => {
                const th = document.createElement('th');
                th.className = 'px-6 py-3 text-right font-medium';
                th.textContent = year;
                thead.appendChild(th);
            });

            const tbody = document.getElementById('breakdown-body');
            tbody.innerHTML = '';

            MONTHS.forEach((label, m) => {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-slate-50 hover:bg-slate-50 transition-colors';

                let cells = `<td class="px-6 py-3 font-medium text-slate-700">${label}</td>`;
                years.forEach(year => {
                    const val = (data[year] || {})[m + 1] ?? 0;
                    cells += `<td class="px-6 py-3 text-right text-slate-600">${val > 0 ? fmtInt(val) : '<span class="text-slate-300">—</span>'}</td>`;
                });
                tr.innerHTML = cells;
                tbody.appendChild(tr);
            });

            // Totals row
            const totalsRow = document.createElement('tr');
            totalsRow.className = 'bg-slate-50 border-t-2 border-slate-200 font-semibold text-slate-800';
            let totalCells = `<td class="px-6 py-3">Total</td>`;
            years.forEach(year => {
                totalCells += `<td class="px-6 py-3 text-right">${fmtInt(yearTotals[year] ?? 0)}</td>`;
            });
            totalsRow.innerHTML = totalCells;
            tbody.appendChild(totalsRow);
        }

        document.addEventListener('DOMContentLoaded', () => loadData());
    </script>

</x-layout>
