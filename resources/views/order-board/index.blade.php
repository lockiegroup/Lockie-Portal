<x-layout title="Order Board — JW Products">
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Order Board</h1>
            <p class="text-slate-500 mt-0.5 text-sm">Live open orders — updates automatically every 60 seconds.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Warehouse filter --}}
            <select id="warehouse-select" onchange="reload()"
                class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-700 outline-none focus:ring-2 focus:ring-sky-500">
                <option value="JW Products">JW Products</option>
                <option value="Hammond &amp; Harper">Hammond &amp; Harper</option>
            </select>
            {{-- Status filter --}}
            <select id="status-filter" onchange="reload()"
                class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-700 outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">All statuses</option>
                @foreach($statuses as $key => $s)
                <option value="{{ $key }}">{{ $s['label'] }}</option>
                @endforeach
            </select>
            <button onclick="reload()"
                class="px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Refresh
            </button>
            <a href="{{ route('order-board.settings') }}"
                class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-semibold rounded-lg hover:bg-slate-50 transition-colors">
                ⚙ Settings
            </a>
        </div>
    </div>

    {{-- Status legend --}}
    <div class="flex flex-wrap gap-3 mb-6">
        @foreach($statuses as $key => $s)
        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600">
            <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $s['color'] }}"></span>
            {{ $s['label'] }}
        </span>
        @endforeach
        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400">
            <span class="w-2.5 h-2.5 rounded-full bg-slate-200"></span>
            No status set
        </span>
    </div>

    {{-- Loading --}}
    <div id="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
            <div class="inline-block w-8 h-8 border-4 border-slate-200 border-t-sky-500 rounded-full animate-spin mb-3"></div>
            <p class="text-slate-400 text-sm">Loading orders…</p>
        </div>
    </div>

    {{-- Error --}}
    <div id="error" class="hidden text-red-500 text-sm py-6 text-center"></div>

    {{-- Order count --}}
    <div id="order-count" class="hidden text-xs text-slate-400 mb-3"></div>

    {{-- Table --}}
    <div id="table-wrap" class="hidden bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse" style="min-width:800px;">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[0.72rem] text-slate-500 uppercase tracking-wide">
                        <th class="text-left px-4 py-3 font-semibold">Order</th>
                        <th class="text-left px-4 py-3 font-semibold">Customer</th>
                        <th class="text-left px-4 py-3 font-semibold">Order Date</th>
                        <th class="text-left px-4 py-3 font-semibold">Required</th>
                        <th class="text-right px-4 py-3 font-semibold">Value</th>
                        <th class="text-left px-4 py-3 font-semibold">Notes</th>
                        <th class="px-4 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody id="order-tbody"></tbody>
            </table>
        </div>
    </div>

    <div id="empty" class="hidden text-center text-slate-400 text-sm py-16">No open orders found.</div>

</main>

{{-- Edit modal --}}
<div id="modal-backdrop" class="hidden fixed inset-0 bg-black/40 z-40 flex items-center justify-center p-4" onclick="closeModal(event)">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 relative" onclick="event.stopPropagation()">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-lg leading-none">✕</button>
        <h2 class="font-bold text-slate-800 text-lg mb-1" id="modal-order-no"></h2>
        <p class="text-slate-500 text-sm mb-5" id="modal-customer"></p>

        <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
        <select id="modal-status"
            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white outline-none focus:ring-2 focus:ring-sky-500 mb-4">
            <option value="">— No status —</option>
        </select>

        <label class="block text-xs font-semibold text-slate-500 mb-1">Notes</label>
        <textarea id="modal-notes" rows="4"
            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500 resize-none mb-5"
            placeholder="e.g. Waiting on artwork from customer…"></textarea>

        <div class="flex gap-3">
            <button onclick="saveModal()"
                class="flex-1 px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Save
            </button>
            <button onclick="closeModal()"
                class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-semibold rounded-lg hover:bg-slate-50 transition-colors">
                Cancel
            </button>
        </div>
        <p id="modal-error" class="hidden text-red-500 text-xs mt-3"></p>
    </div>
</div>

<script>
const STATUSES = @json($statuses);
const STATUS_KEYS = Object.keys(STATUSES);

let allOrders  = [];
let editingOrder = null;
let reloadTimer  = null;

function fmtGBP(n) {
    return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', maximumFractionDigits: 0 }).format(n);
}

function fmtDate(d) {
    if (!d) return '—';
    const [y, m, day] = d.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return day + ' ' + months[parseInt(m) - 1];
}

function isOverdue(required) {
    if (!required) return false;
    return new Date(required) < new Date(new Date().toDateString());
}

