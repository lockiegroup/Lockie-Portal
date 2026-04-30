<x-layout title="Sales Figures — Lockie Portal">

    <main class="max-w-7xl mx-auto px-6 py-10">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Sales Figures</h1>
            <p class="text-slate-500 mt-1">Sales orders and credit notes by warehouse.</p>
        </div>

        {{-- Date Range Form --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
            <div class="flex flex-wrap gap-2 mb-5">
                <span class="text-slate-500 text-sm self-center mr-1">Quick:</span>
                @foreach([
                    'this-week'    => 'This Week',
                    'this-month'   => 'This Month',
                    'last-month'   => 'Last Month',
                    'this-quarter' => 'This Quarter',
                    'this-year'    => 'This Year',
                ] as $key => $label)
                    <button type="button" onclick="setPreset('{{ $key }}')"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-900 hover:text-white hover:border-slate-900 transition-colors">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <form id="sales-form" method="GET" action="{{ route('sales') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="from">From</label>
                    <input type="date" id="from" name="from" value="{{ $from }}"
                        class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="to">To</label>
                    <input type="date" id="to" name="to" value="{{ $to }}"
                        class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition text-sm">
                </div>
                <button type="submit" id="submit-btn"
                    class="px-6 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
                    View Report
                </button>
            </form>
        </div>

        {{-- Refresh button (always visible) --}}
        <div class="flex justify-end mb-4">
            <button id="refresh-btn" onclick="loadData(true)" class="text-xs text-slate-400 hover:text-slate-700 transition-colors">↻ Refresh data</button>
        </div>

        {{-- Results (populated via AJAX) --}}
        <div id="results"></div>

    </main>

    <script>
        // ── Helpers ────────────────────────────────────────────────────────────

        const fmt = n => new Intl.NumberFormat('en-GB', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        }).format(n);

        const fmtInt = n => new Intl.NumberFormat('en-GB').format(n);

        function escHtml(s) {
            return String(s)
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // ── Render helpers ─────────────────────────────────────────────────────

        function loadingHtml() {
            return `<div class="flex items-center justify-center py-24">
                <div class="text-center">
                    <div class="inline-block w-10 h-10 border-4 border-slate-200 border-t-sky-500 rounded-full animate-spin mb-4"></div>
                    <p class="text-slate-500 text-sm">Fetching data from Unleashed…</p>
                </div>
            </div>`;
        }

        function errorHtml(msg) {
            return `<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-6 py-4 text-sm">
                <strong class="font-semibold">Unleashed API error:</strong> ${escHtml(msg)}
            </div>`;
        }

        function tableHtml(group, totals) {
            const keys = Object.keys(group);
            if (keys.length === 0) {
                return `<tbody><tr><td colspan="3" class="px-6 py-10 text-center text-slate-400">No records found for this period.</td></tr></tbody>`;
            }
            const rows = keys.map(w => {
                const d = group[w];
                return `<tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 font-medium text-slate-800">${escHtml(w)}</td>
                    <td class="px-6 py-4 text-right text-slate-600">${fmtInt(d.count)}</td>
                    <td class="px-6 py-4 text-right font-semibold text-slate-800">£${fmt(d.sub)}</td>
                </tr>`;
            }).join('');
            return `<tbody>${rows}</tbody>
            <tfoot>
                <tr class="bg-slate-50 border-t-2 border-slate-200 font-semibold text-slate-800">
                    <td class="px-6 py-4">Total</td>
                    <td class="px-6 py-4 text-right">${fmtInt(totals.count)}</td>
                    <td class="px-6 py-4 text-right">£${fmt(totals.sub)}</td>
                </tr>
            </tfoot>`;
        }

        function renderResults(data, from, to) {
            const sections = [
                { key: 'salesByWarehouse',  title: 'Sales Enquiry by Warehouse',  note: 'All non-cancelled orders by order date',  dot: 'bg-sky-500', cardLabel: 'Sales Enquiry',  cardCls: 'text-slate-800', unit: 'orders'  },
                { key: 'creditsByWarehouse', title: 'Credit Enquiry by Warehouse', note: 'All credit notes including free credits', dot: 'bg-red-500', cardLabel: 'Credit Enquiry', cardCls: 'text-red-500',   unit: 'credits' },
            ];

            const totals = {};
            for (const s of sections) {
                totals[s.key] = Object.values(data[s.key] || {}).reduce(
                    (a, d) => ({ count: a.count+d.count, sub: a.sub+d.sub, tax: a.tax+d.tax, total: a.total+d.total }),
                    { count: 0, sub: 0, tax: 0, total: 0 }
                );
            }

            const cards = sections.map(s => {
                const t = totals[s.key];
                return `<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <p class="text-slate-500 text-sm font-medium">${s.cardLabel}</p>
                    <p class="text-2xl font-bold ${s.cardCls} mt-1">£${fmt(t.sub)}</p>
                    <p class="text-slate-400 text-sm mt-1">${fmtInt(t.count)} ${s.unit} &middot; net ex VAT</p>
                </div>`;
            }).join('');

            const tables = sections.map(s => {
                const group = data[s.key] || {};
                return `<div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                        <div class="w-2.5 h-2.5 rounded-full ${s.dot}"></div>
                        <h2 class="font-semibold text-slate-800">${s.title}</h2>
                        <span class="text-slate-400 text-xs ml-2">${s.note}</span>
                        <div class="ml-auto flex items-center gap-4">
                            <span class="text-slate-400 text-sm">${escHtml(from)} — ${escHtml(to)}</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wide">
                                    <th class="px-6 py-3 text-left font-medium">Warehouse</th>
                                    <th class="px-6 py-3 text-right font-medium">Count</th>
                                    <th class="px-6 py-3 text-right font-medium">Net (ex VAT)</th>
                                </tr>
                            </thead>
                            ${tableHtml(group, totals[s.key])}
                        </table>
                    </div>
                </div>`;
            }).join('');

            return `<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">${cards}</div>${tables}`;
        }

        // ── Data loading ───────────────────────────────────────────────────────

        function loadData(refresh = false) {
            const from = document.getElementById('from').value;
            const to   = document.getElementById('to').value;

            document.getElementById('results').innerHTML = loadingHtml();

            let url = `/sales/data?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
            if (refresh) url += '&refresh=1';

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(text => {
                    let data;
                    try { data = JSON.parse(text); } catch (e) {
                        document.getElementById('results').innerHTML = errorHtml('Bad response from server (not JSON). Check Laravel logs.');
                        return;
                    }
                    if (!data.success) {
                        document.getElementById('results').innerHTML = errorHtml(data.error || data.message || 'Unknown error — check storage/logs/laravel.log on the server');
                    } else {
                        document.getElementById('results').innerHTML = renderResults(data, from, to);
                    }
                })
                .catch(err => {
                    document.getElementById('results').innerHTML = errorHtml(err.message);
                });
        }

        // ── Presets ────────────────────────────────────────────────────────────

        function setPreset(preset) {
            const today = new Date();
            let from, to = new Date();

            switch (preset) {
                case 'this-week': {
                    const day = today.getDay() || 7;
                    from = new Date(today);
                    from.setDate(today.getDate() - day + 1);
                    break;
                }
                case 'this-month':
                    from = new Date(today.getFullYear(), today.getMonth(), 1);
                    break;
                case 'last-month':
                    from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    to   = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
                case 'this-quarter': {
                    const q = Math.floor(today.getMonth() / 3);
                    from = new Date(today.getFullYear(), q * 3, 1);
                    break;
                }
                case 'this-year':
                    from = new Date(today.getFullYear(), 0, 1);
                    break;
            }

            document.getElementById('from').value = fmtDate(from);
            document.getElementById('to').value   = fmtDate(to);
        }

        function fmtDate(d) {
            const y  = d.getFullYear();
            const m  = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${dd}`;
        }

        // ── Init ───────────────────────────────────────────────────────────────

        document.addEventListener('DOMContentLoaded', () => loadData());
    </script>
</x-layout>
