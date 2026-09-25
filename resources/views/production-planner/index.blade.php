<x-layout title="Production Planner — Lockie Portal">

<style>
:root {
    --pp-bg:       #f8fafc;
    --pp-border:   #e2e8f0;
    --pp-text:     #1e293b;
    --pp-muted:    #64748b;
    --pp-am-tint:  #fffbeb;
    --pp-pm-tint:  #eff6ff;
    --pp-row-alt:  #f1f5f9;
    --pp-hdr-bg:   #1e293b;
    --pp-hdr-text: #e2e8f0;
}

#pp-wrap { padding: 20px 24px; max-width: 100%; }

/* Header bar */
#pp-header { display:flex; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
#pp-header h1 { font-size:1.25rem; font-weight:700; color:var(--pp-text); margin:0; flex:1; }
.pp-week-nav { display:flex; align-items:center; gap:8px; }
.pp-week-nav button {
    background:#1e293b; color:#e2e8f0; border:none; border-radius:6px;
    padding:6px 12px; cursor:pointer; font-size:0.8125rem; font-weight:600; transition:background 0.15s;
}
.pp-week-nav button:hover { background:#334155; }
#pp-week-label { font-size:0.9375rem; font-weight:700; color:var(--pp-text); min-width:200px; text-align:center; }
#pp-save-status { font-size:0.75rem; padding:4px 10px; border-radius:999px; font-weight:600; }
#pp-save-status.idle    { background:#f1f5f9; color:#64748b; }
#pp-save-status.saving  { background:#fef3c7; color:#92400e; }
#pp-save-status.saved   { background:#dcfce7; color:#166534; }
#pp-save-status.error   { background:#fee2e2; color:#991b1b; }

/* Legend */
#pp-legend { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:16px; }
.pp-legend-chip {
    display:inline-flex; align-items:center; gap:5px; font-size:0.7rem; font-weight:600;
    padding:3px 8px; border-radius:999px; white-space:nowrap;
}

/* Grid table */
#pp-table-wrap { overflow-x:auto; }
#pp-table {
    border-collapse: collapse; width:100%; font-size:0.78rem;
    background: white; border-radius:8px; overflow:hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
#pp-table th, #pp-table td { border:1px solid var(--pp-border); padding:0; }
#pp-table thead th {
    background:var(--pp-hdr-bg); color:var(--pp-hdr-text);
    padding:8px 10px; text-align:center; font-size:0.75rem; font-weight:700; white-space:nowrap;
}
#pp-table thead th.am-hdr { background:#78350f; }
#pp-table thead th.pm-hdr { background:#1e3a5f; }
#pp-table thead th.op-hdr { background:#0f172a; text-align:left; min-width:150px; }

