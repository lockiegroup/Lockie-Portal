<x-layout title="Sequential Number Finder — Lockie Portal">

    <main class="max-w-4xl mx-auto px-6 py-10">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Sequential Number Finder</h1>
            <p class="text-slate-500 mt-1">Find the highest sequential number used for a product across assemblies and purchase orders.</p>
        </div>

        {{-- Search Form --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
            <form id="search-form" class="flex gap-3">
                <input id="product-input" type="text" name="product"
                    placeholder="Product code e.g. JW-ENV-DL-WH"
                    class="flex-1 border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent uppercase"
                    autocomplete="off" autocapitalize="characters" spellcheck="false">
                <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50"
                    id="search-btn">
                    Search
                </button>
            </form>
        </div>

        {{-- Results --}}
        <div id="results-wrapper" class="hidden">

            {{-- Next Start Card --}}
            <div id="next-start-card" class="bg-indigo-50 border border-indigo-200 rounded-xl p-5 mb-5 flex items-center gap-4">
                <div class="flex-1">
                    <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-1">Next Starting Number</p>
                    <p id="next-start-value" class="text-3xl font-bold text-indigo-800 font-mono"></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-indigo-400 mb-1">Highest end found</p>
                    <p id="max-end-value" class="text-lg font-semibold text-indigo-600 font-mono"></p>
                </div>
            </div>

            <div id="no-results-card" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-5 mb-5 text-amber-700 text-sm">
                No numbered ranges found for this product code.
            </div>

            {{-- Results Table --}}
            <div id="table-wrapper" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 id="table-heading" class="text-sm font-semibold text-slate-700"></h2>
                    <span id="result-count" class="text-xs text-slate-400"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-5 py-3 text-left">Source</th>
                                <th class="px-5 py-3 text-left">Type</th>
                                <th class="px-5 py-3 text-right">From</th>
                                <th class="px-5 py-3 text-right">To</th>
                                <th class="px-5 py-3 text-right">Count</th>
                                <th class="px-5 py-3 text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody id="results-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Error --}}
        <div id="error-card" class="hidden bg-red-50 border border-red-200 rounded-xl p-5 text-red-700 text-sm"></div>

        {{-- Loading --}}
        <div id="loading-card" class="hidden bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center text-slate-400 text-sm">
            <svg class="animate-spin mx-auto mb-3 w-6 h-6 text-indigo-400" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3V4a10 10 0 100 10h-2A8 8 0 014 12z"/>
            </svg>
            Searching assemblies and purchase orders…
        </div>

    </main>

    <script>
    (function () {
        const form       = document.getElementById('search-form');
        const input      = document.getElementById('product-input');
        const btn        = document.getElementById('search-btn');
        const loading    = document.getElementById('loading-card');
        const wrapper    = document.getElementById('results-wrapper');
        const errorCard  = document.getElementById('error-card');
        const body       = document.getElementById('results-body');
        const heading    = document.getElementById('table-heading');
        const count      = document.getElementById('result-count');
        const nextVal    = document.getElementById('next-start-value');
        const maxVal     = document.getElementById('max-end-value');
        const nextCard   = document.getElementById('next-start-card');
        const noResults  = document.getElementById('no-results-card');
        const tableWrap  = document.getElementById('table-wrapper');

        function pad(n) {
            return String(n).padStart(6, '0');
        }

        function typeBadge(type) {
            if (type === 'Assembly')       return '<span style="background:#dbeafe;color:#1e40af;font-size:0.65rem;font-weight:700;padding:2px 7px;border-radius:9999px;">ASM</span>';
            if (type === 'Purchase Order') return '<span style="background:#dcfce7;color:#166534;font-size:0.65rem;font-weight:700;padding:2px 7px;border-radius:9999px;">PO</span>';
            return '<span style="background:#f1f5f9;color:#475569;font-size:0.65rem;font-weight:700;padding:2px 7px;border-radius:9999px;">SO</span>';
        }

        function formatDate(d) {
            if (!d) return '—';
            try {
                const dt = new Date(d);
                return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            } catch { return d; }
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const product = input.value.trim().toUpperCase();
            if (!product) { input.focus(); return; }

            btn.disabled  = true;
            loading.classList.remove('hidden');
            wrapper.classList.add('hidden');
            errorCard.classList.add('hidden');

            try {
                const url  = '{{ route("sequential.search") }}?product=' + encodeURIComponent(product);
                const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await resp.json();

                if (!resp.ok) {
                    throw new Error(data.error ?? resp.statusText);
                }

                const results = data.results ?? [];
                heading.textContent = product;
                count.textContent   = results.length + ' result' + (results.length === 1 ? '' : 's');

                if (results.length === 0) {
                    nextCard.classList.add('hidden');
                    noResults.classList.remove('hidden');
                    tableWrap.classList.add('hidden');
                } else {
                    nextCard.classList.remove('hidden');
                    noResults.classList.add('hidden');
                    tableWrap.classList.remove('hidden');

                    nextVal.textContent = pad(data.next_start);
                    maxVal.textContent  = pad(data.max_end);

                    body.innerHTML = results.map((r, i) => {
                        const isMax = r.to === data.max_end && i === 0;
                        const rowBg = isMax ? 'background:#fef9c3;' : (i % 2 === 0 ? '' : 'background:#f8fafc;');
                        return `<tr style="${rowBg}border-bottom:1px solid #f1f5f9;">
                            <td style="padding:10px 20px;font-family:monospace;font-size:0.8rem;color:#334155;">${escHtml(r.source)}</td>
                            <td style="padding:10px 20px;">${typeBadge(r.type)}</td>
                            <td style="padding:10px 20px;text-align:right;font-family:monospace;font-weight:600;color:#475569;">${pad(r.from)}</td>
                            <td style="padding:10px 20px;text-align:right;font-family:monospace;font-weight:700;color:${isMax ? '#92400e' : '#1e293b'};">${pad(r.to)}</td>
                            <td style="padding:10px 20px;text-align:right;color:#64748b;">${(r.to - r.from + 1).toLocaleString()}</td>
                            <td style="padding:10px 20px;color:#64748b;font-size:0.8rem;">${formatDate(r.date)}</td>
                        </tr>`;
                    }).join('');
                }

                wrapper.classList.remove('hidden');

            } catch (err) {
                errorCard.textContent = 'Error: ' + err.message;
                errorCard.classList.remove('hidden');
            } finally {
                loading.classList.add('hidden');
                btn.disabled = false;
            }
        });

        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }
    })();
    </script>

</x-layout>
