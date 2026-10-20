<x-layout title="Settings — Clocking">
<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800 mb-4">Clocking</h1>
        @include('clocking._tabs', ['active' => 'settings'])
    </div>

    {{-- Alert timing --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <h2 class="text-[0.9375rem] font-bold text-slate-800 mb-2">Alert timing</h2>
        <p class="text-[0.8125rem] text-slate-500 mb-4">Send an alert this many minutes after an employee's scheduled start if they haven't clocked in.</p>
        <div class="flex items-center gap-3 flex-wrap">
            <input type="number" id="grace-mins" value="{{ $graceMins }}" min="0" max="120"
                class="w-20 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
            <span class="text-sm text-slate-600">minutes</span>
            <button onclick="saveGrace()"
                class="px-4 py-2 bg-indigo-500 text-white border-0 rounded-lg text-[0.8125rem] font-semibold cursor-pointer hover:bg-indigo-600 transition-colors">
                Save
            </button>
            <span id="grace-result" class="text-[0.8125rem]"></span>
        </div>
    </div>

    {{-- Break time --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <h2 class="text-[0.9375rem] font-bold text-slate-800 mb-2">Default break time</h2>
        <p class="text-[0.8125rem] text-slate-500 mb-4">Minutes automatically deducted from each employee's hours on days they work. Individual employees can override this on the Employees page.</p>
        <div class="flex items-center gap-3 flex-wrap">
            <input type="number" id="break-mins" value="{{ $defaultBreakMins }}" min="0" max="180"
                class="w-20 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
            <span class="text-sm text-slate-600">minutes</span>
            <button onclick="saveBreak()"
                class="px-4 py-2 bg-indigo-500 text-white border-0 rounded-lg text-[0.8125rem] font-semibold cursor-pointer hover:bg-indigo-600 transition-colors">
                Save
            </button>
            <span id="break-result" class="text-[0.8125rem]"></span>
        </div>
    </div>

    {{-- Global recipients --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <h2 class="text-[0.9375rem] font-bold text-slate-800 mb-1">Global alert recipients</h2>
        <p class="text-[0.8125rem] text-slate-500 mb-4">These addresses receive alerts for <strong>all employees</strong>, regardless of category.</p>

        <div id="global-list">
            @foreach($globalRecipients as $r)
            <div id="recipient-{{ $r->id }}" class="flex items-center gap-3 py-1.5 border-b border-slate-50">
                <span class="flex-1 text-sm text-slate-800">{{ $r->email }}</span>
                @if($r->label)<span class="text-xs text-slate-400">{{ $r->label }}</span>@endif
                <button onclick="removeRecipient({{ $r->id }})"
                    class="px-2.5 py-1 border border-red-200 text-red-500 text-xs rounded-md bg-transparent cursor-pointer">Remove</button>
            </div>
            @endforeach
        </div>
        <div class="mt-3">
            @include('clocking._recipient-form', ['categoryId' => 'null', 'formId' => 'global'])
        </div>
    </div>

    {{-- Categories --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-start justify-between gap-3 flex-wrap mb-4">
            <div>
                <h2 class="text-[0.9375rem] font-bold text-slate-800 mb-1">Categories</h2>
                <p class="text-[0.8125rem] text-slate-500">Group employees so managers only receive alerts for their team.</p>
            </div>
            <button onclick="toggleAddCategory()"
                class="px-3.5 py-2 bg-slate-900 text-white border-0 rounded-lg text-[0.8125rem] font-semibold cursor-pointer hover:bg-slate-700 transition-colors flex-shrink-0">
                + Add Category
            </button>
        </div>

        <div id="add-category-form" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-4 mb-4">
            <div class="flex gap-2 items-end flex-wrap">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Name</label>
                    <input type="text" id="new-cat-name" maxlength="80" placeholder="e.g. Factory"
                        class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 w-full max-w-[180px]">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Colour</label>
                    <input type="color" id="new-cat-color" value="#6366f1"
                        class="w-10 h-9 border border-slate-300 rounded-lg p-0.5 cursor-pointer">
                </div>
                <button onclick="addCategory()"
                    class="px-4 py-2 bg-indigo-500 text-white border-0 rounded-lg text-[0.8125rem] font-semibold cursor-pointer hover:bg-indigo-600 transition-colors">
                    Add
                </button>
                <span id="add-cat-result" class="text-[0.8125rem]"></span>
            </div>
        </div>

        <div id="cat-list">
        @forelse($categories as $cat)
        <div id="cat-{{ $cat->id }}" class="border border-slate-200 rounded-xl p-4 mb-3">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-3.5 h-3.5 rounded-full flex-shrink-0" style="background:{{ $cat->color }}"></div>
                <span id="cat-name-{{ $cat->id }}" class="text-[0.9375rem] font-bold text-slate-800 flex-1">{{ $cat->name }}</span>
                <button onclick="startRenameCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->color }}')"
                    class="px-2.5 py-1 border border-slate-200 text-slate-600 text-xs rounded-md bg-transparent cursor-pointer">Rename</button>
                <button onclick="deleteCategory({{ $cat->id }})"
                    class="px-2.5 py-1 border border-red-200 text-red-500 text-xs rounded-md bg-transparent cursor-pointer">Delete</button>
            </div>
            {{-- Rename inline form (hidden) --}}
            <div id="cat-rename-{{ $cat->id }}" class="hidden mb-3">
                <div class="flex gap-2 items-center flex-wrap">
                    <input type="text" id="cat-rename-name-{{ $cat->id }}" maxlength="80"
                        class="px-2.5 py-1.5 border border-indigo-400 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 w-full max-w-[160px]">
                    <input type="color" id="cat-rename-color-{{ $cat->id }}"
                        class="w-9 h-8 border border-slate-300 rounded-md p-0.5 cursor-pointer">
                    <button onclick="saveRenameCategory({{ $cat->id }})"
                        class="px-3 py-1.5 bg-indigo-500 text-white border-0 rounded-md text-xs cursor-pointer hover:bg-indigo-600 transition-colors">Save</button>
                    <button onclick="cancelRename({{ $cat->id }})"
                        class="px-2.5 py-1.5 border border-slate-200 text-slate-500 text-xs rounded-md bg-transparent cursor-pointer">Cancel</button>
                </div>
            </div>
            {{-- Recipients --}}
            <div class="ml-6">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Alert recipients</p>
                <div id="cat-recipients-{{ $cat->id }}">
                    @foreach($cat->alertRecipients as $r)
                    <div id="recipient-{{ $r->id }}" class="flex items-center gap-2 py-1">
                        <span class="flex-1 text-sm text-slate-800">{{ $r->email }}</span>
                        @if($r->label)<span class="text-xs text-slate-400">{{ $r->label }}</span>@endif
                        <button onclick="removeRecipient({{ $r->id }})"
                            class="px-2 py-0.5 border border-red-200 text-red-500 text-[0.7rem] rounded-md bg-transparent cursor-pointer">×</button>
                    </div>
                    @endforeach
                </div>
                @include('clocking._recipient-form', ['categoryId' => $cat->id, 'formId' => 'cat-'.$cat->id])
            </div>
        </div>
        @empty
        <p class="text-slate-400 text-sm">No categories yet.</p>
        @endforelse
        </div>
    </div>

</main>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Grace period ──────────────────────────────────────────────────────────────
async function saveGrace() {
    const mins = document.getElementById('grace-mins').value;
    const res  = document.getElementById('grace-result');
    const r = await fetch('/clocking/settings', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ alert_grace_minutes: parseInt(mins) }),
    });
    res.innerHTML = r.ok ? '<span style="color:#22c55e">Saved</span>' : '<span style="color:#ef4444">Error</span>';
    setTimeout(() => res.textContent = '', 2000);
}

// ── Break time ────────────────────────────────────────────────────────────────
async function saveBreak() {
    const mins = document.getElementById('break-mins').value;
    const res  = document.getElementById('break-result');
    const r = await fetch('/clocking/settings', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ default_break_minutes: parseInt(mins) }),
    });
    res.innerHTML = r.ok ? '<span style="color:#22c55e">Saved</span>' : '<span style="color:#ef4444">Error</span>';
    setTimeout(() => res.textContent = '', 2000);
}

// ── Categories ────────────────────────────────────────────────────────────────
function toggleAddCategory() {
    const f = document.getElementById('add-category-form');
    f.classList.toggle('hidden');
}

async function addCategory() {
    const name  = document.getElementById('new-cat-name').value.trim();
    const color = document.getElementById('new-cat-color').value;
    const res   = document.getElementById('add-cat-result');
    if (!name) { res.innerHTML = '<span style="color:#ef4444">Name required.</span>'; return; }
    const r = await fetch('/clocking/settings/categories', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, color }),
    });
    if (r.ok) location.reload();
    else res.innerHTML = '<span style="color:#ef4444">Error.</span>';
}

