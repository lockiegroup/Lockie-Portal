<x-layout title="Collar Production — Lockie Portal">
<main class="max-w-screen-2xl mx-auto px-6 py-10">

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
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Recent Works Orders</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($worksOrders as $wo)
            <div class="flex items-center justify-between px-5 py-3">
                <div class="flex items-center gap-4">
                    <span class="font-medium text-slate-800 text-sm">{{ $wo->title }}</span>
                    <span class="text-xs text-slate-400">{{ $wo->period->format('F Y') }}</span>
                    <span class="text-xs text-slate-400">{{ $wo->lines->count() ?? '—' }} lines</span>
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
    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-900 text-white text-left text-xs">
                    <th class="px-3 py-3 font-semibold">Code</th>
                    <th class="px-3 py-3 font-semibold">Description</th>
                    <th class="px-3 py-3 font-semibold">Reel</th>
                    <th class="px-3 py-3 font-semibold text-center">Stock Line</th>
                    @foreach($years as $yr)
                    <th class="px-3 py-3 font-semibold text-right">{{ $yr }}</th>
                    @endforeach
                    <th class="px-3 py-3 font-semibold text-right">Avg/Mo</th>
                    <th class="px-3 py-3 font-semibold border-l border-slate-700 bg-slate-800">Cut Blank Stock</th>
                    <th class="px-3 py-3 font-semibold bg-slate-800">CB Reorder</th>
                    <th class="px-3 py-3 font-semibold bg-slate-800">CB MOQ</th>
                    <th class="px-3 py-3 font-semibold bg-slate-800">CB Status</th>
                    <th class="px-3 py-3 font-semibold bg-slate-800">Adjust</th>
                    <th class="px-3 py-3 font-semibold border-l border-slate-700">Made Stock</th>
                    <th class="px-3 py-3 font-semibold">Made Reorder</th>
                    <th class="px-3 py-3 font-semibold">Made MOQ</th>
                    <th class="px-3 py-3 font-semibold">Made Status</th>
                    <th class="px-3 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody id="collar-tbody">
            @forelse($products as $p)
            @php
                $madeStock  = $stockMap[$p->product_code]->qty_on_hand ?? null;
                $totalSales = 0;
                $salesCols  = [];
                foreach ($years as $yr) {
                    $qty = $salesData[$p->product_code][$yr] ?? 0;
                    $salesCols[] = $qty;
                    $totalSales += $qty;
                }
                $avgMo = count($years) > 0 ? round($totalSales / (count($years) * 12), 1) : 0;
                $cbStock = (float) $p->cut_blank_stock;
                $cbStatus = $p->cutBlankStatus((int)$cbStock);
                $madeStatus = $madeStock !== null ? $p->madeStatus((int)$madeStock) : 'unknown';
            @endphp
            <tr class="border-t border-slate-100 hover:bg-slate-50 collar-row" data-id="{{ $p->id }}">
                <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ $p->product_code ?? '—' }}</td>
                <td class="px-3 py-2 text-slate-800 font-medium">{{ $p->description }}</td>
                <td class="px-3 py-2 text-slate-500 text-xs">{{ $p->reel_width ?? '—' }}</td>
                <td class="px-3 py-2 text-center">
                    <input type="checkbox" {{ $p->is_stock_line ? 'checked' : '' }}
                           onchange="toggleStockLine({{ $p->id }}, this.checked)"
                           class="rounded accent-indigo-600">
                </td>
                @foreach($salesCols as $qty)
                <td class="px-3 py-2 text-right text-slate-600">{{ $qty ?: '—' }}</td>
                @endforeach
                <td class="px-3 py-2 text-right font-medium text-slate-700">{{ $avgMo }}</td>

                {{-- Cut Blank --}}
                <td class="px-3 py-2 border-l border-slate-100 font-semibold text-slate-800 cb-stock-cell" data-id="{{ $p->id }}">{{ number_format($cbStock) }}</td>
                <td class="px-3 py-2">
                    <input type="number" value="{{ $p->cut_blank_reorder_level }}" placeholder="—" min="0"
                           class="w-16 border border-slate-200 rounded px-1.5 py-0.5 text-xs text-center focus:outline-none focus:ring-1 focus:ring-indigo-400"
                           onblur="saveField({{ $p->id }}, 'cut_blank_reorder_level', this.value)">
                </td>
                <td class="px-3 py-2">
                    <input type="number" value="{{ $p->cut_blank_moq }}" placeholder="—" min="0"
                           class="w-16 border border-slate-200 rounded px-1.5 py-0.5 text-xs text-center focus:outline-none focus:ring-1 focus:ring-indigo-400"
                           onblur="saveField({{ $p->id }}, 'cut_blank_moq', this.value)">
                </td>
                <td class="px-3 py-2">@include('collars._status', ['status' => $cbStatus])</td>
                <td class="px-3 py-2">
                    <div class="flex items-center gap-1">
                        <input type="number" placeholder="+/−"
                               class="w-16 border border-slate-200 rounded px-1.5 py-0.5 text-xs text-center focus:outline-none focus:ring-1 focus:ring-indigo-400 cb-adjust-input"
                               data-id="{{ $p->id }}" data-type="cut_blank"
                               onkeydown="if(event.key==='Enter'){applyAdjust(this);}">
                        <button onclick="applyAdjust(this.previousElementSibling)"
                                class="text-xs px-1.5 py-0.5 bg-slate-100 hover:bg-slate-200 rounded transition">✓</button>
                        <button onclick="viewLog({{ $p->id }})" class="text-xs text-slate-400 hover:text-indigo-600 px-1" title="View log">📋</button>
                    </div>
                </td>

                {{-- Made --}}
                <td class="px-3 py-2 border-l border-slate-100 font-semibold text-slate-800">
                    {{ $madeStock !== null ? number_format((float)$madeStock) : '—' }}
                </td>
                <td class="px-3 py-2">
                    <input type="number" value="{{ $p->made_reorder_level }}" placeholder="—" min="0"
                           class="w-16 border border-slate-200 rounded px-1.5 py-0.5 text-xs text-center focus:outline-none focus:ring-1 focus:ring-indigo-400"
                           onblur="saveField({{ $p->id }}, 'made_reorder_level', this.value)">
                </td>
                <td class="px-3 py-2">
                    <input type="number" value="{{ $p->made_moq }}" placeholder="—" min="0"
                           class="w-16 border border-slate-200 rounded px-1.5 py-0.5 text-xs text-center focus:outline-none focus:ring-1 focus:ring-indigo-400"
                           onblur="saveField({{ $p->id }}, 'made_moq', this.value)">
                </td>
                <td class="px-3 py-2">@include('collars._status', ['status' => $madeStatus])</td>
                <td class="px-3 py-2">
                    <button onclick="deleteCollar({{ $p->id }})" class="text-slate-300 hover:text-red-500 transition p-1">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
                        </svg>
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="16" class="px-6 py-12 text-center text-slate-400">No collar products yet. Import a CSV or add one below.</td></tr>
            @endforelse
            </tbody>
        </table>

        {{-- Add product form --}}
        <div class="border-t border-slate-100 bg-slate-50 px-4 py-3">
            <form onsubmit="addCollar(event, this)" class="flex items-center gap-2 flex-wrap">
                <input name="product_code" placeholder="Product code" class="border border-slate-300 rounded px-2 py-1.5 text-sm w-36 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <input name="description" placeholder="Description *" required class="border border-slate-300 rounded px-2 py-1.5 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <input name="reel_width" placeholder="Reel width" class="border border-slate-300 rounded px-2 py-1.5 text-sm w-28 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white text-sm rounded hover:bg-slate-700 transition">Add</button>
            </form>
        </div>
    </div>

