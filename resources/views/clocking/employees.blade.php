<x-layout title="Employees — Clocking">
<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800 mb-4">Clocking</h1>
        @include('clocking._tabs', ['active' => 'employees'])
    </div>

    {{-- Add employee --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <h2 class="text-[0.9375rem] font-bold text-slate-800">Employees</h2>
            <button onclick="toggleAddForm()"
                class="px-3.5 py-1.5 bg-slate-900 text-white border-0 rounded-lg text-[0.8125rem] font-semibold cursor-pointer hover:bg-slate-700 transition-colors">
                + Add Employee
            </button>
        </div>

        <div id="add-form" style="display:none" class="mt-4 bg-slate-50 border border-slate-200 rounded-lg p-5">
            <p class="text-[0.8125rem] text-slate-500 mb-4">Fill in the employee's name and their weekly schedule.</p>
            <div class="flex flex-wrap gap-3 mb-3">
                <div>
                    <label class="text-[0.8125rem] font-semibold text-gray-700 block mb-1">Name</label>
                    <input type="text" id="add-name" placeholder="Full name" maxlength="100"
                        class="w-full min-w-[220px] px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>
                @if($categories->isNotEmpty())
                <div>
                    <label class="text-[0.8125rem] font-semibold text-gray-700 block mb-1">Category</label>
                    <select id="add-category" class="px-3 py-2 border border-slate-200 rounded-lg text-sm min-w-[160px] focus:outline-none">
                        <option value="">— none —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="text-[0.8125rem] font-semibold text-gray-700 block mb-1">
                        Fob UID <span class="font-normal text-slate-400">— type or tap reader</span>
                    </label>
                    <div class="flex gap-1.5 items-center">
                        <input type="text" id="add-fob" maxlength="20" placeholder="e.g. 0012345678" inputmode="numeric"
                            class="w-40 px-3 py-2 border border-slate-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <button type="button" onclick="startCapture('add-fob')"
                            class="px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-600 cursor-pointer hover:bg-slate-200 transition-colors">
                            📡 Tap
                        </button>
                    </div>
                </div>
            </div>
            <div id="add-schedule" class="mb-3"></div>
            <div class="mb-4">
                <label class="text-[0.8125rem] font-semibold text-gray-700 block mb-1">
                    Break override (minutes) <span class="font-normal text-slate-400">— leave blank to use default</span>
                </label>
                <input type="number" id="add-break" min="0" max="120" placeholder="e.g. 30"
                    class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-rose-500">
            </div>
            <div class="flex gap-2">
                <button onclick="saveEmployee()"
                    class="px-5 py-2 bg-indigo-500 text-white border-0 rounded-lg text-sm font-semibold cursor-pointer hover:bg-indigo-600 transition-colors">
                    Save
                </button>
                <button onclick="toggleAddForm()"
                    class="px-4 py-2 bg-transparent border border-slate-200 rounded-lg text-sm text-slate-500 cursor-pointer hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
            </div>
            <div id="add-result" class="mt-2 text-[0.8125rem]"></div>
        </div>
    </div>

    {{-- Employee list --}}
    <div class="bg-white rounded-xl border border-slate-200">
        @if($employees->isEmpty())
            <p class="p-6 text-slate-400 text-sm">No employees yet. Add one above.</p>
        @else
        <div class="overflow-x-auto rounded-xl">
        <table class="w-full border-collapse text-sm" id="emp-table">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-2.5 text-xs uppercase tracking-wide text-slate-500 font-semibold">Name</th>
                    @if($categories->isNotEmpty())
                    <th class="text-left px-3 py-2.5 text-xs uppercase tracking-wide text-slate-500 font-semibold">Category</th>
                    @endif
                    <th class="emp-col-schedule-hd text-left px-3 py-2.5 text-xs uppercase tracking-wide text-slate-500 font-semibold">Schedule</th>
                    <th class="emp-col-fob-hd text-left px-3 py-2.5 text-xs uppercase tracking-wide text-slate-500 font-semibold">Fob</th>
                    <th class="text-left px-3 py-2.5 text-xs uppercase tracking-wide text-slate-500 font-semibold">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($employees as $emp)
            @php
                $sched  = $emp->schedule ?? [];
                $days   = collect(['mon','tue','wed','thu','fri','sat','sun'])
                    ->filter(fn($d) => !empty($sched[$d]['start']))
                    ->map(fn($d) => ucfirst($d))
                    ->implode(', ');
                $fob = $emp->fobs->first();
            @endphp
            <tr class="border-b border-slate-50" id="emp-row-{{ $emp->id }}">
                <td class="px-4 py-3 font-semibold text-slate-800">{{ $emp->name }}</td>
                @if($categories->isNotEmpty())
                <td class="px-3 py-3">
                    @if($emp->category)
                        <span class="inline-flex items-center gap-1.5 text-[0.8125rem] text-slate-600">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $emp->category->color }};"></span>
                            {{ $emp->category->name }}
                        </span>
                    @else
                        <span class="text-slate-300 text-[0.8125rem]">—</span>
                    @endif
                </td>
                @endif
                <td class="emp-col-schedule px-3 py-3 text-slate-600">{{ $days ?: '—' }}</td>
                <td class="emp-col-fob px-3 py-3 font-mono text-slate-500">{{ $fob?->uid ?? '—' }}</td>
                <td class="px-3 py-3">
                    @if($emp->is_active)
                        <span class="bg-green-100 text-green-800 px-2 py-px rounded-full text-[0.72rem] font-semibold">Active</span>
                    @else
                        <span class="bg-slate-100 text-slate-400 px-2 py-px rounded-full text-[0.72rem] font-semibold">Inactive</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <button onclick="openEdit({{ $emp->id }})"
                        class="px-2.5 py-0.5 bg-transparent border border-slate-200 rounded-md text-slate-600 text-xs cursor-pointer mr-1 hover:bg-slate-50 transition-colors">
                        Edit
                    </button>
                    <button onclick="toggleActive({{ $emp->id }}, {{ $emp->is_active ? 'true' : 'false' }})"
                        class="px-2.5 py-0.5 bg-transparent border rounded-md text-xs cursor-pointer transition-colors {{ $emp->is_active ? 'border-red-200 text-red-500 hover:bg-red-50' : 'border-slate-200 text-slate-500 hover:bg-slate-50' }}">
                        {{ $emp->is_active ? 'Deactivate' : 'Reactivate' }}
                    </button>
                    <button onclick="deleteEmployee({{ $emp->id }}, '{{ addslashes($emp->name) }}')"
                        class="px-2.5 py-0.5 bg-transparent border border-red-200 rounded-md text-red-600 text-xs cursor-pointer ml-1 hover:bg-red-50 transition-colors"
                        title="Permanently delete (history is kept)">
                        Delete
                    </button>
                </td>
            </tr>
            {{-- Edit row (hidden) --}}
            <tr id="emp-edit-{{ $emp->id }}" style="display:none;">
                <td colspan="5" class="p-0 bg-slate-50 border-b border-slate-200">
                    <div class="p-4">
                        <div class="flex flex-wrap gap-3 mb-3">
                            <div>
                                <label class="text-[0.8125rem] font-semibold text-gray-700 block mb-1">Name</label>
                                <input type="text" id="edit-name-{{ $emp->id }}" value="{{ $emp->name }}" maxlength="100"
                                    class="w-full min-w-[220px] px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-rose-500">
                            </div>
                            @if($categories->isNotEmpty())
                            <div>
                                <label class="text-[0.8125rem] font-semibold text-gray-700 block mb-1">Category</label>
                                <select id="edit-category-{{ $emp->id }}" class="px-3 py-2 border border-slate-200 rounded-lg text-sm min-w-[160px] focus:outline-none">
                                    <option value="">— none —</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $emp->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div>
                                <label class="text-[0.8125rem] font-semibold text-gray-700 block mb-1">
                                    Fob UID <span class="font-normal text-slate-400">— type or tap reader</span>
                                </label>
                                <div class="flex gap-1.5 items-center">
                                    <input type="text" id="edit-fob-{{ $emp->id }}" maxlength="20" inputmode="numeric"
                                        value="{{ $fob?->uid ?? '' }}" placeholder="none assigned"
                                        class="w-40 px-3 py-2 border border-slate-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-rose-500">
                                    <button type="button" onclick="startCapture('edit-fob-{{ $emp->id }}')"
                                        class="px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-600 cursor-pointer hover:bg-slate-200 transition-colors">
                                        📡 Tap
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="edit-schedule-{{ $emp->id }}" class="mb-3"></div>
                        <div class="mb-4">
                            <label class="text-[0.8125rem] font-semibold text-gray-700 block mb-1">Break override (mins)</label>
                            <input type="number" id="edit-break-{{ $emp->id }}" min="0" max="120"
                                value="{{ $emp->break_override_minutes ?? '' }}" placeholder="default"
                                class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-rose-500">
                        </div>
                        <div class="flex gap-2">
                            <button onclick="updateEmployee({{ $emp->id }})"
                                class="px-5 py-2 bg-indigo-500 text-white border-0 rounded-lg text-sm font-semibold cursor-pointer hover:bg-indigo-600 transition-colors">
                                Save
                            </button>
                            <button onclick="closeEdit({{ $emp->id }})"
                                class="px-4 py-2 bg-transparent border border-slate-200 rounded-lg text-sm text-slate-500 cursor-pointer hover:bg-slate-50 transition-colors">
                                Cancel
                            </button>
                        </div>
                        <div id="edit-result-{{ $emp->id }}" class="mt-2 text-[0.8125rem]"></div>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>{{-- scroll wrapper --}}
        @endif
    </div>

