<x-layout title="Stock Watchlist — Lockie Portal">
<main class="max-w-screen-lg mx-auto px-6 py-10">

    <div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Stock Watchlist</h1>
            <p class="text-slate-500 mt-1">Lockie Group stock ordering tracker — select a category to view and manage products.</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <span id="sync-status" class="text-sm text-slate-400">
                @if($syncedAt) Last synced {{ \Carbon\Carbon::parse($syncedAt)->diffForHumans() }} @else Not yet synced @endif
            </span>
            <a href="{{ route('imports.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                Import Sales
            </a>
            <button id="sync-skus-btn" onclick="runSyncSkus()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                <svg id="sync-skus-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
                Sync SKUs
            </button>
            <button id="sync-btn" onclick="runSync()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 transition">
                <svg id="sync-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
                Sync Stock
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-6">
        @forelse($categories as $cat)
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 hover:bg-slate-50 transition group">
            <div class="flex items-center gap-4 min-w-0">
                <a href="{{ route('stock-watchlist.categories.show', $cat) }}"
                   class="font-semibold text-slate-900 hover:text-indigo-600 transition text-base">
                    {{ $cat->name }}
                </a>
                <span class="text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ $cat->items_count }} products</span>
                <span class="text-xs text-slate-400">Lead time: {{ $cat->lead_time_days ?? 30 }} days</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('stock-watchlist.categories.show', $cat) }}"
                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition opacity-0 group-hover:opacity-100">
                    View →
                </a>
                <button onclick="deleteCategory({{ $cat->id }})"
                    class="text-slate-300 hover:text-red-500 transition p-1 rounded">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
                        <path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
                    </svg>
                </button>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center text-slate-400 text-sm">
            No categories yet. Add one below to get started.
        </div>
        @endforelse

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
            <form onsubmit="addCategory(event, this)" class="flex items-center gap-3">
                <input type="text" name="name" placeholder="New category name…" required
                    class="border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 transition">
                    Add Category
                </button>
            </form>
        </div>
    </div>

    {{-- All Products --}}
    <div class="mt-10">
        <div class="flex items-center justify-between mb-3 flex-wrap gap-3">
            <h2 class="text-lg font-semibold text-slate-800">All Unleashed Products</h2>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" id="unallocated-only" class="rounded" onchange="filterProducts()">
                    Unallocated only
                </label>
                <input type="text" id="product-search" placeholder="Search code or name…"
                    oninput="filterProducts()"
                    class="border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-900 text-white text-left">
                        <th class="px-4 py-3 font-semibold">Code</th>
                        <th class="px-4 py-3 font-semibold">Name</th>
                        <th class="px-4 py-3 font-semibold">Category</th>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                    @foreach($allProducts as $p)
                    <tr class="border-t border-slate-100 hover:bg-slate-50 product-row"
                        data-code="{{ strtolower($p->product_code) }}"
                        data-name="{{ strtolower($p->product_name ?? '') }}"
                        data-allocated="{{ $p->category_name ? '1' : '0' }}">
                        <td class="px-4 py-2.5 font-mono text-xs text-slate-700">{{ $p->product_code }}</td>
                        <td class="px-4 py-2.5 text-slate-600">{{ $p->product_name }}</td>
                        <td class="px-4 py-2.5">
                            @if($p->category_name)
                                <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full font-medium">{{ $p->category_name }}</span>
                            @else
                                <span class="text-xs bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full font-medium">Unallocated</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div id="no-results" class="hidden px-6 py-8 text-center text-slate-400 text-sm">No products match your search.</div>
            <div class="px-4 py-2 border-t border-slate-100 bg-slate-50 text-xs text-slate-400">
                <span id="products-count">{{ $allProducts->count() }}</span> products shown
            </div>
        </div>
    </div>

</main>

<script>
const csrfToken = '{{ csrf_token() }}';

function runSync() {
    const btn  = document.getElementById('sync-btn');
    const icon = document.getElementById('sync-icon');
    btn.disabled = true;
    icon.style.animation = 'spin 1s linear infinite';
    document.getElementById('sync-status').textContent = 'Syncing stock…';

    fetch('{{ route("stock-watchlist.sync") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            document.getElementById('sync-status').textContent = `Synced ${d.products} products`;
            btn.disabled = false;
            icon.style.animation = '';
        } else {
            alert('Sync failed: ' + (d.error || 'Unknown error'));
            icon.style.animation = '';
            btn.disabled = false;
        }
    })
    .catch(() => {
        alert('Sync request failed. Check network.');
        icon.style.animation = '';
        btn.disabled = false;
    });
}

function runSyncSkus() {
    const btn  = document.getElementById('sync-skus-btn');
    const icon = document.getElementById('sync-skus-icon');
    btn.disabled = true;
    icon.style.animation = 'spin 1s linear infinite';
    document.getElementById('sync-status').textContent = 'Syncing SKUs…';

    fetch('{{ route("stock-watchlist.sync-products") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            document.getElementById('sync-status').textContent = `SKUs synced — fetched ${d.products} from Unleashed, ${d.db_count} in DB`;
            btn.disabled = false;
            icon.style.animation = '';
            setTimeout(() => location.reload(), 5000);
        } else {
            alert('SKU sync failed: ' + (d.error || 'Unknown error'));
            icon.style.animation = '';
            btn.disabled = false;
        }
    })
    .catch(() => {
        alert('SKU sync request failed. Check network.');
        icon.style.animation = '';
        btn.disabled = false;
    });
}

function addCategory(e, form) {
    e.preventDefault();
    const name = form.name.value.trim();
    if (!name) return;
    fetch('{{ route("stock-watchlist.categories.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ name }),
    })
    .then(r => r.json())
    .then(cat => { location.reload(); })
    .catch(() => alert('Failed to add category'));
}

function deleteCategory(id) {
    if (!confirm('Delete this category? All its products will be removed from the watchlist.')) return;
    fetch(`/stock-watchlist/categories/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); else alert('Delete failed'); });
}

function filterProducts() {
    const q             = document.getElementById('product-search').value.toLowerCase().trim();
    const unallocOnly   = document.getElementById('unallocated-only').checked;
    const rows          = document.querySelectorAll('.product-row');
    let visible         = 0;

    rows.forEach(row => {
        const matchSearch    = !q || row.dataset.code.includes(q) || row.dataset.name.includes(q);
        const matchAlloc     = !unallocOnly || row.dataset.allocated === '0';
        const show           = matchSearch && matchAlloc;
        row.style.display    = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('products-count').textContent = visible;
    document.getElementById('no-results').classList.toggle('hidden', visible > 0);
}
</script>

<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
</x-layout>
