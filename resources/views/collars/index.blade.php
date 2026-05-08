<x-layout title="Collar Production — Lockie Portal">
<main class="max-w-screen-xl mx-auto px-6 py-10">

    <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Collar Production</h1>
            <p class="text-slate-500 mt-1">Track cut blank and made collar stock levels, and create monthly works orders.</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <button onclick="document.getElementById('import-file').click()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                ↑ Import CSV
            </button>
            <input type="file" id="import-file" accept=".csv" style="display:none" onchange="importCsv(this)">
            <button onclick="openWorksOrderModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                + New Works Order
            </button>
        </div>
    </div>

    {{-- Works Order history --}}
    @if($worksOrders->count())
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-700">Recent Works Orders</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($worksOrders as $wo)
            <div class="flex items-center justify-between px-5 py-3">
                <div class="flex items-center gap-4 flex-wrap">
                    <span class="font-medium text-slate-800 text-sm">{{ $wo->title }}</span>
                    <span class="text-xs text-slate-400">{{ $wo->period->format('F Y') }}</span>
                    <span class="text-xs text-slate-400">{{ $wo->lines->count() }} lines</span>
                    @if($wo->created_by)<span class="text-xs text-slate-400">by {{ $wo->created_by }}</span>@endif
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('collars.works-orders.show', $wo) }}" target="_blank"
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View / Print →</a>
                    <button onclick="deleteWorksOrder({{ $wo->id }})" class="text-slate-300 hover:text-red-500 transition p-1">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
                            <path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
                        </svg>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Products table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-900 text-white text-left text-xs">
                    <th class="px-4 py-3 font-semibold">Product</th>
                    <th class="px-4 py-3 font-semibold text-right">Cut Blank Stock</th>
                    <th class="px-4 py-3 font-semibold">CB Status</th>
                    <th class="px-4 py-3 font-semibold">Adjust Stock</th>
                    <th class="px-4 py-3 font-semibold text-right border-l border-slate-700">Made Stock</th>
                    <th class="px-4 py-3 font-semibold">Made Status</th>
                    <th class="px-4 py-3 font-semibold w-16"></th>
                </tr>
            </thead>
            <tbody id="collar-tbody">
            @forelse($products as $p)
            @php
                $madeStock  = $stockMap[$p->product_code]->qty_on_hand ?? null;
                $cbStock    = (float) $p->cut_blank_stock;
                $cbStatus   = $p->cutBlankStatus((int)$cbStock);
                $madeStatus = $madeStock !== null ? $p->madeStatus((int)$madeStock) : 'unknown';
            @endphp
            <tr class="border-t border-slate-100 hover:bg-slate-50 collar-row" data-id="{{ $p->id }}">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-800">{{ $p->description }}</div>
                    <div class="flex items-center gap-2 mt-0.5">
                        @if($p->product_code)<span class="font-mono text-xs text-slate-400">{{ $p->product_code }}</span>@endif
                        @if($p->reel_width)<span class="text-xs text-slate-400">· {{ $p->reel_width }}</span>@endif
                        @if($p->is_stock_line)<span class="text-xs bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded font-medium">Stock Line</span>@endif
                    </div>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-slate-800 cb-stock-cell" data-id="{{ $p->id }}">
                    {{ number_format($cbStock) }}
                </td>
                <td class="px-4 py-3">@include('collars._status', ['status' => $cbStatus])</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1.5">
                        <input type="number" placeholder="+/−"
                               class="w-20 border border-slate-200 rounded px-2 py-1 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-400 cb-adjust-input"
                               data-id="{{ $p->id }}" data-type="cut_blank"
                               onkeydown="if(event.key==='Enter'){applyAdjust(this);}">
                        <button onclick="applyAdjust(this.previousElementSibling)"
                                class="px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded text-sm transition">✓</button>
                        <button onclick="viewLog({{ $p->id }})" class="text-slate-400 hover:text-indigo-600 px-1" title="View adjustment log">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-slate-800 border-l border-slate-100">
                    {{ $madeStock !== null ? number_format((float)$madeStock) : '—' }}
                </td>
                <td class="px-4 py-3">@include('collars._status', ['status' => $madeStatus])</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button onclick="openEditModal({{ $p->id }})" class="text-slate-400 hover:text-indigo-600 p-1 transition" title="Edit settings">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button onclick="deleteCollar({{ $p->id }})" class="text-slate-300 hover:text-red-500 transition p-1">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-6 py-12 text-center text-slate-400">No collar products yet. Import a CSV or add one below.</td></tr>
            @endforelse
            </tbody>
        </table>

        {{-- Add product form --}}
        <div class="border-t border-slate-100 bg-slate-50 px-4 py-3">
            <form onsubmit="addCollar(event, this)" class="flex items-center gap-2 flex-wrap">
                <input name="product_code" placeholder="Product code" class="border border-slate-300 rounded px-2 py-1.5 text-sm w-36 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <input name="description" placeholder="Description *" required class="border border-slate-300 rounded px-2 py-1.5 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <input name="reel_width" placeholder="Reel width" class="border border-slate-300 rounded px-2 py-1.5 text-sm w-28 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white text-sm rounded hover:bg-slate-700 transition">+ Add Product</button>
            </form>
        </div>
    </div>

