<x-layout title="Timesheets — Clocking">
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800 mb-4">Clocking</h1>
        @include('clocking._tabs', ['active' => 'timesheets'])
    </div>

    {{-- Week navigator + export --}}
    @php
        $london   = new \DateTimeZone('Europe/London');
        $prevWeek = $weekStart->copy()->subWeek()->format('Y-m-d');
        $nextWeek = $weekStart->copy()->addWeek()->format('Y-m-d');
        $weekLabel = $weekStart->format('j M') . ' – ' . $weekStart->copy()->addDays(6)->format('j M Y');
    @endphp
    <div class="flex items-center gap-3 mb-6 flex-wrap">
        <a href="?week={{ $prevWeek }}" class="px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-[0.8125rem] text-slate-600 no-underline font-semibold hover:bg-slate-50 transition-colors">← Prev</a>
        <span class="text-base font-bold text-slate-800">{{ $weekLabel }}</span>
        <a href="?week={{ $nextWeek }}" class="px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-[0.8125rem] text-slate-600 no-underline font-semibold hover:bg-slate-50 transition-colors">Next →</a>
        @if($weekStart->format('Y-m-d') !== now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'))
            <a href="?" class="text-[0.8125rem] text-indigo-500 no-underline font-medium">This week</a>
        @endif
        <a href="?week={{ $weekStart->format('Y-m-d') }}&export=1" onclick="this.href='/clocking/timesheets/export?week={{ $weekStart->format('Y-m-d') }}'"
           class="sm:ml-auto px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-[0.8125rem] text-slate-600 no-underline font-semibold hover:bg-slate-50 transition-colors">
            ↓ Export CSV
        </a>
    </div>

    {{-- Timesheet table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        @if($grid->isEmpty())
            <p class="p-6 text-slate-400 text-sm">No active employees.</p>
        @else
        <table class="w-full border-collapse text-sm" style="min-width:700px;">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-2.5 text-xs text-slate-500 uppercase tracking-wide font-semibold whitespace-nowrap">Employee</th>
                    @foreach($days as $day)
                    @php $isToday = $day->isToday(); @endphp
                    <th class="text-center px-2 py-2.5 text-xs uppercase tracking-wide font-semibold whitespace-nowrap {{ $isToday ? 'text-indigo-600 font-bold' : 'text-slate-500' }}">
                        {{ $day->format('D j') }}
                    </th>
                    @endforeach
                    <th class="text-right px-4 py-2.5 text-xs text-slate-500 uppercase tracking-wide font-semibold whitespace-nowrap">Week</th>
                </tr>
            </thead>
            <tbody>
            @foreach($grid as $row)
            @php $emp = $row['employee']; @endphp
            <tr class="border-b border-slate-50">
                <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">{{ $emp->name }}</td>
                @foreach($row['days'] as $i => $dayInfo)
                @php
                    $sessions   = $dayInfo['sessions'];
                    $total      = $dayInfo['total_mins'];
                    $date       = $dayInfo['date']->format('Y-m-d');
                    $isToday    = $dayInfo['date']->isToday();
                    $hasPunches = count($dayInfo['punches']) > 0;
                    $ongoing    = count($sessions) > 0 && $sessions[array_key_last($sessions)]['ongoing'];
                    $isAbsent   = isset($absentOverrides[$emp->id . '_' . $date]);
                @endphp
                <td class="px-1 py-1.5 text-center {{ $isToday ? 'bg-indigo-50/30' : '' }}">
                    <button onclick="openDay({{ $emp->id }}, '{{ $date }}')"
                        class="w-full cursor-pointer text-center text-xs leading-snug rounded-lg border py-1.5 px-1"
                        style="min-width:90px;background:{{ $isAbsent ? '#fef9f0' : ($hasPunches ? ($ongoing ? '#fffbeb' : '#f0fdf4') : 'transparent') }};border-color:{{ $isAbsent ? '#fed7aa' : ($hasPunches ? ($ongoing ? '#fde68a' : '#bbf7d0') : '#e2e8f0') }};">
                        @if($isAbsent)
                            <span class="text-amber-800 font-semibold text-[0.7rem] uppercase tracking-wide">Absent</span>
                        @elseif(count($sessions) === 0 && !$hasPunches)
                            <span class="text-slate-300">—</span>
                        @elseif(count($sessions) === 0 && $hasPunches)
                            <span class="text-slate-400 text-[0.7rem]">edit</span>
                        @else
                            @foreach($sessions as $s)
                            <div class="font-mono whitespace-nowrap {{ $s['ongoing'] ? 'text-amber-600' : 'text-green-800' }}">
                                {{ $s['in']->punched_at->format('H:i') }}–{{ $s['ongoing'] ? '…' : $s['out']->punched_at->format('H:i') }}
                            </div>
                            @endforeach
                            @php $h = intdiv($total, 60); $m = $total % 60; @endphp
                            <div class="text-[0.7rem] mt-px {{ $ongoing ? 'text-amber-600' : 'text-slate-500' }}">
                                {{ $h > 0 ? $h.'h ' : '' }}{{ sprintf('%02dm', $m) }}
                            </div>
                        @endif
                    </button>
                </td>
                @endforeach
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    @php $wh = intdiv($row['week_total'], 60); $wm = $row['week_total'] % 60; @endphp
                    <span class="font-bold text-slate-800">{{ $wh > 0 ? $wh.'h ' : '' }}{{ sprintf('%02dm', $wm) }}</span>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

</main>

{{-- Day detail modal --}}
<div id="day-modal" class="hidden fixed inset-0 bg-slate-900/50 z-50 items-center justify-content-center p-4" style="align-items:center;justify-content:center;">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="px-5 pt-5 pb-4 flex items-center justify-between border-b border-slate-100">
            <div>
                <div id="modal-name" class="text-base font-bold text-slate-800"></div>
                <div id="modal-date" class="text-[0.8125rem] text-slate-500 mt-0.5"></div>
            </div>
            <button onclick="closeDay()" class="px-2 py-1 bg-transparent border border-slate-200 rounded-md text-slate-500 text-base cursor-pointer">✕</button>
        </div>
        <div id="modal-punches" class="px-5 py-4"></div>
        {{-- Absent override --}}
        <div class="px-5 pb-3">
            <button id="absent-btn" onclick="toggleAbsent()"
                class="w-full text-left px-3.5 py-2 bg-transparent border border-slate-200 rounded-lg text-[0.8125rem] text-slate-500 cursor-pointer">
                Mark as absent (suppress alert)
            </button>
        </div>

        {{-- Add punch form --}}
        <div class="px-5 pb-5">
            <div class="border-t border-slate-100 pt-4">
                <p class="text-[0.8125rem] font-semibold text-slate-700 mb-2">Add a punch</p>
                <div class="flex gap-2 flex-wrap items-end">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Direction</label>
                        <select id="add-dir" onchange="togglePairedOut()" class="px-3 py-2 border border-slate-300 rounded-lg text-[0.8125rem] focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <option value="in">Clock In</option>
                            <option value="out">Clock Out</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Time</label>
                        <input type="datetime-local" id="add-time"
                            class="px-3 py-2 border border-slate-300 rounded-lg text-[0.8125rem] focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>
                    <div id="paired-out-wrap">
                        <label class="block text-xs text-slate-500 mb-1">Clock Out <span class="text-slate-400">(optional)</span></label>
                        <input type="datetime-local" id="add-time-out"
                            class="px-3 py-2 border border-slate-300 rounded-lg text-[0.8125rem] focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>
                    <button onclick="submitAddPunch()"
                        class="px-4 py-2 bg-indigo-500 text-white border-0 rounded-lg text-[0.8125rem] font-semibold cursor-pointer hover:bg-indigo-600 transition-colors">
                        Add
                    </button>
                </div>
                <div id="add-result" class="mt-2 text-[0.8125rem]"></div>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// All punch data keyed by "empId_YYYY-MM-DD"
const PUNCH_DATA = @json($punchJson);

let currentEmpId = null;
let currentDate  = null;

function togglePairedOut() {
    const isIn = document.getElementById('add-dir').value === 'in';
    document.getElementById('paired-out-wrap').style.display = isIn ? '' : 'none';
}

function openDay(empId, date) {
    currentEmpId = empId;
    currentDate  = date;
    const key  = empId + '_' + date;
    const data = PUNCH_DATA[key];
    if (!data) return;

    document.getElementById('modal-name').textContent = data.employee_name;
    document.getElementById('modal-date').textContent = data.date_label;

    // Absent toggle button
    const absentBtn = document.getElementById('absent-btn');
    absentBtn.textContent     = data.absent ? '✓ Marked absent — click to remove' : 'Mark as absent (suppress alert)';
    absentBtn.style.background  = data.absent ? '#fef9f0' : '';
    absentBtn.style.borderColor = data.absent ? '#fed7aa' : '';
    absentBtn.style.color       = data.absent ? '#92400e' : '';

    // Pre-fill add-punch time to 9am on that date, reset out field
    document.getElementById('add-time').value     = date + 'T09:00';
    document.getElementById('add-time-out').value = '';
    document.getElementById('add-dir').value = 'in';
    togglePairedOut();

    renderPunches(data.punches);

    const modal = document.getElementById('day-modal');
    modal.style.display = 'flex';
    document.getElementById('add-result').textContent = '';
}

function renderPunches(punches) {
    const container = document.getElementById('modal-punches');
    if (!punches.length) {
        container.innerHTML = '<p style="color:#94a3b8;font-size:0.875rem;margin:0;">No punches recorded.</p>';
        return;
    }
    let html = '';
    punches.forEach(p => {
        const voided  = p.voided;
        const flagged = p.flagged;
        const dirColor = p.direction === 'in' ? '#166534' : '#9a3412';
        const dirBg    = p.direction === 'in' ? '#dcfce7' : '#ffedd5';

        html += `<div style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid #f8fafc;${voided ? 'opacity:0.45;' : ''}">
            <span style="background:${dirBg};color:${dirColor};padding:2px 8px;border-radius:99px;font-size:0.7rem;font-weight:700;text-transform:uppercase;flex-shrink:0;">
                ${p.direction === 'in' ? 'In' : 'Out'}
            </span>
            <span style="font-family:monospace;font-size:0.9375rem;font-weight:600;color:#1e293b;${voided ? 'text-decoration:line-through;' : ''}">
                ${p.time}
            </span>
            <span style="font-size:0.7rem;color:#94a3b8;flex:1;">${p.source}${flagged ? ' ⚑' : ''}${voided ? ' — voided' + (p.voided_reason ? ': ' + p.voided_reason : '') : ''}</span>
            ${!voided ? `<button onclick="voidPunch(${p.id})"
                style="padding:2px 8px;background:none;border:1px solid #fecaca;border-radius:5px;color:#ef4444;font-size:0.7rem;cursor:pointer;flex-shrink:0;">
                Void
            </button>` : ''}
        </div>`;
    });
    container.innerHTML = html;
}

function closeDay() {
    document.getElementById('day-modal').style.display = 'none';
    currentEmpId = null;
    currentDate  = null;
}

async function voidPunch(id) {
    const reason = prompt('Reason for voiding (optional):') ?? null;
    if (reason === null) return; // cancelled
    const r = await fetch(`/clocking/timesheets/void/${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ reason }),
    });
    if (r.ok) location.reload();
}

async function submitAddPunch() {
    const res        = document.getElementById('add-result');
    const direction  = document.getElementById('add-dir').value;
    const punched_at = document.getElementById('add-time').value;
    const out_at     = document.getElementById('add-time-out').value;

    if (!punched_at) { res.innerHTML = '<span style="color:#ef4444">Choose a clock-in time.</span>'; return; }
    if (direction === 'in' && out_at && out_at <= punched_at) {
        res.innerHTML = '<span style="color:#ef4444">Clock-out must be after clock-in.</span>'; return;
    }

    res.textContent = 'Saving…';
    try {
        const post = async (dir, time) => fetch('/clocking/timesheets/punch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ employee_id: currentEmpId, direction: dir, punched_at: time }),
        });

        const r1 = await post(direction, punched_at);
        if (!r1.ok) {
            let msg = `Error (${r1.status})`;
            try { const j = await r1.json(); msg = j.message || Object.values(j.errors||{})[0]?.[0] || msg; } catch(_) {}
            res.innerHTML = `<span style="color:#ef4444">${msg}</span>`; return;
        }

        // If clock-in with an out time, save the out punch too
        if (direction === 'in' && out_at) {
            const r2 = await post('out', out_at);
            if (!r2.ok) {
                let msg = `Clock-in saved, but clock-out failed.`;
                try { const j = await r2.json(); msg += ' ' + (j.message || ''); } catch(_) {}
                res.innerHTML = `<span style="color:#f59e0b">${msg}</span>`; return;
            }
        }

        location.reload();
    } catch(_) { res.innerHTML = '<span style="color:#ef4444">Network error.</span>'; }
}

async function toggleAbsent() {
    const r = await fetch('/clocking/timesheets/absent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ employee_id: currentEmpId, date: currentDate }),
    });
    if (r.ok) location.reload();
}

// Close modal on backdrop click
document.getElementById('day-modal').addEventListener('click', function(e) {
    if (e.target === this) closeDay();
});
</script>
</x-layout>