</main>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const DAYS = ['mon','tue','wed','thu','fri','sat','sun'];
const DAY_LABELS = { mon:'Mon', tue:'Tue', wed:'Wed', thu:'Thu', fri:'Fri', sat:'Sat', sun:'Sun' };

// Existing schedule data passed from PHP
const empSchedules  = @json($employees->pluck('schedule', 'id'));
const empCategories = @json($employees->pluck('category_id', 'id'));
const categories    = @json($categories->map->only(['id','name'])->values());

function buildScheduleEditor(containerId, empId) {
    const container = document.getElementById(containerId);
    const existing  = empId ? (empSchedules[empId] || {}) : {};
    let html = '<label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.5rem;">Weekly schedule</label>';
    html += '<div style="display:flex;flex-wrap:wrap;gap:0.5rem;">';
    DAYS.forEach(d => {
        const s = existing[d] || {};
        const checked = s.start ? 'checked' : '';
        html += `<div style="border:1px solid #e2e8f0;border-radius:8px;padding:0.5rem 0.75rem;background:#fff;">
            <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.8125rem;font-weight:600;color:#374151;cursor:pointer;">
                <input type="checkbox" id="${containerId}-${d}-on" ${checked} onchange="toggleDayTime('${containerId}','${d}')">
                ${DAY_LABELS[d]}
            </label>
            <div id="${containerId}-${d}-times" style="display:${s.start ? 'flex' : 'none'};align-items:center;gap:0.25rem;margin-top:0.35rem;">
                <input type="time" id="${containerId}-${d}-start" value="${s.start || '08:00'}"
                    style="width:80px;padding:3px 5px;border:1px solid #e2e8f0;border-radius:5px;font-size:0.75rem;">
                <span style="font-size:0.75rem;color:#94a3b8;">to</span>
                <input type="time" id="${containerId}-${d}-end" value="${s.end || '16:30'}"
                    style="width:80px;padding:3px 5px;border:1px solid #e2e8f0;border-radius:5px;font-size:0.75rem;">
            </div>
        </div>`;
    });
    html += '</div>';
    html += `<button type="button" onclick="copyMonToFri('${containerId}')"
        style="margin-top:0.5rem;padding:3px 10px;background:none;border:1px solid #e2e8f0;border-radius:6px;font-size:0.75rem;color:#475569;cursor:pointer;">
        Copy Mon → Fri
    </button>`;
    container.innerHTML = html;
}