</main>

{{-- Adjustment Log Modal --}}
<div id="log-modal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this)closeLogModal()">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800" id="log-modal-title">Adjustment Log</h3>
            <button onclick="closeLogModal()" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <div id="log-modal-body" class="px-5 py-4 max-h-80 overflow-y-auto text-sm text-slate-600">Loading…</div>
    </div>
</div>

{{-- Works Order Modal --}}
<div id="wo-modal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4" onclick="if(event.target===this)closeWoModal()">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 flex-shrink-0">
            <h3 class="font-semibold text-slate-800">New Works Order</h3>
            <button onclick="closeWoModal()" class="text-slate-400 hover:text-slate-600">✕</button>
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
                <label class="block text-xs font-medium text-slate-600 mb-1">Notes</label>
                <textarea id="wo-notes" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
            <p class="text-xs text-slate-500 mb-2">Add lines below — leave qty blank to skip a product.</p>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-100 text-left text-xs">
                        <th class="px-3 py-2">Product</th>
                        <th class="px-3 py-2 text-center">Type</th>
                        <th class="px-3 py-2 text-center w-24">Qty</th>
                        <th class="px-3 py-2">Note</th>
                    </tr>
                </thead>
                <tbody id="wo-lines">
                @foreach($products->where('is_stock_line', true) as $p)
                <tr class="border-t border-slate-100" data-id="{{ $p->id }}">
                    <td class="px-3 py-1.5">
                        <div class="font-medium text-slate-800 text-xs">{{ $p->description }}</div>
                        @if($p->product_code)<div class="text-slate-400 text-xs font-mono">{{ $p->product_code }}</div>@endif
                    </td>
                    <td class="px-3 py-1.5 text-center">
                        <select class="wo-type border border-slate-200 rounded px-1.5 py-0.5 text-xs focus:outline-none">
                            <option value="cut_blank">Cut Blanks</option>
                            <option value="made">Made</option>
                        </select>
                    </td>
                    <td class="px-3 py-1.5 text-center">
                        <input type="number" min="1" class="wo-qty w-20 border border-slate-200 rounded px-1.5 py-0.5 text-xs text-center focus:outline-none focus:ring-1 focus:ring-indigo-400" placeholder="—">
                    </td>
                    <td class="px-3 py-1.5">
                        <input type="text" class="wo-note w-full border border-slate-200 rounded px-1.5 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400" placeholder="Optional note">
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex justify-end gap-3 flex-shrink-0">
            <button onclick="closeWoModal()" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">Cancel</button>
            <button onclick="saveWorksOrder()" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Save Works Order</button>
        </div>
    </div>
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

