<x-layout title="Fobs — Clocking">
<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800 mb-4">Clocking</h1>
        @include('clocking._tabs', ['active' => 'fobs'])
    </div>

    {{-- Enrol new fob --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-[0.9375rem] font-bold text-slate-800">Fobs</h2>
            <button onclick="startEnrol()"
                class="px-3.5 py-2 bg-slate-900 text-white border-0 rounded-lg text-[0.8125rem] font-semibold cursor-pointer hover:bg-slate-700 transition-colors flex-shrink-0">
                + Enrol New Fob
            </button>
        </div>

        {{-- Step 1: tap to capture UID --}}
        <div id="enrol-tap" class="hidden mt-4 bg-green-50 border border-green-200 rounded-lg p-5 text-center">
            <div class="text-3xl mb-2">📡</div>
            <p class="text-[0.9375rem] font-semibold text-green-800 mb-1">Tap a fob on the reader now</p>
            <p class="text-[0.8125rem] text-green-400 mb-4">Waiting for RFID input…</p>
            <button onclick="cancelEnrol()"
                class="px-3.5 py-1.5 bg-transparent border border-green-200 rounded-lg text-[0.8125rem] text-green-800 cursor-pointer">
                Cancel
            </button>
        </div>

        {{-- Step 2: assign + save --}}
        <div id="enrol-assign" class="hidden mt-4 bg-slate-50 border border-slate-200 rounded-lg p-5">
            <p class="text-[0.8125rem] text-slate-500 mb-3">Fob detected. Assign it to an employee and save.</p>
            <div class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1">UID</label>
                    <input type="text" id="enrol-uid" readonly
                        class="w-40 px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-100 font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1">Assign to</label>
                    <select id="enrol-employee"
                        class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 min-w-[180px]">
                        <option value="">— No assignment yet —</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1">Notes (optional)</label>
                    <input type="text" id="enrol-notes" maxlength="200" placeholder="e.g. spare fob"
                        class="w-44 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div class="flex gap-2">
                    <button onclick="saveEnrol()"
                        class="px-5 py-2 bg-indigo-500 text-white border-0 rounded-lg text-[0.875rem] font-semibold cursor-pointer hover:bg-indigo-600 transition-colors">
                        Save Fob
                    </button>
                    <button onclick="cancelEnrol()"
                        class="px-4 py-2 bg-transparent border border-slate-200 rounded-lg text-[0.875rem] text-slate-500 cursor-pointer hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
            <div id="enrol-result" class="mt-2 text-[0.8125rem]"></div>
        </div>
    </div>

    {{-- Fob list --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        @if($fobs->isEmpty())
            <p class="p-6 text-slate-400 text-sm">No fobs enrolled yet.</p>
        @else
        <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm" style="min-width:560px;">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left py-2.5 pl-5 pr-3 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">UID</th>
                    <th class="text-left py-2.5 px-3 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">Employee</th>
                    <th class="text-left py-2.5 px-3 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">Assigned</th>
                    <th class="text-left py-2.5 px-3 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">Status</th>
                    <th class="py-2.5 pr-5"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($fobs as $fob)
            <tr class="border-b border-slate-50 {{ $fob->is_active ? '' : 'opacity-50' }}" id="fob-row-{{ $fob->id }}">
                <td class="py-3 pl-5 pr-3 font-mono font-semibold text-slate-800">{{ $fob->uid }}</td>
                <td class="py-3 px-3 text-slate-500" id="fob-emp-{{ $fob->id }}">
                    {{ $fob->employee?->name ?? '—' }}
                </td>
                <td class="py-3 px-3 text-slate-500">
                    {{ $fob->assigned_at ? $fob->assigned_at->setTimezone('Europe/London')->format('d M Y') : '—' }}
                </td>
                <td class="py-3 px-3">
                    @if($fob->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.72rem] font-semibold bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.72rem] font-semibold bg-slate-100 text-slate-400">Retired</span>
                    @endif
                </td>
                <td class="py-3 pl-3 pr-5 text-right whitespace-nowrap">
                    @if($fob->is_active)
                    <button onclick="openAssign({{ $fob->id }})"
                        class="px-2.5 py-1 border border-slate-200 text-slate-500 text-xs rounded-md bg-transparent cursor-pointer mr-1">
                        Assign
                    </button>
                    <button onclick="deactivateFob({{ $fob->id }})"
                        class="px-2.5 py-1 border border-red-200 text-red-500 text-xs rounded-md bg-transparent cursor-pointer">
                        Retire
                    </button>
                    @endif
                </td>
            </tr>
            {{-- Assign row (hidden) --}}
            <tr id="fob-assign-{{ $fob->id }}" class="hidden">
                <td colspan="5" class="p-0 bg-slate-50 border-b border-slate-200">
                    <div class="px-5 py-3 flex items-center gap-2 flex-wrap">
                        <span class="text-[0.8125rem] text-slate-500">Assign to:</span>
                        <select id="assign-emp-{{ $fob->id }}"
                            class="px-2.5 py-1.5 border border-slate-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <option value="">— Unassigned —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ $fob->employee_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                        <button onclick="saveAssign({{ $fob->id }})"
                            class="px-3.5 py-1.5 bg-indigo-500 text-white border-0 rounded-lg text-[0.8125rem] font-semibold cursor-pointer hover:bg-indigo-600 transition-colors">
                            Save
                        </button>
                        <button onclick="closeAssign({{ $fob->id }})"
                            class="px-3 py-1.5 bg-transparent border border-slate-200 rounded-lg text-[0.8125rem] text-slate-500 cursor-pointer">
                            Cancel
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
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
    document.getElementById('enrol-tap').classList.remove('hidden');
    document.getElementById('enrol-assign').classList.add('hidden');
}

function cancelEnrol() {
    enrolMode = false;
    capturedUid = null;
    document.getElementById('enrol-tap').classList.add('hidden');
    document.getElementById('enrol-assign').classList.add('hidden');
}

function uidCaptured(uid) {
    capturedUid = uid;
    document.getElementById('enrol-uid').value = uid;
    document.getElementById('enrol-tap').classList.add('hidden');
    document.getElementById('enrol-assign').classList.remove('hidden');
    document.getElementById('enrol-result').textContent = '';
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
    document.getElementById('fob-assign-' + id).classList.remove('hidden');
}
function closeAssign(id) {
    document.getElementById('fob-assign-' + id).classList.add('hidden');
}
function closeAllAssigns() {
    document.querySelectorAll('[id^="fob-assign-"]').forEach(el => el.classList.add('hidden'));
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