function startRenameCategory(id, name, color) {
    document.getElementById('cat-rename-' + id).classList.remove('hidden');
    document.getElementById('cat-rename-' + id).style.display = 'flex';
    document.getElementById('cat-rename-name-' + id).value  = name;
    document.getElementById('cat-rename-color-' + id).value = color;
}
function cancelRename(id) {
    document.getElementById('cat-rename-' + id).classList.add('hidden');
}

async function saveRenameCategory(id) {
    const name  = document.getElementById('cat-rename-name-' + id).value.trim();
    const color = document.getElementById('cat-rename-color-' + id).value;
    if (!name) return;
    const r = await fetch(`/clocking/settings/categories/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, color }),
    });
    if (r.ok) location.reload();
}

async function deleteCategory(id) {
    if (!confirm('Delete this category? Employees in it will become uncategorised.')) return;
    const r = await fetch(`/clocking/settings/categories/${id}`, {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
    });
    if (r.ok) document.getElementById('cat-' + id)?.remove();
}

// ── Recipients ────────────────────────────────────────────────────────────────
async function addRecipient(categoryId, formId) {
    const email = document.getElementById('email-' + formId).value.trim();
    const label = document.getElementById('label-' + formId).value.trim();
    const res   = document.getElementById('res-' + formId);
    if (!email) { res.innerHTML = '<span style="color:#ef4444">Email required.</span>'; return; }
    const r = await fetch('/clocking/settings/recipients', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ category_id: categoryId === 'null' ? null : categoryId, email, label: label || null }),
    });
    if (r.ok) location.reload();
    else {
        let msg = 'Error.';
        try { const j = await r.json(); msg = j.message || Object.values(j.errors||{})[0]?.[0] || msg; } catch(_){}
        res.innerHTML = `<span style="color:#ef4444">${msg}</span>`;
    }
}

async function removeRecipient(id) {
    const r = await fetch(`/clocking/settings/recipients/${id}`, {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
    });
    if (r.ok) document.getElementById('recipient-' + id)?.remove();
}
</script>
</x-layout>