function saveField(id, field, value) {
    fetch(`/collars/${id}`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ [field]: value === '' ? null : parseInt(value) }),
    });
}

function toggleStockLine(id, checked) {
    fetch(`/collars/${id}`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ is_stock_line: checked }),
    });
}

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
    document.getElementById('log-modal').classList.replace('hidden', 'flex');
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
function closeLogModal() { document.getElementById('log-modal').classList.replace('flex', 'hidden'); }

function deleteCollar(id) {
    if (!confirm('Delete this product?')) return;
    fetch(`/collars/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken } })
    .then(r => r.json()).then(d => { if (d.ok) location.reload(); });
}

function addCollar(e, form) {
    e.preventDefault();
    const data = { product_code: form.product_code.value.trim() || null, description: form.description.value.trim(), reel_width: form.reel_width.value.trim() || null };
    if (!data.description) return;
    fetch('/collars', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(data) })
    .then(r => r.json()).then(() => location.reload());
}

function importCsv(input) {
    const form = new FormData(); form.append('file', input.files[0]);
    fetch('/collars/import', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: form })
    .then(r => r.json()).then(d => { if (d.ok) { alert(`Imported ${d.imported} products.`); location.reload(); } else alert('Import failed: ' + (d.error || 'Unknown error')); });
}

// Works Order Modal
function openWorksOrderModal() {
    document.getElementById('wo-title').value = '';
    document.getElementById('wo-modal').classList.replace('hidden', 'flex');
}
function closeWoModal() { document.getElementById('wo-modal').classList.replace('flex', 'hidden'); }

function saveWorksOrder() {
    const title  = document.getElementById('wo-title').value.trim();
    const period = document.getElementById('wo-period').value + '-01';
    const notes  = document.getElementById('wo-notes').value.trim();
    if (!title) { alert('Please enter a title.'); return; }

    const lines = [];
    document.querySelectorAll('#wo-lines tr[data-id]').forEach(row => {
        const qty = parseInt(row.querySelector('.wo-qty').value);
        if (!qty || qty < 1) return;
        lines.push({
            collar_product_id: parseInt(row.dataset.id),
            type: row.querySelector('.wo-type').value,
            qty,
            note: row.querySelector('.wo-note').value.trim() || null,
        });
    });
    if (!lines.length) { alert('Add at least one line with a quantity.'); return; }

    fetch('/collars/works-orders', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ title, period, notes, lines }),
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) { closeWoModal(); window.open(`/collars/works-orders/${d.id}`, '_blank'); location.reload(); }
        else alert('Failed: ' + (d.message || 'Unknown error'));
    });
}

function deleteWorksOrder(id) {
    if (!confirm('Delete this works order?')) return;
    fetch(`/collars/works-orders/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken } })
    .then(r => r.json()).then(d => { if (d.ok) location.reload(); });
}
</script>
</x-layout>