</main>

{{-- Adjustment Log Modal --}}
<div id="log-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)closeLogModal()">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Adjustment Log</h3>
            <button onclick="closeLogModal()" class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
        </div>
        <div id="log-modal-body" class="px-5 py-4 max-h-80 overflow-y-auto text-sm text-slate-600">Loading…</div>
    </div>
</div>

{{-- Edit Product Modal --}}
<div id="edit-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)closeEditModal()">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Product Settings</h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
        </div>
        <div class="px-5 py-4 space-y-4">
            <input type="hidden" id="edit-id">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Description</label>
                    <input id="edit-description" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Product Code</label>
                    <input id="edit-product-code" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Reel Width</label>
                    <input id="edit-reel-width" type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input id="edit-stock-line" type="checkbox" class="rounded accent-indigo-600 w-4 h-4">
                        <span class="text-sm text-slate-700">Is Stock Line</span>
                    </label>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-3">
                <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-2">Cut Blank Settings</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Reorder Level</label>
                        <input id="edit-cb-reorder" type="number" min="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">MOQ (Min Order Qty)</label>
                        <input id="edit-cb-moq" type="number" min="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-3">
                <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-2">Made Collar Settings</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Reorder Level</label>
                        <input id="edit-made-reorder" type="number" min="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">MOQ (Min Order Qty)</label>
                        <input id="edit-made-moq" type="number" min="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Sales History</p>
                <div class="grid grid-cols-3 gap-2 text-center text-sm">
                    @foreach($years as $yr)
                    <div class="bg-slate-50 rounded-lg py-2">
                        <div class="text-xs text-slate-400 mb-0.5">{{ $yr }}</div>
                        <div id="edit-sales-{{ $yr }}" class="font-semibold text-slate-700">—</div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-2 text-center bg-indigo-50 rounded-lg py-2">
                    <div class="text-xs text-slate-400 mb-0.5">Avg / Month</div>
                    <div id="edit-sales-avg" class="font-semibold text-indigo-700">—</div>
                </div>
            </div>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex justify-end gap-3">
            <button onclick="closeEditModal()" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">Cancel</button>
            <button onclick="saveEditModal()" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Save Changes</button>
        </div>
    </div>
</div>

{{-- Works Order Modal --}}
<div id="wo-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)closeWoModal()">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 flex-shrink-0">
            <h3 class="font-semibold text-slate-800">New Works Order</h3>
            <button onclick="closeWoModal()" class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
        </div>
        <div class="px-5 py-4 overflow-y-auto flex-1">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Title</label>
                    <input id="wo-title" type="text" placeholder="e.g. May 2026 Works Order"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Period</label>
                    <input id="wo-period" type="month" value="{{ date('Y-m') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-slate-600 mb-1">Notes (optional)</label>
                <textarea id="wo-notes" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            @if($products->count())
            <p class="text-xs text-slate-500 mb-3">Enter quantities for the products you want to include. Leave blank to skip.</p>
            <div class="space-y-2" id="wo-lines">
                @foreach($products as $p)
                <div class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:border-slate-200 bg-slate-50 wo-product-row" data-id="{{ $p->id }}">
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-slate-800 text-sm truncate">{{ $p->description }}</div>
                        @if($p->product_code)<div class="text-xs text-slate-400 font-mono">{{ $p->product_code }}</div>@endif
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <select class="wo-type border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                            <option value="cut_blank">Cut Blanks</option>
                            <option value="made">Made</option>
                        </select>
                        <input type="number" min="1" class="wo-qty w-20 border border-slate-200 rounded-lg px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white" placeholder="Qty">
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8 text-slate-400">
                <p class="text-sm">No collar products added yet.</p>
                <p class="text-xs mt-1">Add products to the list first, then create a works order.</p>
            </div>
            @endif
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex justify-end gap-3 flex-shrink-0">
            <button onclick="closeWoModal()" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">Cancel</button>
            <button onclick="saveWorksOrder()" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Create Works Order</button>
        </div>
    </div>
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