function toggleDayTime(containerId, day) {
    const on    = document.getElementById(`${containerId}-${day}-on`).checked;
    const times = document.getElementById(`${containerId}-${day}-times`);
    times.style.display = on ? 'flex' : 'none';
}

function copyMonToFri(containerId) {
    const monOn    = document.getElementById(`${containerId}-mon-on`);
    const monStart = document.getElementById(`${containerId}-mon-start`);
    const monEnd   = document.getElementById(`${containerId}-mon-end`);
    if (!monOn?.checked) return;
    const start = monStart?.value || '08:00';
    const end   = monEnd?.value   || '16:30';
    ['tue','wed','thu','fri'].forEach(d => {
        const cb = document.getElementById(`${containerId}-${d}-on`);
        const times = document.getElementById(`${containerId}-${d}-times`);
        const s = document.getElementById(`${containerId}-${d}-start`);
        const e = document.getElementById(`${containerId}-${d}-end`);
        if (cb) cb.checked = true;
        if (times) times.style.display = 'flex';
        if (s) s.value = start;
        if (e) e.value = end;
    });
}

function collectSchedule(containerId) {
    const s = {};
    DAYS.forEach(d => {
        const on = document.getElementById(`${containerId}-${d}-on`)?.checked;
        if (on) {
            s[d] = {
                start: document.getElementById(`${containerId}-${d}-start`)?.value || '08:00',
                end:   document.getElementById(`${containerId}-${d}-end`)?.value   || '16:30',
            };
        } else {
            s[d] = {};
        }
    });
    return s;
}

// ── Add form ──────────────────────────────────────────────────────────────────
let addFormOpen = false;
function toggleAddForm() {
    addFormOpen = !addFormOpen;
    const f = document.getElementById('add-form');
    f.style.display = addFormOpen ? 'block' : 'none';
    if (addFormOpen) buildScheduleEditor('add-schedule', null);
}