.pp-op-cell {
    padding:6px 10px; background:#f8fafc; min-width:150px; vertical-align:middle;
}
.pp-op-name { font-weight:700; color:var(--pp-text); display:block; line-height:1.3; }
.pp-op-hours { font-size:0.68rem; color:var(--pp-muted); }
.pp-copy-btn {
    font-size:0.65rem; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px;
    background:white; color:#475569; cursor:pointer; margin-top:3px; display:inline-block;
    transition:background 0.12s;
}
.pp-copy-btn:hover { background:#f1f5f9; }

.pp-shift-cell { padding:4px; min-width:200px; vertical-align:top; }
.pp-shift-cell.am { background:var(--pp-am-tint); }
.pp-shift-cell.pm { background:var(--pp-pm-tint); }
.pp-shift-inner { display:flex; align-items:center; gap:4px; flex-wrap:wrap; }

.pp-select {
    flex:1; min-width:100px; padding:4px 6px; border:1px solid #cbd5e1; border-radius:5px;
    font-size:0.75rem; background:white; cursor:pointer; outline:none;
    transition:border-color 0.15s;
}
.pp-select:focus { border-color:#6366f1; }

.pp-hours-input {
    width:46px; padding:4px 5px; border:1px solid #cbd5e1; border-radius:5px;
    font-size:0.75rem; text-align:center; outline:none; transition:border-color 0.15s;
}
.pp-hours-input:focus { border-color:#6366f1; }
.pp-hours-input.over  { border-color:#ef4444; background:#fef2f2; }
.pp-hours-input.under { border-color:#f59e0b; background:#fffbeb; }

.pp-hours-badge {
    font-size:0.65rem; font-weight:700; padding:2px 5px; border-radius:4px; white-space:nowrap;
}
.pp-hours-badge.over  { background:#fee2e2; color:#991b1b; }
.pp-hours-badge.under { background:#fef3c7; color:#92400e; }

/* Bottom tables */
.pp-bottom { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:24px; }
@media(max-width:900px){ .pp-bottom { grid-template-columns:1fr; } }
.pp-bottom-table { background:white; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.08); overflow:hidden; }
.pp-bottom-table h3 { font-size:0.875rem; font-weight:700; padding:10px 14px; background:#f8fafc; border-bottom:1px solid var(--pp-border); margin:0; }
.pp-bottom-table table { width:100%; border-collapse:collapse; font-size:0.78rem; }
.pp-bottom-table th { padding:6px 10px; text-align:left; background:#f1f5f9; font-weight:700; border-bottom:1px solid var(--pp-border); }
.pp-bottom-table td { padding:6px 10px; border-bottom:1px solid #f1f5f9; }
.pp-bottom-table tr:last-child td { border-bottom:none; }
</style>

<div id="pp-wrap">

    {{-- Header --}}
    <div id="pp-header">
        <h1>Production Planner</h1>
        <div class="pp-week-nav">
            <button id="pp-prev">&#8592; Prev</button>
            <span id="pp-week-label">Loading…</span>
            <button id="pp-next">Next &#8594;</button>
        </div>
        <span id="pp-save-status" class="idle">Ready</span>
    </div>

    {{-- Legend --}}
    <div id="pp-legend">
        <span class="pp-legend-chip" style="background:#f1f5f9;color:#475569;">Unassigned</span>
        <span class="pp-legend-chip" style="background:#fef3c7;color:#92400e;">Holiday</span>
        <span class="pp-legend-chip" style="background:#fee2e2;color:#991b1b;">Sick</span>
        @foreach($machines->groupBy('division') as $division => $divMachines)
            @foreach($divMachines as $m)
                <span class="pp-legend-chip"
                    style="background:hsl({{ $m->hue }},70%,88%);color:hsl({{ $m->hue }},60%,25%);">
                    {{ $m->name }}
                </span>
            @endforeach
        @endforeach
    </div>

    {{-- Grid --}}
    <div id="pp-table-wrap">
        <table id="pp-table">
            <thead>
                <tr>
                    <th class="op-hdr" rowspan="2">Operator</th>
                    @foreach(['Mon','Tue','Wed','Thu','Fri'] as $day)
                        <th class="am-hdr" colspan="2">{{ $day }} AM</th>
                        <th class="pm-hdr" colspan="2">{{ $day }} PM</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach(['Mon','Tue','Wed','Thu','Fri'] as $day)
                        <th class="am-hdr" style="font-size:0.65rem;padding:4px 8px;">Machine</th>
                        <th class="am-hdr" style="font-size:0.65rem;padding:4px 8px;">Hours</th>
                        <th class="pm-hdr" style="font-size:0.65rem;padding:4px 8px;">Machine</th>
                        <th class="pm-hdr" style="font-size:0.65rem;padding:4px 8px;">Hours</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="pp-tbody">
                <tr><td colspan="21" style="padding:20px;text-align:center;color:#94a3b8;">Loading week data…</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Bottom summary tables --}}
    <div class="pp-bottom">
        <div class="pp-bottom-table">
            <h3>Coverage (hours per machine per day)</h3>
            <div id="pp-coverage-wrap"><p style="padding:12px;color:#94a3b8;font-size:0.8rem;">Loading…</p></div>
        </div>
        <div class="pp-bottom-table">
            <h3>Labour Summary (operator hours)</h3>
            <div id="pp-labour-wrap"><p style="padding:12px;color:#94a3b8;font-size:0.8rem;">Loading…</p></div>
        </div>
    </div>

</div>

<script>
(function(){
'use strict';

const OPERATORS = @json($operators);
const MACHINES  = @json($machines);
const CSRF      = document.querySelector('meta[name=csrf-token]').content;

const DAYS    = ['mon','tue','wed','thu','fri'];
const DAYNAME = {mon:'Monday',tue:'Tuesday',wed:'Wednesday',thu:'Thursday',fri:'Friday'};
const SHIFTS  = ['am','pm'];

// Machine lookup
const machineByKey = {};
MACHINES.forEach(m => { machineByKey[m.key] = m; });

// State
let currentMonday = getMonday(new Date());
let assignments   = {};   // { opId: { mon: { am: {m,h,m2,h2}, pm: {...} }, ... } }
let saveTimer     = null;

// ── Date helpers ──────────────────────────────────────────────────────
function getMonday(d) {
    const date = new Date(d);
    const day  = date.getDay();
    const diff = (day === 0 ? -6 : 1 - day);
    date.setDate(date.getDate() + diff);
    date.setHours(0,0,0,0);
    return date;
}
function weekKey(monday) {
    return monday.toISOString().slice(0,10);
}
function formatWeekLabel(monday) {
    const opts = {day:'numeric',month:'short',year:'numeric'};
    const fri  = new Date(monday); fri.setDate(fri.getDate()+4);
    return monday.toLocaleDateString('en-GB',opts) + ' – ' + fri.toLocaleDateString('en-GB',opts);
}

// ── Status badge ─────────────────────────────────────────────────────
function setStatus(state, text) {
    const el = document.getElementById('pp-save-status');
    el.className = state;
    el.textContent = text;
}

// ── Fetch week ────────────────────────────────────────────────────────
async function loadWeek() {
    const key = weekKey(currentMonday);
    document.getElementById('pp-week-label').textContent = formatWeekLabel(currentMonday);
    setStatus('saving','Loading…');
    try {
        const res = await fetch(`/production-planner/week/${key}`);
        const json = await res.json();
        assignments = json.assignments || {};
        renderGrid();
        setStatus('saved','Loaded ✓');
    } catch(e) {
        setStatus('error','Load error');
    }
}

// ── Save week ─────────────────────────────────────────────────────────
function scheduleSave() {
    clearTimeout(saveTimer);
    setStatus('saving','Saving…');
    saveTimer = setTimeout(doSave, 800);
}
async function doSave() {
    const key = weekKey(currentMonday);
    try {
        const res = await fetch(`/production-planner/week/${key}`, {
            method: 'PUT',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body: JSON.stringify({assignments})
        });
        const json = await res.json();
        setStatus(json.ok ? 'saved' : 'error', json.ok ? 'Saved ✓' : 'Error saving');
    } catch(e) {
        setStatus('error','Error saving');
    }
}

// ── Get/set assignment data ───────────────────────────────────────────
function getCell(opId, day, shift) {
    const opStr = String(opId);
    if (!assignments[opStr]) assignments[opStr] = {};
    if (!assignments[opStr][day]) assignments[opStr][day] = {};
    if (!assignments[opStr][day][shift]) assignments[opStr][day][shift] = {m:'',h:'',m2:'',h2:''};
    return assignments[opStr][day][shift];
}

// ── Build machine select options ─────────────────────────────────────
function buildOptions(selectedKey) {
    let html = `<option value="" ${!selectedKey?'selected':''} style="font-style:italic;color:#94a3b8;">(Unassigned)</option>`;
    html += `<option value="holiday" ${selectedKey==='holiday'?'selected':''} style="background:#fef3c7;">Holiday</option>`;
    html += `<option value="sick"    ${selectedKey==='sick'?'selected':''} style="background:#fee2e2;">Sick</option>`;
    MACHINES.forEach(m => {
        const sel = selectedKey === m.key ? 'selected' : '';
        const bg  = `hsl(${m.hue},65%,88%)`;
        html += `<option value="${m.key}" ${sel} style="background:${bg};">${m.name}</option>`;
    });
    return html;
}

// ── Style a select based on value ────────────────────────────────────
function styleSelect(sel, val) {
    if (!val) {
        sel.style.background = '#f8fafc';
        sel.style.color      = '#94a3b8';
        sel.style.fontStyle  = 'italic';
    } else if (val === 'holiday') {
        sel.style.background = '#fef3c7';
        sel.style.color      = '#92400e';
        sel.style.fontStyle  = 'normal';
    } else if (val === 'sick') {
        sel.style.background = '#fee2e2';
        sel.style.color      = '#991b1b';
        sel.style.fontStyle  = 'normal';
    } else {
        const m = machineByKey[val];
        if (m) {
            sel.style.background = `hsl(${m.hue},65%,88%)`;
            sel.style.color      = `hsl(${m.hue},60%,20%)`;
        }
        sel.style.fontStyle = 'normal';
    }
}

// ── Hours validation ─────────────────────────────────────────────────
function validateHours(opId, shift) {
    const op = OPERATORS.find(o => String(o.id) === String(opId));
    if (!op) return;
    const scheduled = parseFloat(shift === 'am' ? op.am_hours : op.pm_hours) || 0;

    let totalHours = 0;
    let hasAny = false;
    DAYS.forEach(day => {
        const cell = getCell(opId, day, shift);
        const h  = parseFloat(cell.h)  || 0;
        const h2 = parseFloat(cell.h2) || 0;
        if (cell.h !== '' || cell.h2 !== '') hasAny = true;
        totalHours += h + h2;
    });

    // per-day validation
    DAYS.forEach(day => {
        const cell  = getCell(opId, day, shift);
        const total = (parseFloat(cell.h)||0) + (parseFloat(cell.h2)||0);
        const inp1  = document.querySelector(`[data-op="${opId}"][data-day="${day}"][data-shift="${shift}"][data-field="h"]`);
        const inp2  = document.querySelector(`[data-op="${opId}"][data-day="${day}"][data-shift="${shift}"][data-field="h2"]`);
        const badge = document.querySelector(`.pp-badge[data-op="${opId}"][data-day="${day}"][data-shift="${shift}"]`);

        if (inp1 || inp2) {
            [inp1, inp2].forEach(inp => {
                if (!inp) return;
                inp.classList.remove('over','under');
                if (total > scheduled) inp.classList.add('over');
                else if (total > 0 && total < scheduled) inp.classList.add('under');
            });
            if (badge) {
                badge.textContent = '';
                badge.className   = 'pp-hours-badge';
                if (total > scheduled) {
                    badge.textContent = `+${(total-scheduled).toFixed(1)}h over`;
                    badge.className   += ' over';
                } else if (total > 0 && total < scheduled) {
                    badge.textContent = `${(scheduled-total).toFixed(1)}h under`;
                    badge.className   += ' under';
                }
            }
        }
    });
}

// ── Copy Mon → rest of week ──────────────────────────────────────────
function copyMonToWeek(opId) {
    const opStr = String(opId);
    SHIFTS.forEach(shift => {
        const src = getCell(opId, 'mon', shift);
        DAYS.slice(1).forEach(day => {
            if (!assignments[opStr]) assignments[opStr] = {};
            if (!assignments[opStr][day]) assignments[opStr][day] = {};
            assignments[opStr][day][shift] = {...src};
        });
    });
    renderGrid();
    scheduleSave();
}

// ── Render full grid ─────────────────────────────────────────────────
function renderGrid() {
    const tbody = document.getElementById('pp-tbody');
    let html = '';

    OPERATORS.forEach(op => {
        html += `<tr>`;
        // Operator cell
        html += `<td class="pp-op-cell">
            <span class="pp-op-name">${escHtml(op.name)}</span>
            <span class="pp-op-hours">${op.am_hours}h AM &middot; ${op.pm_hours}h PM</span><br>
            <button class="pp-copy-btn" onclick="ppCopyMon(${op.id})">Copy Mon &rarr; week</button>
        </td>`;

        DAYS.forEach(day => {
            SHIFTS.forEach(shift => {
                const cell   = getCell(op.id, day, shift);
                const cls    = shift === 'am' ? 'am' : 'pm';
                const selBg  = selectBg(cell.m);
                const selBg2 = selectBg(cell.m2);

                html += `<td class="pp-shift-cell ${cls}">
                    <div class="pp-shift-inner">
                        <select class="pp-select" style="${selBg}"
                            data-op="${op.id}" data-day="${day}" data-shift="${shift}" data-field="m"
                            onchange="ppChange(this)">
                            ${buildOptions(cell.m)}
                        </select>
                        <input type="number" class="pp-hours-input${hoursClass(op,day,shift,'h')}" min="0" max="12" step="0.5"
                            placeholder="h" value="${escHtml(cell.h)}"
                            data-op="${op.id}" data-day="${day}" data-shift="${shift}" data-field="h"
                            onchange="ppChange(this)" oninput="ppChange(this)">
                    </div>
                    <div class="pp-shift-inner" style="margin-top:3px;">
                        <select class="pp-select" style="${selBg2}"
                            data-op="${op.id}" data-day="${day}" data-shift="${shift}" data-field="m2"
                            onchange="ppChange(this)">
                            ${buildOptions(cell.m2)}
                        </select>
                        <input type="number" class="pp-hours-input${hoursClass(op,day,shift,'h2')}" min="0" max="12" step="0.5"
                            placeholder="h" value="${escHtml(cell.h2)}"
                            data-op="${op.id}" data-day="${day}" data-shift="${shift}" data-field="h2"
                            onchange="ppChange(this)" oninput="ppChange(this)">
                    </div>
                    <div>
                        <span class="pp-hours-badge pp-badge" data-op="${op.id}" data-day="${day}" data-shift="${shift}"></span>
                    </div>
                </td>`;
            });
        });

        html += `</tr>`;
    });

    if (!html) html = `<tr><td colspan="21" style="padding:20px;text-align:center;color:#94a3b8;">No operators configured.</td></tr>`;
    tbody.innerHTML = html;

    // Re-validate all hours
    OPERATORS.forEach(op => {
        SHIFTS.forEach(shift => validateHours(op.id, shift));
    });

    renderCoverage();
    renderLabour();
}

function selectBg(val) {
    if (!val)            return 'background:#f8fafc;color:#94a3b8;font-style:italic;';
    if (val==='holiday') return 'background:#fef3c7;color:#92400e;';
    if (val==='sick')    return 'background:#fee2e2;color:#991b1b;';
    const m = machineByKey[val];
    if (m) return `background:hsl(${m.hue},65%,88%);color:hsl(${m.hue},60%,20%);`;
    return '';
}

function hoursClass(op, day, shift, field) {
    const cell      = getCell(op.id, day, shift);
    const scheduled = parseFloat(shift==='am' ? op.am_hours : op.pm_hours) || 0;
    const total     = (parseFloat(cell.h)||0) + (parseFloat(cell.h2)||0);
    if (total > scheduled)  return ' over';
    if (total > 0 && total < scheduled) return ' under';
    return '';
}

// ── onChange handler ─────────────────────────────────────────────────
window.ppChange = function(el) {
    const opId  = el.dataset.op;
    const day   = el.dataset.day;
    const shift = el.dataset.shift;
    const field = el.dataset.field;
    const val   = el.value;

    const cell = getCell(opId, day, shift);
    cell[field] = val;

    // Re-style select immediately
    if (field === 'm' || field === 'm2') styleSelect(el, val);

    validateHours(opId, shift);
    renderCoverage();
    renderLabour();
    scheduleSave();
};

window.ppCopyMon = function(opId) { copyMonToWeek(opId); };

// ── Coverage table ────────────────────────────────────────────────────
function renderCoverage() {
    // machine → day → total hours
    const cov = {};
    MACHINES.forEach(m => { cov[m.key] = {}; DAYS.forEach(d => { cov[m.key][d] = 0; }); });

    OPERATORS.forEach(op => {
        DAYS.forEach(day => {
            SHIFTS.forEach(shift => {
                const cell = getCell(op.id, day, shift);
                [['m','h'],['m2','h2']].forEach(([mk,hk]) => {
                    const mkey = cell[mk];
                    const hrs  = parseFloat(cell[hk]) || 0;
                    if (mkey && machineByKey[mkey] && hrs > 0) {
                        cov[mkey][day] = (cov[mkey][day] || 0) + hrs;
                    }
                });
            });
        });
    });

    let html = `<table><thead><tr><th>Machine</th>${DAYS.map(d=>`<th>${d.charAt(0).toUpperCase()+d.slice(1)}</th>`).join('')}</tr></thead><tbody>`;
    MACHINES.forEach(m => {
        const row    = cov[m.key];
        const hasAny = DAYS.some(d => row[d] > 0);
        if (!hasAny) return;
        const bg = `hsl(${m.hue},60%,92%)`;
        html += `<tr><td style="font-weight:600;background:${bg};">${escHtml(m.name)}</td>`;
        DAYS.forEach(d => {
            const v = row[d];
            html += `<td style="text-align:center;">${v>0 ? v+'h' : '<span style="color:#cbd5e1">–</span>'}</td>`;
        });
        html += `</tr>`;
    });
    html += `</tbody></table>`;
    document.getElementById('pp-coverage-wrap').innerHTML = html;
}

// ── Labour summary ────────────────────────────────────────────────────
function renderLabour() {
    let html = `<table><thead><tr><th>Operator</th><th>Sched AM</th><th>Sched PM</th><th>Logged AM</th><th>Logged PM</th></tr></thead><tbody>`;
    OPERATORS.forEach(op => {
        let logAm = 0, logPm = 0;
        DAYS.forEach(day => {
            const am = getCell(op.id, day, 'am');
            const pm = getCell(op.id, day, 'pm');
            logAm += (parseFloat(am.h)||0) + (parseFloat(am.h2)||0);
            logPm += (parseFloat(pm.h)||0) + (parseFloat(pm.h2)||0);
        });
        const schedAm = parseFloat(op.am_hours) * 5;
        const schedPm = parseFloat(op.pm_hours) * 5;
        const amColor = logAm > schedAm ? '#991b1b' : logAm < schedAm && logAm > 0 ? '#92400e' : '#166534';
        const pmColor = logPm > schedPm ? '#991b1b' : logPm < schedPm && logPm > 0 ? '#92400e' : '#166534';
        html += `<tr>
            <td style="font-weight:600;">${escHtml(op.name)}</td>
            <td style="text-align:center;">${schedAm}h</td>
            <td style="text-align:center;">${schedPm}h</td>
            <td style="text-align:center;color:${amColor};font-weight:700;">${logAm}h</td>
            <td style="text-align:center;color:${pmColor};font-weight:700;">${logPm}h</td>
        </tr>`;
    });
    html += `</tbody></table>`;
    document.getElementById('pp-labour-wrap').innerHTML = html;
}

// ── Escape HTML ───────────────────────────────────────────────────────
function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Week navigation ───────────────────────────────────────────────────
document.getElementById('pp-prev').addEventListener('click', () => {
    currentMonday = new Date(currentMonday);
    currentMonday.setDate(currentMonday.getDate() - 7);
    loadWeek();
});
document.getElementById('pp-next').addEventListener('click', () => {
    currentMonday = new Date(currentMonday);
    currentMonday.setDate(currentMonday.getDate() + 7);
    loadWeek();
});

// ── Init ──────────────────────────────────────────────────────────────
loadWeek();

})();
</script>

</x-layout>