// Sales data passed from PHP for edit modal
const salesData = @json($salesData);
const productYears = @json($years);

function applyAdjust(input) {
    const qty = parseFloat(input.value);
    if (!qty || isNaN(qty)) return;
    const id   = input.dataset.id;
    const type = input.dataset.type;
    const note = prompt('Note for this adjustment (optional):') ?? '';

    fetch(`/collars/${id}/adjust`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ type, qty, note }),
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            input.value = '';
            if (type === 'cut_blank') {
                const cell = document.querySelector(`.cb-stock-cell[data-id="${id}"]`);
                if (cell) cell.textContent = Math.round(d.cut_blank_stock).toLocaleString();
            }
        }
    });
}

function viewLog(id) {
    document.getElementById('log-modal').classList.remove('hidden');
    const body = document.getElementById('log-modal-body');
    body.innerHTML = 'Loading…';
    fetch(`/collars/${id}/adjustments`, { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(rows => {
        if (!rows.length) { body.innerHTML = '<p class="text-slate-400">No adjustments recorded.</p>'; return; }
        body.innerHTML = `<table class="w-full text-xs"><thead><tr class="text-left text-slate-500 border-b border-slate-100"><th class="py-1 pr-3">Date</th><th class="py-1 pr-3">Type</th><th class="py-1 pr-3 text-right">Qty</th><th class="py-1">Note</th><th class="py-1 text-right">By</th></tr></thead><tbody>` +
            rows.map(r => `<tr class="border-t border-slate-50"><td class="py-1 pr-3 text-slate-400">${r.created_at?.slice(0,16).replace('T',' ')}</td><td class="py-1 pr-3">${r.type==='cut_blank'?'Cut Blank':'Made'}</td><td class="py-1 pr-3 text-right font-mono ${r.qty>0?'text-green-600':'text-red-500'}">${r.qty>0?'+':''}${r.qty}</td><td class="py-1">${r.note||''}</td><td class="py-1 text-right text-slate-400">${r.created_by||''}</td></tr>`).join('') +
            '</tbody></table>';
    });
}
function closeLogModal() {
    document.getElementById('log-modal').classList.add('hidden');
}

// Edit modal data loaded from PHP
const productData = {
    @foreach($products as $p)
    {{ $p->id }}: {
        description: @json($p->description),
        product_code: @json($p->product_code),
        reel_width: @json($p->reel_width),
        is_stock_line: {{ $p->is_stock_line ? 'true' : 'false' }},
        cut_blank_reorder_level: {{ $p->cut_blank_reorder_level ?? 'null' }},
        cut_blank_moq: {{ $p->cut_blank_moq ?? 'null' }},
        made_reorder_level: {{ $p->made_reorder_level ?? 'null' }},
        made_moq: {{ $p->made_moq ?? 'null' }},
    },
    @endforeach
};

function openEditModal(id) {
    const d = productData[id];
    if (!d) return;
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-description').value = d.description || '';
    document.getElementById('edit-product-code').value = d.product_code || '';
    document.getElementById('edit-reel-width').value = d.reel_width || '';
    document.getElementById('edit-stock-line').checked = d.is_stock_line;
    document.getElementById('edit-cb-reorder').value = d.cut_blank_reorder_level ?? '';
    document.getElementById('edit-cb-moq').value = d.cut_blank_moq ?? '';
    document.getElementById('edit-made-reorder').value = d.made_reorder_level ?? '';
    document.getElementById('edit-made-moq').value = d.made_moq ?? '';

    // Sales
    const pCode = d.product_code;
    let total = 0, count = 0;
    productYears.forEach(yr => {
        const qty = (salesData[pCode] && salesData[pCode][yr]) ? salesData[pCode][yr] : 0;
        const el = document.getElementById('edit-sales-' + yr);
        if (el) el.textContent = qty ? qty.toLocaleString() : '—';
        total += qty;
        count++;
    });
    const avg = count > 0 ? (total / (count * 12)).toFixed(1) : '—';
    document.getElementById('edit-sales-avg').textContent = avg;

    document.getElementById('edit-modal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}

function saveEditModal() {
    const id = document.getElementById('edit-id').value;
    const data = {
        description: document.getElementById('edit-description').value.trim(),
        product_code: document.getElementById('edit-product-code').value.trim() || null,
        reel_width: document.getElementById('edit-reel-width').value.trim() || null,
        is_stock_line: document.getElementById('edit-stock-line').checked,
        cut_blank_reorder_level: document.getElementById('edit-cb-reorder').value !== '' ? parseInt(document.getElementById('edit-cb-reorder').value) : null,
        cut_blank_moq: document.getElementById('edit-cb-moq').value !== '' ? parseInt(document.getElementById('edit-cb-moq').value) : null,
        made_reorder_level: document.getElementById('edit-made-reorder').value !== '' ? parseInt(document.getElementById('edit-made-reorder').value) : null,
        made_moq: document.getElementById('edit-made-moq').value !== '' ? parseInt(document.getElementById('edit-made-moq').value) : null,
    };
    if (!data.description) { alert('Description is required.'); return; }

    fetch(`/collars/${id}`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) { closeEditModal(); location.reload(); }
        else alert('Failed to save: ' + (d.message || 'Unknown error'));
    });
}

function deleteCollar(id) {
    if (!confirm('Delete this product?')) return;
    fetch(`/collars/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken } })
    .then(r => r.json()).then(d => { if (d.ok) location.reload(); });
}

function addCollar(e, form) {
    e.preventDefault();
    const data = {
        product_code: form.product_code.value.trim() || null,
        description: form.description.value.trim(),
        reel_width: form.reel_width.value.trim() || null,
    };
    if (!data.description) return;
    fetch('/collars', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(r => r.json()).then(() => location.reload());
}

function importCsv(input) {
    const form = new FormData();
    form.append('file', input.files[0]);
    fetch('/collars/import', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: form,
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) { alert(`Imported ${d.imported} products.`); location.reload(); }
        else alert('Import failed: ' + (d.error || 'Unknown error'));
    });
}

// Works Order Modal
function openWorksOrderModal() {
    document.getElementById('wo-title').value = '';
    document.getElementById('wo-notes').value = '';
    // Reset quantities
    document.querySelectorAll('.wo-qty').forEach(i => i.value = '');
    document.getElementById('wo-modal').classList.remove('hidden');
}
function closeWoModal() {
    document.getElementById('wo-modal').classList.add('hidden');
}

function saveWorksOrder() {
    const title  = document.getElementById('wo-title').value.trim();
    const period = document.getElementById('wo-period').value + '-01';
    const notes  = document.getElementById('wo-notes').value.trim();
    if (!title) { alert('Please enter a title.'); return; }

    const lines = [];
    document.querySelectorAll('.wo-product-row[data-id]').forEach(row => {
        const qty = parseInt(row.querySelector('.wo-qty').value);
        if (!qty || qty < 1) return;
        lines.push({
            collar_product_id: parseInt(row.dataset.id),
            type: row.querySelector('.wo-type').value,
            qty,
            note: null,
        });
    });
    if (!lines.length) { alert('Enter a quantity for at least one product.'); return; }

    fetch('/collars/works-orders', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ title, period, notes, lines }),
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            closeWoModal();
            window.open(`/collars/works-orders/${d.id}`, '_blank');
            location.reload();
        } else {
            alert('Failed: ' + (d.message || 'Unknown error'));
        }
    });
}

function deleteWorksOrder(id) {
    if (!confirm('Delete this works order?')) return;
    fetch(`/collars/works-orders/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken } })
    .then(r => r.json()).then(d => { if (d.ok) location.reload(); });
}
</script>
</x-layout>
