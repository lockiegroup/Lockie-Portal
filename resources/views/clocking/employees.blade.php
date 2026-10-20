<x-layout title="Employees — Clocking">
<main style="max-width:960px;margin:0 auto;padding:2rem 1.5rem;">

    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">Clocking</h1>
        @include('clocking._tabs', ['active' => 'employees'])
    </div>

    {{-- Add employee --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0;">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0;">Employees</h2>
            <button onclick="toggleAddForm()"
                style="padding:0.4rem 0.875rem;background:#1e293b;color:#fff;border:none;border-radius:7px;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                + Add Employee
            </button>
        </div>

        <div id="add-form" style="display:none;margin-top:1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1.25rem;">
            <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;">Fill in the employee's name and their weekly schedule.</p>
            <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-bottom:0.75rem;">
                <div>
                    <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">Name</label>
                    <input type="text" id="add-name" placeholder="Full name" maxlength="100"
                        style="width:100%;min-width:220px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;">
                </div>
                @if($categories->isNotEmpty())
                <div>
                    <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">Category</label>
                    <select id="add-category" style="padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;min-width:160px;">
                        <option value="">— none —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">
                        Fob UID <span style="font-weight:400;color:#94a3b8;">— type or tap reader</span>
                    </label>
                    <div style="display:flex;gap:0.375rem;align-items:center;">
                        <input type="text" id="add-fob" maxlength="20" placeholder="e.g. 0012345678" inputmode="numeric"
                            style="width:160px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;font-family:monospace;">
                        <button type="button" onclick="startCapture('add-fob')"
                            style="padding:0.45rem 0.75rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:7px;font-size:0.75rem;color:#475569;cursor:pointer;">
                            📡 Tap
                        </button>
                    </div>
                </div>
            </div>
            <div id="add-schedule" style="margin-bottom:0.75rem;"></div>
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">
                    Break override (minutes) <span style="font-weight:400;color:#94a3b8;">— leave blank to use default</span>
                </label>
                <input type="number" id="add-break" min="0" max="120" placeholder="e.g. 30"
                    style="width:100px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;">
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button onclick="saveEmployee()"
                    style="padding:0.5rem 1.25rem;background:#6366f1;color:#fff;border:none;border-radius:7px;font-size:0.875rem;font-weight:600;cursor:pointer;">
                    Save
                </button>
                <button onclick="toggleAddForm()"
                    style="padding:0.5rem 1rem;background:none;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;color:#64748b;cursor:pointer;">
                    Cancel
                </button>
            </div>
            <div id="add-result" style="margin-top:0.5rem;font-size:0.8125rem;"></div>
        </div>
    </div>

    <style>
        @media (max-width: 600px) {
            .emp-col-schedule, .emp-col-fob { display: none; }
            .emp-col-schedule-hd, .emp-col-fob-hd { display: none; }
        }
    </style>

    {{-- Employee list --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;">
        @if($employees->isEmpty())
            <p style="padding:1.5rem;color:#94a3b8;font-size:0.875rem;">No employees yet. Add one above.</p>
        @else
        <div style="overflow-x:auto;border-radius:14px;">
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;" id="emp-table">
            <thead>
                <tr style="border-bottom:1px solid #e2e8f0;background:#f8fafc;">
                    <th style="text-align:left;padding:0.625rem 1rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;">Name</th>
                    @if($categories->isNotEmpty())
                    <th style="text-align:left;padding:0.625rem 0.75rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;">Category</th>
                    @endif
                    <th class="emp-col-schedule-hd" style="text-align:left;padding:0.625rem 0.75rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;">Schedule</th>
                    <th class="emp-col-fob-hd" style="text-align:left;padding:0.625rem 0.75rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;">Fob</th>
                    <th style="text-align:left;padding:0.625rem 0.75rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;">Status</th>
                    <th style="padding:0.625rem 1rem;"></th>
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
            <tr style="border-bottom:1px solid #f1f5f9;" id="emp-row-{{ $emp->id }}">
                <td style="padding:0.75rem 1rem;font-weight:600;color:#1e293b;">{{ $emp->name }}</td>
                @if($categories->isNotEmpty())
                <td style="padding:0.75rem 0.75rem;">
                    @if($emp->category)
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.8125rem;color:#475569;">
                            <span style="width:9px;height:9px;border-radius:50%;background:{{ $emp->category->color }};flex-shrink:0;"></span>
                            {{ $emp->category->name }}
                        </span>
                    @else
                        <span style="color:#cbd5e1;font-size:0.8125rem;">—</span>
                    @endif
                </td>
                @endif
                <td class="emp-col-schedule" style="padding:0.75rem 0.75rem;color:#475569;">{{ $days ?: '—' }}</td>
                <td class="emp-col-fob" style="padding:0.75rem 0.75rem;font-family:monospace;color:#64748b;">{{ $fob?->uid ?? '—' }}</td>
                <td style="padding:0.75rem 0.75rem;">
                    @if($emp->is_active)
                        <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:99px;font-size:0.72rem;font-weight:600;">Active</span>
                    @else
                        <span style="background:#f1f5f9;color:#94a3b8;padding:2px 8px;border-radius:99px;font-size:0.72rem;font-weight:600;">Inactive</span>
                    @endif
                </td>
                <td style="padding:0.75rem 1rem;text-align:right;white-space:nowrap;">
                    <button onclick="openEdit({{ $emp->id }})"
                        style="padding:3px 10px;background:none;border:1px solid #e2e8f0;border-radius:6px;color:#475569;font-size:0.75rem;cursor:pointer;margin-right:4px;">
                        Edit
                    </button>
                    <button onclick="toggleActive({{ $emp->id }}, {{ $emp->is_active ? 'true' : 'false' }})"
                        style="padding:3px 10px;background:none;border:1px solid {{ $emp->is_active ? '#fecaca' : '#e2e8f0' }};border-radius:6px;color:{{ $emp->is_active ? '#ef4444' : '#64748b' }};font-size:0.75rem;cursor:pointer;">
                        {{ $emp->is_active ? 'Deactivate' : 'Reactivate' }}
                    </button>
                    <button onclick="deleteEmployee({{ $emp->id }}, '{{ addslashes($emp->name) }}')"
                        style="padding:3px 10px;background:none;border:1px solid #fecaca;border-radius:6px;color:#dc2626;font-size:0.75rem;cursor:pointer;margin-left:4px;"
                        title="Permanently delete (history is kept)">
                        Delete
                    </button>
                </td>
            </tr>
            {{-- Edit row (hidden) --}}
            <tr id="emp-edit-{{ $emp->id }}" style="display:none;">
                <td colspan="5" style="padding:0;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <div style="padding:1.25rem 1rem;">
                        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-bottom:0.75rem;">
                            <div>
                                <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">Name</label>
                                <input type="text" id="edit-name-{{ $emp->id }}" value="{{ $emp->name }}" maxlength="100"
                                    style="width:100%;min-width:220px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;">
                            </div>
                            @if($categories->isNotEmpty())
                            <div>
                                <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">Category</label>
                                <select id="edit-category-{{ $emp->id }}" style="padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;min-width:160px;">
                                    <option value="">— none —</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $emp->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div>
                                <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">
                                    Fob UID <span style="font-weight:400;color:#94a3b8;">— type or tap reader</span>
                                </label>
                                <div style="display:flex;gap:0.375rem;align-items:center;">
                                    <input type="text" id="edit-fob-{{ $emp->id }}" maxlength="20" inputmode="numeric"
                                        value="{{ $fob?->uid ?? '' }}" placeholder="none assigned"
                                        style="width:160px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;font-family:monospace;">
                                    <button type="button" onclick="startCapture('edit-fob-{{ $emp->id }}')"
                                        style="padding:0.45rem 0.75rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:7px;font-size:0.75rem;color:#475569;cursor:pointer;">
                                        📡 Tap
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="edit-schedule-{{ $emp->id }}" style="margin-bottom:0.75rem;"></div>
                        <div style="margin-bottom:1rem;">
                            <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">Break override (mins)</label>
                            <input type="number" id="edit-break-{{ $emp->id }}" min="0" max="120"
                                value="{{ $emp->break_override_minutes ?? '' }}" placeholder="default"
                                style="width:100px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;">
                        </div>
                        <div style="display:flex;gap:0.5rem;">
                            <button onclick="updateEmployee({{ $emp->id }})"
                                style="padding:0.5rem 1.25rem;background:#6366f1;color:#fff;border:none;border-radius:7px;font-size:0.875rem;font-weight:600;cursor:pointer;">
                                Save
                            </button>
                            <button onclick="closeEdit({{ $emp->id }})"
                                style="padding:0.5rem 1rem;background:none;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;color:#64748b;cursor:pointer;">
                                Cancel
                            </button>
                        </div>
                        <div id="edit-result-{{ $emp->id }}" style="margin-top:0.5rem;font-size:0.8125rem;"></div>
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