function escH(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function reload() {
    clearTimeout(reloadTimer);
    loadOrders();
}

function loadOrders() {
    const warehouse = document.getElementById('warehouse-select').value;
    const status    = document.getElementById('status-filter').value;

    document.getElementById('loading').classList.remove('hidden');
    document.getElementById('table-wrap').classList.add('hidden');
    document.getElementById('empty').classList.add('hidden');
    document.getElementById('error').classList.add('hidden');
    document.getElementById('order-count').classList.add('hidden');

    const params = new URLSearchParams({ warehouse });
    if (status) params.set('status', status);

    fetch('/order-board/data?' + params, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(res => {
            document.getElementById('loading').classList.add('hidden');
            if (!res.ok) throw new Error(res.error || 'Server error');
            allOrders = res.orders;
            renderTable(res.orders);
            reloadTimer = setTimeout(loadOrders, 60000);
        })
        .catch(err => {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error').textContent = 'Failed to load orders: ' + err.message;
            document.getElementById('error').classList.remove('hidden');
        });
}

function orderRow(o) {
    const overdue   = isOverdue(o.required_date) ? ' style="color:#ef4444;font-weight:600;"' : '';
    const notesHtml = o.notes
        ? `<span class="text-slate-600 text-xs">${escH(o.notes)}</span>`
        : `<span class="text-slate-300 text-xs">—</span>`;
    const tr = document.createElement('tr');
    tr.className = 'border-b border-slate-50 hover:bg-slate-50/60 transition-colors';
    tr.innerHTML = `
        <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">${escH(o.order_no)}</td>
        <td class="px-4 py-3 text-slate-700 whitespace-nowrap">${escH(o.customer)}</td>
        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">${fmtDate(o.order_date)}</td>
        <td class="px-4 py-3 whitespace-nowrap"${overdue}>${fmtDate(o.required_date)}</td>
        <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap font-medium">${fmtGBP(o.total_value)}</td>
        <td class="px-4 py-3 max-w-xs">${notesHtml}</td>
        <td class="px-4 py-3">
            <button onclick="openModal('${escH(o.order_no)}')"
                class="px-2.5 py-1 text-xs font-semibold text-slate-500 border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors whitespace-nowrap">
                Edit
            </button>
        </td>`;
    return tr;
}

function groupHeaderRow(label, color, count) {
    const tr = document.createElement('tr');
    tr.className = 'border-b border-slate-100';
    tr.style.background = color + '18'; // 10% opacity tint
    tr.innerHTML = `
        <td colspan="7" class="px-4 py-2">
            <span class="inline-flex items-center gap-2 font-semibold text-sm" style="color:${color}">
                <span class="w-2.5 h-2.5 rounded-full" style="background:${color}"></span>
                ${escH(label)}
                <span class="font-normal text-xs opacity-70">${count} order${count !== 1 ? 's' : ''}</span>
            </span>
        </td>`;
    return tr;
}

function renderTable(orders) {
    const countEl = document.getElementById('order-count');
    countEl.textContent = orders.length + ' open order' + (orders.length !== 1 ? 's' : '');
    countEl.classList.remove('hidden');

    if (!orders.length) {
        document.getElementById('empty').classList.remove('hidden');
        return;
    }

    const tbody = document.getElementById('order-tbody');
    tbody.innerHTML = '';

    // Group by board_status, preserving defined status order, unset at end
    const groups = {};
    STATUS_KEYS.forEach(k => { groups[k] = []; });
    groups['__none'] = [];

    orders.forEach(o => {
        const key = o.board_status && groups[o.board_status] !== undefined ? o.board_status : '__none';
        groups[key].push(o);
    });

    const filterActive = document.getElementById('status-filter').value;

    [...STATUS_KEYS, '__none'].forEach(key => {
        const group = groups[key];
        if (!group.length) return;

        const isNone  = key === '__none';
        const label   = isNone ? 'No Status' : STATUSES[key].label;
        const color   = isNone ? '#94a3b8'   : STATUSES[key].color;

        // Only show header when not already filtered to one status
        if (!filterActive) {
            tbody.appendChild(groupHeaderRow(label, color, group.length));
        }

        group.forEach(o => tbody.appendChild(orderRow(o)));
    });

    document.getElementById('table-wrap').classList.remove('hidden');
}

function openModal(orderNo) {
    const o = allOrders.find(x => x.order_no === orderNo);
    if (!o) return;

    clearTimeout(reloadTimer); // pause auto-refresh while modal is open
    editingOrder = o;

    document.getElementById('modal-order-no').textContent = o.order_no;
    document.getElementById('modal-customer').textContent  = o.customer;
    document.getElementById('modal-error').classList.add('hidden');

    // Populate status select
    const sel = document.getElementById('modal-status');
    sel.innerHTML = '<option value="">— No status —</option>';
    STATUS_KEYS.forEach(key => {
        const opt = document.createElement('option');
        opt.value       = key;
        opt.textContent = STATUSES[key].label;
        if (o.board_status === key) opt.selected = true;
        sel.appendChild(opt);
    });

    document.getElementById('modal-notes').value = o.notes || '';
    document.getElementById('modal-backdrop').classList.remove('hidden');
    document.getElementById('modal-notes').focus();
}

function closeModal(e) {
    if (e && e.target !== document.getElementById('modal-backdrop')) return;
    document.getElementById('modal-backdrop').classList.add('hidden');
    editingOrder = null;
    reloadTimer = setTimeout(loadOrders, 60000); // resume auto-refresh
}

function saveModal() {
    if (!editingOrder) return;

    const status = document.getElementById('modal-status').value;
    const notes  = document.getElementById('modal-notes').value.trim();
    const errEl  = document.getElementById('modal-error');
    errEl.classList.add('hidden');

    fetch('/order-board/' + encodeURIComponent(editingOrder.order_no), {
        method:  'POST',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ board_status: status, notes }),
    })
    .then(r => r.json())
    .then(res => {
        if (!res.ok) throw new Error('Save failed');
        // Update local data
        editingOrder.board_status = status || null;
        editingOrder.notes        = notes;
        renderTable(allOrders);
        document.getElementById('modal-backdrop').classList.add('hidden');
        editingOrder = null;
    })
    .catch(err => {
        errEl.textContent = 'Failed to save: ' + err.message;
        errEl.classList.remove('hidden');
    });
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('modal-backdrop').classList.add('hidden');
        editingOrder = null;
        reloadTimer = setTimeout(loadOrders, 60000);
    }
});

document.addEventListener('DOMContentLoaded', loadOrders);
</script>
</x-layout>
