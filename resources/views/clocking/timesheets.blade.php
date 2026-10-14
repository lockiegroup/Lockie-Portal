<x-layout title="Timesheets — Clocking">
<main style="max-width:1100px;margin:0 auto;padding:2rem 1.5rem;">

    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">Clocking</h1>
        @include('clocking._tabs', ['active' => 'timesheets'])
    </div>

    {{-- Week navigator --}}
    @php
        $london   = new \DateTimeZone('Europe/London');
        $prevWeek = $weekStart->copy()->subWeek()->format('Y-m-d');
        $nextWeek = $weekStart->copy()->addWeek()->format('Y-m-d');
        $weekLabel = $weekStart->format('j M') . ' – ' . $weekStart->copy()->addDays(6)->format('j M Y');
    @endphp
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <a href="?week={{ $prevWeek }}" style="padding:0.4rem 0.875rem;background:#fff;border:1px solid #e2e8f0;border-radius:7px;font-size:0.8125rem;color:#475569;text-decoration:none;font-weight:600;">← Prev</a>
        <span style="font-size:1rem;font-weight:700;color:#1e293b;">{{ $weekLabel }}</span>
        <a href="?week={{ $nextWeek }}" style="padding:0.4rem 0.875rem;background:#fff;border:1px solid #e2e8f0;border-radius:7px;font-size:0.8125rem;color:#475569;text-decoration:none;font-weight:600;">Next →</a>
        @if($weekStart->format('Y-m-d') !== now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'))
            <a href="?" style="font-size:0.8125rem;color:#6366f1;text-decoration:none;font-weight:500;">This week</a>
        @endif
    </div>

    {{-- Timesheet table --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow-x:auto;">
        @if($grid->isEmpty())
            <p style="padding:1.5rem;color:#94a3b8;font-size:0.875rem;">No active employees.</p>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;min-width:700px;">
            <thead>
                <tr style="border-bottom:1px solid #e2e8f0;background:#f8fafc;">
                    <th style="text-align:left;padding:0.625rem 1rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;white-space:nowrap;">Employee</th>
                    @foreach($days as $day)
                    @php $isToday = $day->isToday(); @endphp
                    <th style="text-align:center;padding:0.625rem 0.5rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:{{ $isToday ? '#6366f1' : '#64748b' }};font-weight:{{ $isToday ? '700' : '600' }};white-space:nowrap;">
                        {{ $day->format('D j') }}
                    </th>
                    @endforeach
                    <th style="text-align:right;padding:0.625rem 1rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;white-space:nowrap;">Week</th>
                </tr>
            </thead>
            <tbody>
            @foreach($grid as $row)
            @php $emp = $row['employee']; @endphp
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:0.75rem 1rem;font-weight:600;color:#1e293b;white-space:nowrap;">{{ $emp->name }}</td>
                @foreach($row['days'] as $i => $dayInfo)
                @php
                    $sessions = $dayInfo['sessions'];
                    $total    = $dayInfo['total_mins'];
                    $date     = $dayInfo['date']->format('Y-m-d');
                    $isToday  = $dayInfo['date']->isToday();
                    $hasPunches = count($dayInfo['punches']) > 0;
                    $ongoing  = count($sessions) > 0 && $sessions[array_key_last($sessions)]['ongoing'];
                @endphp
                <td style="padding:0.375rem 0.25rem;text-align:center;{{ $isToday ? 'background:#fafaff;' : '' }}">
                    <button onclick="openDay({{ $emp->id }}, '{{ $date }}')"
                        style="width:100%;min-width:90px;padding:0.4rem 0.35rem;background:{{ $hasPunches ? ($ongoing ? '#fffbeb' : '#f0fdf4') : 'transparent' }};border:1px solid {{ $hasPunches ? ($ongoing ? '#fde68a' : '#bbf7d0') : '#e2e8f0' }};border-radius:7px;cursor:pointer;text-align:center;font-size:0.75rem;line-height:1.4;">
                        @if(count($sessions) === 0 && !$hasPunches)
                            <span style="color:#cbd5e1;">—</span>
                        @elseif(count($sessions) === 0 && $hasPunches)
                            {{-- Has punches but no complete sessions (only voided?) --}}
                            <span style="color:#94a3b8;font-size:0.7rem;">edit</span>
                        @else
                            @foreach($sessions as $s)
                            <div style="color:{{ $s['ongoing'] ? '#d97706' : '#166534' }};font-family:monospace;white-space:nowrap;">
                                {{ $s['in']->punched_at->format('H:i') }}–{{ $s['ongoing'] ? '…' : $s['out']->punched_at->format('H:i') }}
                            </div>
                            @endforeach
                            @php $h = intdiv($total, 60); $m = $total % 60; @endphp
                            <div style="color:{{ $ongoing ? '#d97706' : '#64748b' }};font-size:0.7rem;margin-top:1px;">
                                {{ $h > 0 ? $h.'h ' : '' }}{{ sprintf('%02dm', $m) }}
                            </div>
                        @endif
                    </button>
                </td>
                @endforeach
                <td style="padding:0.75rem 1rem;text-align:right;white-space:nowrap;">
                    @php $wh = intdiv($row['week_total'], 60); $wm = $row['week_total'] % 60; @endphp
                    <span style="font-weight:700;color:#1e293b;">{{ $wh > 0 ? $wh.'h ' : '' }}{{ sprintf('%02dm', $wm) }}</span>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

</main>

{{-- Day detail modal --}}
<div id="day-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.5);z-index:200;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding:1.25rem 1.25rem 0;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f1f5f9;padding-bottom:1rem;">
            <div>
                <div id="modal-name" style="font-size:1rem;font-weight:700;color:#1e293b;"></div>
                <div id="modal-date" style="font-size:0.8125rem;color:#64748b;margin-top:1px;"></div>
            </div>
            <button onclick="closeDay()" style="padding:4px 8px;background:none;border:1px solid #e2e8f0;border-radius:6px;color:#64748b;font-size:1rem;cursor:pointer;">✕</button>
        </div>
        <div id="modal-punches" style="padding:1rem 1.25rem;"></div>
        {{-- Add punch form --}}
        <div style="padding:0 1.25rem 1.25rem;">
            <div style="border-top:1px solid #f1f5f9;padding-top:1rem;">
                <p style="font-size:0.8125rem;font-weight:600;color:#374151;margin:0 0 0.5rem;">Add a punch</p>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:flex-end;">
                    <div>
                        <label style="font-size:0.75rem;color:#64748b;display:block;margin-bottom:3px;">Direction</label>
                        <select id="add-dir" style="padding:0.4rem 0.6rem;border:1px solid #e2e8f0;border-radius:6px;font-size:0.8125rem;">
                            <option value="in">Clock In</option>
                            <option value="out">Clock Out</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:0.75rem;color:#64748b;display:block;margin-bottom:3px;">Time</label>
                        <input type="datetime-local" id="add-time"
                            style="padding:0.4rem 0.6rem;border:1px solid #e2e8f0;border-radius:6px;font-size:0.8125rem;">
                    </div>
                    <button onclick="submitAddPunch()"
                        style="padding:0.4rem 1rem;background:#6366f1;color:#fff;border:none;border-radius:6px;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                        Add
                    </button>
                </div>
                <div id="add-result" style="margin-top:0.5rem;font-size:0.8125rem;"></div>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// All punch data keyed by "empId_YYYY-MM-DD"
const PUNCH_DATA = @json(
    $grid->flatMap(function ($row) {
        return $row['days']->map(function ($day) use ($row) {
            $key = $row['employee']->id . '_' . $day['date']->format('Y-m-d');
            return [
                $key => [
                    'employee_id'   => $row['employee']->id,
                    'employee_name' => $row['employee']->name,
                    'date'          => $day['date']->format('Y-m-d'),
                    'date_label'    => $day['date']->format('l, j F Y'),
                    'punches'       => $day['punches']->map(fn($p) => [
                        'id'            => $p->id,
                        'direction'     => $p->direction,
                        'time'          => $p->punched_at->format('H:i'),
                        'punched_at'    => $p->punched_at->format('Y-m-d\TH:i'),
                        'source'        => $p->source,
                        'flagged'       => $p->is_flagged,
                        'voided'        => (bool) $p->voided_at,
                        'voided_reason' => $p->voided_reason,
                    ])->values()->toArray(),
                ]
            ];
        });
    })->collapse()->toArray()
);

let currentEmpId = null;
let currentDate  = null;

function openDay(empId, date) {
    currentEmpId = empId;
    currentDate  = date;
    const key  = empId + '_' + date;
    const data = PUNCH_DATA[key];
    if (!data) return;

    document.getElementById('modal-name').textContent = data.employee_name;
    document.getElementById('modal-date').textContent = data.date_label;

    // Pre-fill add-punch time to noon on that date
    document.getElementById('add-time').value = date + 'T09:00';

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
    const res = document.getElementById('add-result');
    const direction  = document.getElementById('add-dir').value;
    const punched_at = document.getElementById('add-time').value;
    if (!punched_at) { res.innerHTML = '<span style="color:#ef4444">Choose a time.</span>'; return; }
    res.textContent = 'Saving…';
    try {
        const r = await fetch('/clocking/timesheets/punch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ employee_id: currentEmpId, direction, punched_at }),
        });
        if (r.ok) { location.reload(); return; }
        let msg = `Error (${r.status})`;
        try { const j = await r.json(); msg = j.message || Object.values(j.errors||{})[0]?.[0] || msg; } catch(_) {}
        res.innerHTML = `<span style="color:#ef4444">${msg}</span>`;
    } catch(_) { res.innerHTML = '<span style="color:#ef4444">Network error.</span>'; }
}

// Close modal on backdrop click
document.getElementById('day-modal').addEventListener('click', function(e) {
    if (e.target === this) closeDay();
});
</script>
</x-layout>
