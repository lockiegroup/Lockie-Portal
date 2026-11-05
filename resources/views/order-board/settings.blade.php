<x-layout title="Order Board Settings">
<main class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Order Board Settings</h1>
            <p class="text-slate-500 mt-0.5 text-sm">Manage the status categories shown on the order board.</p>
        </div>
        <a href="{{ route('order-board.index') }}"
            class="text-sm text-slate-500 hover:text-slate-800 transition-colors">← Back to board</a>
    </div>

    {{-- Status list --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Statuses</h2>
            <p class="text-xs text-slate-400 mt-0.5">Drag to reorder. Changes take effect immediately.</p>
        </div>

        <ul id="status-list" class="divide-y divide-slate-100">
            @foreach($statuses as $s)
            <li class="flex items-center gap-3 px-5 py-3 group" data-id="{{ $s->id }}">
                <span class="cursor-grab text-slate-300 hover:text-slate-500 select-none drag-handle text-lg leading-none">⠿</span>
                <input type="color" value="{{ $s->color }}"
                    class="w-8 h-8 rounded-lg border border-slate-200 cursor-pointer p-0.5 color-input"
                    data-id="{{ $s->id }}">
                <input type="text" value="{{ $s->label }}"
                    class="flex-1 px-3 py-1.5 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500 label-input"
                    data-id="{{ $s->id }}">
                <button onclick="saveStatus({{ $s->id }})"
                    class="px-3 py-1.5 text-xs font-semibold bg-slate-900 text-white rounded-lg hover:bg-slate-700 transition-colors whitespace-nowrap">
                    Save
                </button>
                <button onclick="deleteStatus({{ $s->id }}, '{{ addslashes($s->label) }}')"
                    class="px-3 py-1.5 text-xs font-semibold text-red-500 border border-red-200 rounded-lg hover:bg-red-50 transition-colors whitespace-nowrap">
                    Delete
                </button>
            </li>
            @endforeach
        </ul>

        @if($statuses->isEmpty())
        <p class="px-5 py-6 text-sm text-slate-400">No statuses yet. Add one below.</p>
        @endif
    </div>

    {{-- Add new --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Add New Status</h2>
        <div class="flex items-center gap-3">
            <input type="color" id="new-color" value="#6366f1"
                class="w-10 h-10 rounded-lg border border-slate-200 cursor-pointer p-0.5">
            <input type="text" id="new-label" placeholder="Status name…"
                class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500">
            <button onclick="addStatus()"
                class="px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors whitespace-nowrap">
                + Add
            </button>
        </div>
        <p id="add-error" class="hidden text-red-500 text-xs mt-2"></p>
    </div>

</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function api(url, method, body) {
    return fetch(url, {
        method,
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : undefined,
    }).then(r => r.json());
}

function saveStatus(id) {
    const label = document.querySelector(`.label-input[data-id="${id}"]`).value.trim();
    const color = document.querySelector(`.color-input[data-id="${id}"]`).value;

    if (!label) return alert('Please enter a label.');

    api(`/order-board/settings/statuses/${id}`, 'PUT', { label, color })
        .then(res => {
            if (!res.ok) throw new Error('Save failed');
            // Update the dot preview color
            document.querySelector(`.color-input[data-id="${id}"]`).value = color;
        })
        .catch(err => alert('Failed to save: ' + err.message));
}

function deleteStatus(id, label) {
    if (!confirm(`Delete "${label}"? Any orders using this status will be set to No Status.`)) return;

    api(`/order-board/settings/statuses/${id}`, 'DELETE')
        .then(res => {
            if (!res.ok) throw new Error('Delete failed');
            document.querySelector(`#status-list li[data-id="${id}"]`)?.remove();
        })
        .catch(err => alert('Failed to delete: ' + err.message));
}

function addStatus() {
    const label   = document.getElementById('new-label').value.trim();
    const color   = document.getElementById('new-color').value;
    const errEl   = document.getElementById('add-error');
    errEl.classList.add('hidden');

    if (!label) {
        errEl.textContent = 'Please enter a name.';
        errEl.classList.remove('hidden');
        return;
    }

    api('/order-board/settings/statuses', 'POST', { label, color })
        .then(res => {
            if (!res.ok) throw new Error('Failed to add');
            const s = res.status;
            const li = document.createElement('li');
            li.className = 'flex items-center gap-3 px-5 py-3 group';
            li.dataset.id = s.id;
            li.innerHTML = `
                <span class="cursor-grab text-slate-300 hover:text-slate-500 select-none drag-handle text-lg leading-none">⠿</span>
                <input type="color" value="${s.color}"
                    class="w-8 h-8 rounded-lg border border-slate-200 cursor-pointer p-0.5 color-input"
                    data-id="${s.id}">
                <input type="text" value="${s.label}"
                    class="flex-1 px-3 py-1.5 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500 label-input"
                    data-id="${s.id}">
                <button onclick="saveStatus(${s.id})"
                    class="px-3 py-1.5 text-xs font-semibold bg-slate-900 text-white rounded-lg hover:bg-slate-700 transition-colors whitespace-nowrap">
                    Save
                </button>
                <button onclick="deleteStatus(${s.id}, '${s.label.replace(/'/g, "\\'")}')"
                    class="px-3 py-1.5 text-xs font-semibold text-red-500 border border-red-200 rounded-lg hover:bg-red-50 transition-colors whitespace-nowrap">
                    Delete
                </button>`;
            document.getElementById('status-list').appendChild(li);
            document.getElementById('new-label').value = '';
            document.getElementById('new-color').value = '#6366f1';
        })
        .catch(err => {
            errEl.textContent = err.message;
            errEl.classList.remove('hidden');
        });
}

// Drag to reorder
Sortable.create(document.getElementById('status-list'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd() {
        const order = [...document.querySelectorAll('#status-list li[data-id]')]
            .map(li => parseInt(li.dataset.id));
        api('/order-board/settings/statuses/reorder', 'POST', { order });
    },
});

document.getElementById('new-label').addEventListener('keydown', e => {
    if (e.key === 'Enter') addStatus();
});
</script>
</x-layout>
