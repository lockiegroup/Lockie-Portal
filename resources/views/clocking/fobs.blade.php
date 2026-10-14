<x-layout title="Fobs — Clocking">
<main style="max-width:960px;margin:0 auto;padding:2rem 1.5rem;">

    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">Clocking</h1>
        @include('clocking._tabs', ['active' => 'fobs'])
    </div>

    {{-- Enrol new fob --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0;">Fobs</h2>
            <button onclick="startEnrol()"
                style="padding:0.4rem 0.875rem;background:#1e293b;color:#fff;border:none;border-radius:7px;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                + Enrol New Fob
            </button>
        </div>

        {{-- Step 1: tap to capture UID --}}
        <div id="enrol-tap" style="display:none;margin-top:1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:1.25rem;text-align:center;">
            <div style="font-size:2rem;margin-bottom:0.5rem;">📡</div>
            <p style="font-size:0.9375rem;font-weight:600;color:#166534;margin:0 0 0.25rem;">Tap a fob on the reader now</p>
            <p style="font-size:0.8125rem;color:#4ade80;margin:0 0 1rem;">Waiting for RFID input…</p>
            <button onclick="cancelEnrol()"
                style="padding:0.4rem 0.875rem;background:none;border:1px solid #bbf7d0;border-radius:7px;font-size:0.8125rem;color:#166534;cursor:pointer;">
                Cancel
            </button>
        </div>

        {{-- Step 2: assign + save --}}
        <div id="enrol-assign" style="display:none;margin-top:1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1.25rem;">
            <p style="font-size:0.8125rem;color:#64748b;margin:0 0 0.75rem;">Fob detected. Assign it to an employee and save.</p>
            <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;">
                <div>
                    <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">UID</label>
                    <input type="text" id="enrol-uid" readonly
                        style="width:160px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;background:#f1f5f9;font-family:monospace;">
                </div>
                <div>
                    <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">Assign to</label>
                    <select id="enrol-employee"
                        style="padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;min-width:180px;">
                        <option value="">— No assignment yet —</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:0.25rem;">Notes (optional)</label>
                    <input type="text" id="enrol-notes" maxlength="200" placeholder="e.g. spare fob"
                        style="width:180px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;">
                </div>
                <div style="display:flex;gap:0.5rem;">
                    <button onclick="saveEnrol()"
                        style="padding:0.5rem 1.25rem;background:#6366f1;color:#fff;border:none;border-radius:7px;font-size:0.875rem;font-weight:600;cursor:pointer;">
                        Save Fob
                    </button>
                    <button onclick="cancelEnrol()"
                        style="padding:0.5rem 1rem;background:none;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;color:#64748b;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </div>
            <div id="enrol-result" style="margin-top:0.5rem;font-size:0.8125rem;"></div>
        </div>
    </div>

    {{-- Fob list --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;">
        @if($fobs->isEmpty())
            <p style="padding:1.5rem;color:#94a3b8;font-size:0.875rem;">No fobs enrolled yet.</p>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
            <thead>
                <tr style="border-bottom:1px solid #e2e8f0;background:#f8fafc;">
                    <th style="text-align:left;padding:0.625rem 1rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;">UID</th>
                    <th style="text-align:left;padding:0.625rem 0.75rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;">Employee</th>
                    <th style="text-align:left;padding:0.625rem 0.75rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;">Assigned</th>
                    <th style="text-align:left;padding:0.625rem 0.75rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;">Status</th>
                    <th style="padding:0.625rem 1rem;"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($fobs as $fob)
            <tr style="border-bottom:1px solid #f1f5f9;{{ $fob->is_active ? '' : 'opacity:0.5;' }}" id="fob-row-{{ $fob->id }}">
                <td style="padding:0.75rem 1rem;font-family:monospace;font-weight:600;color:#1e293b;">{{ $fob->uid }}</td>
                <td style="padding:0.75rem 0.75rem;color:#475569;" id="fob-emp-{{ $fob->id }}">
                    {{ $fob->employee?->name ?? '—' }}
                </td>
                <td style="padding:0.75rem 0.75rem;color:#64748b;">
                    {{ $fob->assigned_at ? $fob->assigned_at->setTimezone('Europe/London')->format('d M Y') : '—' }}
                </td>
                <td style="padding:0.75rem 0.75rem;">
                    @if($fob->is_active)
                        <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:99px;font-size:0.72rem;font-weight:600;">Active</span>
                    @else
                        <span style="background:#f1f5f9;color:#94a3b8;padding:2px 8px;border-radius:99px;font-size:0.72rem;font-weight:600;">Retired</span>
                    @endif
                </td>
                <td style="padding:0.75rem 1rem;text-align:right;white-space:nowrap;">
                    @if($fob->is_active)
                    <button onclick="openAssign({{ $fob->id }})"
                        style="padding:3px 10px;background:none;border:1px solid #e2e8f0;border-radius:6px;color:#475569;font-size:0.75rem;cursor:pointer;margin-right:4px;">
                        Assign
                    </button>
                    <button onclick="deactivateFob({{ $fob->id }})"
                        style="padding:3px 10px;background:none;border:1px solid #fecaca;border-radius:6px;color:#ef4444;font-size:0.75rem;cursor:pointer;">
                        Retire
                    </button>
                    @endif
                </td>
            </tr>
            {{-- Assign row (hidden) --}}
            <tr id="fob-assign-{{ $fob->id }}" style="display:none;">
                <td colspan="5" style="padding:0;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <div style="padding:0.75rem 1rem;display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                        <span style="font-size:0.8125rem;color:#64748b;">Assign to:</span>
                        <select id="assign-emp-{{ $fob->id }}"
                            style="padding:0.4rem 0.625rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;">
                            <option value="">— Unassigned —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ $fob->employee_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                        <button onclick="saveAssign({{ $fob->id }})"
                            style="padding:0.4rem 0.875rem;background:#6366f1;color:#fff;border:none;border-radius:7px;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                            Save
                        </button>
                        <button onclick="closeAssign({{ $fob->id }})"
                            style="padding:0.4rem 0.75rem;background:none;border:1px solid #e2e8f0;border-radius:7px;font-size:0.8125rem;color:#64748b;cursor:pointer;">
                            Cancel
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

</main>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Enrol flow ────────────────────────────────────────────────────────────────
let enrolMode = false;
let capturedUid = null;

function startEnrol() {
    enrolMode  = true;
    capturedUid = null;
    document.getElementById('enrol-tap').style.display    = 'block';
    document.getElementById('enrol-assign').style.display = 'none';
}

function cancelEnrol() {
    enrolMode = false;
    capturedUid = null;
    document.getElementById('enrol-tap').style.display    = 'none';
    document.getElementById('enrol-assign').style.display = 'none';
}

function uidCaptured(uid) {
    capturedUid = uid;
    document.getElementById('enrol-uid').value           = uid;
    document.getElementById('enrol-tap').style.display   = 'none';
    document.getElementById('enrol-assign').style.display = 'block';
    document.getElementById('enrol-result').textContent  = '';
}

async function saveEnrol() {
    if (!capturedUid) return;
    const empId = document.getElementById('enrol-employee').value;
    const notes = document.getElementById('enrol-notes').value.trim();
    const res   = document.getElementById('enrol-result');
    res.textContent = 'Saving…';
    try {
        const r = await fetch('{{ route('clocking.fobs.enrol') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ uid: capturedUid, employee_id: empId || null, notes: notes || null }),
        });
        if (r.ok) { location.reload(); }
        else {
            const j = await r.json();
            res.innerHTML = `<span style="color:#ef4444">${j.errors?.uid?.[0] || j.message || 'Error.'}</span>`;
        }
    } catch(_) { res.innerHTML = '<span style="color:#ef4444">Network error.</span>'; }
}

// ── Assign ------------------------------------------------------------------
function openAssign(id) {
    closeAllAssigns();
    document.getElementById('fob-assign-' + id).style.display = 'table-row';
}
function closeAssign(id) {
    document.getElementById('fob-assign-' + id).style.display = 'none';
}
function closeAllAssigns() {
    document.querySelectorAll('[id^="fob-assign-"]').forEach(el => el.style.display = 'none');
}

async function saveAssign(id) {
    const empId = document.getElementById('assign-emp-' + id).value;
    const r = await fetch(`/clocking/fobs/${id}/assign`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ employee_id: empId || null }),
    });
    if (r.ok) location.reload();
}

async function deactivateFob(id) {
    if (!confirm('Retire this fob? It will no longer be accepted by the kiosk.')) return;
    const r = await fetch(`/clocking/fobs/${id}/deactivate`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF },
    });
    if (r.ok) location.reload();
}

// ── RFID capture (same digit-buffer technique as kiosk) ──────────────────────
let inputBuffer = '';
let inputTimer  = null;

document.addEventListener('keydown', e => {
    if (!enrolMode) return;

    if (e.key === 'Enter') {
        const uid = inputBuffer.trim();
        inputBuffer = '';
        if (inputTimer) { clearTimeout(inputTimer); inputTimer = null; }
        if (uid.length >= 8 && uid.length <= 14 && /^\d+$/.test(uid)) {
            uidCaptured(uid);
        }
        return;
    }

    if (e.key.length === 1 && /\d/.test(e.key)) {
        inputBuffer += e.key;
        if (inputTimer) clearTimeout(inputTimer);
        inputTimer = setTimeout(() => { inputBuffer = ''; inputTimer = null; }, 500);
    } else if (e.key.length === 1) {
        inputBuffer = '';
        if (inputTimer) { clearTimeout(inputTimer); inputTimer = null; }
    }
});
</script>
</x-layout>