async function saveEmployee() {
    const name     = document.getElementById('add-name').value.trim();
    const fob_uid     = document.getElementById('add-fob').value.trim() || null;
    const category_id = document.getElementById('add-category')?.value || null;
    const brk         = document.getElementById('add-break').value;
    const schedule    = collectSchedule('add-schedule');
    const res      = document.getElementById('add-result');
    if (!name) { res.innerHTML = '<span style="color:#ef4444">Name is required.</span>'; return; }
    res.textContent = 'Saving…';
    try {
        const r = await fetch('{{ route('clocking.employees.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ name, fob_uid, category_id: category_id || null, schedule, break_override_minutes: brk || null }),
        });
        if (r.ok) { location.reload(); return; }
        let msg = `Server error (${r.status}) — please refresh the page.`;
        try { const j = await r.json(); msg = j.message || Object.values(j.errors||{})[0]?.[0] || msg; } catch(_){}
        res.innerHTML = `<span style="color:#ef4444">${msg}</span>`;
    } catch(_) { res.innerHTML = '<span style="color:#ef4444">Could not reach server — check your connection.</span>'; }
}

// ── Edit form ─────────────────────────────────────────────────────────────────
function openEdit(id) {
    closeAllEdits();
    document.getElementById('emp-edit-' + id).style.display = 'table-row';
    buildScheduleEditor('edit-schedule-' + id, id);
}
function closeEdit(id) {
    document.getElementById('emp-edit-' + id).style.display = 'none';
}
function closeAllEdits() {
    document.querySelectorAll('[id^="emp-edit-"]').forEach(el => el.style.display = 'none');
}

async function updateEmployee(id) {
    const name     = document.getElementById('edit-name-' + id).value.trim();
    const fob_uid     = document.getElementById('edit-fob-' + id).value.trim();
    const category_id = document.getElementById('edit-category-' + id)?.value || null;
    const brk         = document.getElementById('edit-break-' + id).value;
    const schedule    = collectSchedule('edit-schedule-' + id);
    const res      = document.getElementById('edit-result-' + id);
    if (!name) { res.innerHTML = '<span style="color:#ef4444">Name is required.</span>'; return; }
    res.textContent = 'Saving…';
    try {
        const r = await fetch(`/clocking/employees/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ name, fob_uid: fob_uid || null, category_id: category_id || null, schedule, break_override_minutes: brk || null }),
        });
        if (r.ok) { location.reload(); return; }
        let msg = `Server error (${r.status}) — please refresh the page.`;
        try { const j = await r.json(); msg = j.message || Object.values(j.errors||{})[0]?.[0] || msg; } catch(_){}
        res.innerHTML = `<span style="color:#ef4444">${msg}</span>`;
    } catch(_) { res.innerHTML = '<span style="color:#ef4444">Could not reach server — check your connection.</span>'; }
}

async function toggleActive(id, currentlyActive) {
    const action = currentlyActive ? 'Deactivate' : 'Reactivate';
    if (!confirm(`${action} this employee?`)) return;
    const r = await fetch(`/clocking/employees/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
    });
    if (r.ok) location.reload();
}

async function deleteEmployee(id, name) {
    if (!confirm(`Permanently delete ${name}?\n\nTheir clocking history will be kept, but they will be removed from all lists and cannot be recovered.`)) return;
    const r = await fetch(`/clocking/employees/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    });
    if (r.ok) location.reload();
}

// ── RFID tap-to-capture for fob fields ───────────────────────────────────────
let captureTargetId = null;
let rfidBuffer = '';
let rfidTimer  = null;

function startCapture(inputId) {
    captureTargetId = inputId;
    rfidBuffer = '';
    const input = document.getElementById(inputId);
    input.style.borderColor = '#6366f1';
    input.placeholder = 'Tap fob on reader…';
}

function stopCapture(inputId) {
    if (captureTargetId !== inputId) return;
    captureTargetId = null;
    const input = document.getElementById(inputId);
    if (input) { input.style.borderColor = ''; input.placeholder = 'none assigned'; }
}

document.addEventListener('keydown', e => {
    if (!captureTargetId) return;

    if (e.key === 'Enter') {
        const uid = rfidBuffer.trim();
        rfidBuffer = '';
        if (rfidTimer) { clearTimeout(rfidTimer); rfidTimer = null; }
        if (uid.length >= 8 && uid.length <= 14 && /^\d+$/.test(uid)) {
            const input = document.getElementById(captureTargetId);
            if (input) { input.value = uid; input.style.borderColor = '#22c55e'; }
        }
        captureTargetId = null;
        return;
    }

    e.preventDefault();
    if (e.key.length === 1 && /\d/.test(e.key)) {
        rfidBuffer += e.key;
        if (rfidTimer) clearTimeout(rfidTimer);
        rfidTimer = setTimeout(() => { rfidBuffer = ''; rfidTimer = null; }, 500);
    } else if (e.key.length === 1) {
        rfidBuffer = '';
        if (rfidTimer) { clearTimeout(rfidTimer); rfidTimer = null; }
    }
});
</script>
</x-layout>
