<x-layout title="Production Planner — Lockie Portal">

<style>
:root {
    --pp-bg:       #f8fafc;
    --pp-border:   #e2e8f0;
    --pp-text:     #1e293b;
    --pp-muted:    #64748b;
    --pp-am-bg:    #fffbeb;
    --pp-pm-bg:    #eff6ff;
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

/* Main grid */
#pp-table-wrap { overflow-x:auto; }
#pp-table {
    border-collapse: collapse; width:100%; font-size:0.78rem;
    background:white; border-radius:8px; overflow:hidden;
    box-shadow:0 1px 3px rgba(0,0,0,0.08);
}
#pp-table th, #pp-table td { border:1px solid var(--pp-border); }
#pp-table thead th {
    background:var(--pp-hdr-bg); color:var(--pp-hdr-text);
    padding:8px 12px; text-align:center; font-size:0.8rem; font-weight:700; white-space:nowrap;
}
#pp-table thead th.op-hdr { text-align:left; min-width:160px; background:#0f172a; }
#pp-table thead th.day-hdr { min-width:190px; }

/* Operator name cell */
.pp-op-cell {
    padding:0; background:#f8fafc; vertical-align:top;
}
.pp-op-inner {
    padding:8px 10px;
    border-bottom:1px solid #e2e8f0;
}
.pp-op-name { font-weight:700; color:var(--pp-text); display:block; font-size:0.82rem; }
.pp-op-hours { font-size:0.68rem; color:var(--pp-muted); }
.pp-op-actions { padding:6px 10px; }
.pp-copy-btn {
    font-size:0.65rem; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px;
    background:white; color:#475569; cursor:pointer; display:inline-block;
    transition:background 0.12s;
}
.pp-copy-btn:hover { background:#f1f5f9; }

/* Day cell — two halves stacked */
.pp-day-cell { padding:0; vertical-align:top; }
.pp-day-cell-inner { display:flex; flex-direction:column; height:100%; }

.pp-shift-block { padding:5px 7px; flex:1; }
.pp-shift-block.am { background:var(--pp-am-bg); border-bottom:1px solid #e8e0c8; }
.pp-shift-block.pm { background:var(--pp-pm-bg); }

.pp-shift-label {
    font-size:0.6rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;
    color:#94a3b8; margin-bottom:4px; display:flex; align-items:center; justify-content:space-between;
}
.pp-shift-label .pp-badge {
    font-size:0.6rem; font-weight:700; padding:1px 5px; border-radius:3px;
}
.pp-badge.over  { background:#fee2e2; color:#991b1b; }
.pp-badge.under { background:#fef3c7; color:#92400e; }

.pp-slot { display:flex; align-items:center; gap:4px; margin-bottom:3px; }
.pp-slot:last-child { margin-bottom:0; }

.pp-select {
    flex:1; min-width:0; padding:3px 5px; border:1px solid #cbd5e1; border-radius:5px;
    font-size:0.73rem; background:white; cursor:pointer; outline:none;
    transition:border-color 0.15s; height:26px;
}
.pp-select:focus { border-color:#6366f1; }

.pp-hours-input {
    width:40px; padding:3px 4px; border:1px solid #cbd5e1; border-radius:5px;
    font-size:0.73rem; text-align:center; outline:none;
    transition:border-color 0.15s; height:26px; flex-shrink:0;
}
.pp-hours-input:focus  { border-color:#6366f1; }
.pp-hours-input.over   { border-color:#ef4444; background:#fef2f2; }
.pp-hours-input.under  { border-color:#f59e0b; background:#fffbeb; }

.pp-slot2 { display:none; }
.pp-slot2.visible { display:flex; }

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
        <button onclick="printWeek()" style="background:#475569;color:white;border:none;border-radius:6px;padding:6px 14px;cursor:pointer;font-size:0.8125rem;font-weight:600;">&#128438; Print</button>
    </div>

    @php $divHueMap = $divisions->pluck('hue', 'name')->toArray(); @endphp

    {{-- Grid --}}
    <div id="pp-table-wrap">
        <table id="pp-table">
            <thead>
                <tr>
                    <th class="op-hdr">Operator</th>
                    @foreach(['Mon','Tue','Wed','Thu','Fri'] as $day)
                        <th class="day-hdr">{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="pp-tbody">
                <tr><td colspan="6" style="padding:20px;text-align:center;color:#94a3b8;">Loading week data…</td></tr>
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

    {{-- Division allocation --}}
    <div class="pp-bottom-table" style="margin-top:16px;">
        <h3>Labour Allocation (hours per division per day)</h3>
        <div id="pp-division-wrap"><p style="padding:12px;color:#94a3b8;font-size:0.8rem;">Loading…</p></div>
    </div>

</div>

<script>
(function(){
'use strict';

const OPERATORS = @json($operators);
const MACHINES  = @json($machines);
const CSRF      = document.querySelector('meta[name=csrf-token]').content;

const DAYS    = ['mon','tue','wed','thu','fri'];
const SHIFTS  = ['am','pm'];

// Division → hue mapping (loaded from DB)
const DIVISIONS = @json($divisions);
const DIV_HUES  = {};
DIVISIONS.forEach(d => { DIV_HUES[d.name] = d.hue; });
function divHue(division) { return DIV_HUES[division] ?? 220; }

const machineByKey = {};
MACHINES.forEach(m => { machineByKey[m.key] = m; });

let currentMonday = getMonday(new Date());
let assignments   = {};
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

// ── Per-day scheduled hours ───────────────────────────────────────────
function getScheduled(op, day, shift) {
    return parseFloat(op.schedule?.[day]?.[shift]) || 0;
}

// ── Machine select options ────────────────────────────────────────────
function buildOptions(selectedKey) {
    let html = `<option value="" ${!selectedKey?'selected':''} style="font-style:italic;color:#94a3b8;">(Unassigned)</option>`;
    html += `<option value="holiday" ${selectedKey==='holiday'?'selected':''} style="background:#fef3c7;">Holiday</option>`;
    html += `<option value="sick"    ${selectedKey==='sick'?'selected':''} style="background:#fee2e2;">Sick</option>`;
    MACHINES.forEach(m => {
        const sel = selectedKey === m.key ? 'selected' : '';
        const h   = divHue(m.division);
        html += `<option value="${m.key}" ${sel} style="background:hsl(${h},65%,88%);">${m.name}</option>`;
    });
    return html;
}

function selectStyle(val) {
    if (!val)            return 'background:#f8fafc;color:#94a3b8;font-style:italic;';
    if (val==='holiday') return 'background:#fef3c7;color:#92400e;font-style:normal;';
    if (val==='sick')    return 'background:#fee2e2;color:#991b1b;font-style:normal;';
    const m = machineByKey[val];
    if (m) { const h = divHue(m.division); return `background:hsl(${h},65%,88%);color:hsl(${h},60%,20%);font-style:normal;`; }
    return '';
}

// ── Should the second slot be visible? ───────────────────────────────
function shouldShowSlot2(op, day, shift) {
    const cell = getCell(op.id, day, shift);
    // Always show if m2 or h2 already has data saved
    if (cell.m2 || cell.h2) return true;
    const scheduled = getScheduled(op, day, shift);
    const hours1    = parseFloat(cell.h) || 0;
    // Show when scheduled > 0 and there are remaining hours after first allocation
    if (scheduled > 0 && hours1 > 0 && hours1 < scheduled) return true;
    return false;
}

// ── Validate & update badge ───────────────────────────────────────────
function validateCell(opId, day, shift) {
    const op = OPERATORS.find(o => String(o.id) === String(opId));
    if (!op) return;

    const scheduled = getScheduled(op, day, shift);
    const cell  = getCell(opId, day, shift);
    const total = (parseFloat(cell.h)||0) + (parseFloat(cell.h2)||0);

    const inp1  = document.querySelector(`[data-op="${opId}"][data-day="${day}"][data-shift="${shift}"][data-field="h"]`);
    const inp2  = document.querySelector(`[data-op="${opId}"][data-day="${day}"][data-shift="${shift}"][data-field="h2"]`);
    const badge = document.querySelector(`.pp-badge[data-op="${opId}"][data-day="${day}"][data-shift="${shift}"]`);
    const slot2 = document.querySelector(`.pp-slot2[data-op="${opId}"][data-day="${day}"][data-shift="${shift}"]`);

    // Update slot2 visibility
    if (slot2) {
        const show = shouldShowSlot2(op, day, shift);
        slot2.classList.toggle('visible', show);
    }

    // Determine validation state
    // scheduled=0 means no work this shift — any allocation is an error
    const isOver  = (scheduled === 0 && total > 0) || (scheduled > 0 && total > scheduled);
    const isUnder = !isOver && scheduled > 0 && total > 0 && total < scheduled;

    [inp1, inp2].forEach(inp => {
        if (!inp) return;
        inp.classList.remove('over','under');
        if (isOver)  inp.classList.add('over');
        if (isUnder) inp.classList.add('under');
    });

    if (badge) {
        badge.textContent = '';
        badge.className   = 'pp-badge';
        if (scheduled === 0 && total > 0) {
            badge.textContent = `not scheduled`;
            badge.className  += ' over';
        } else if (scheduled > 0 && total > scheduled) {
            badge.textContent = `+${(total-scheduled).toFixed(1)}h over`;
            badge.className  += ' over';
        } else if (isUnder) {
            badge.textContent = `${(scheduled-total).toFixed(1)}h under`;
            badge.className  += ' under';
        }
    }
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

// ── Format scheduled hours for operator cell ──────────────────────────
function weeklyTotal(op) {
    const s = op.schedule || {};
    const days = ['mon','tue','wed','thu','fri'];
    return days.reduce((sum, d) => sum + (s[d]?.am ?? 4) + (s[d]?.pm ?? 4), 0);
}

function fmtSched(op) {
    const s = op.schedule || {};
    const days = ['mon','tue','wed','thu','fri'];
    const monAm = s.mon?.am ?? 4, monPm = s.mon?.pm ?? 4;
    const allSame = days.every(d => (s[d]?.am ?? 4) === monAm && (s[d]?.pm ?? 4) === monPm);
    const total = weeklyTotal(op);
    const perDay = allSame ? `${monAm}h AM · ${monPm}h PM` : days.map((d,i) => `${'MTWRF'[i]}:${s[d]?.am??4}/${s[d]?.pm??4}`).join(' ');
    return `${perDay} <span style="font-weight:700;color:#1e293b;">(${total}h/wk)</span>`;
}

// ── Render full grid ─────────────────────────────────────────────────
function renderGrid() {
    const tbody = document.getElementById('pp-tbody');
    let html = '';

    OPERATORS.forEach(op => {
        html += `<tr>`;

        // Operator name cell
        html += `<td class="pp-op-cell">
            <div class="pp-op-inner">
                <span class="pp-op-name">${escHtml(op.name)}</span>
                <span class="pp-op-hours">${fmtSched(op)}</span>
            </div>
            <div class="pp-op-actions">
                <button class="pp-copy-btn" onclick="ppCopyMon(${op.id})">Copy Mon &rarr; week</button>
            </div>
        </td>`;

        // One cell per day
        DAYS.forEach(day => {
            html += `<td class="pp-day-cell"><div class="pp-day-cell-inner">`;

            SHIFTS.forEach(shift => {
                const scheduled = getScheduled(op, day, shift);
                const cell    = getCell(op.id, day, shift);
                const show2   = shouldShowSlot2(op, day, shift);
                const shiftLabel = shift.toUpperCase();

                if (scheduled === 0) {
                    html += `<div class="pp-shift-block ${shift}">
                        <div class="pp-shift-label" style="color:#94a3b8;">${shiftLabel}</div>
                    </div>`;
                    return;
                }

                html += `<div class="pp-shift-block ${shift}">
                    <div class="pp-shift-label">
                        <span>${shiftLabel}</span>
                        <span class="pp-badge" data-op="${op.id}" data-day="${day}" data-shift="${shift}"></span>
                    </div>
                    <div class="pp-slot">
                        <select class="pp-select" style="${selectStyle(cell.m)}"
                            data-op="${op.id}" data-day="${day}" data-shift="${shift}" data-field="m"
                            onchange="ppChange(this)">
                            ${buildOptions(cell.m)}
                        </select>
                        <input type="number" class="pp-hours-input" min="0" max="12" step="0.5"
                            placeholder="h" value="${escHtml(cell.h)}"
                            data-op="${op.id}" data-day="${day}" data-shift="${shift}" data-field="h"
                            onchange="ppChange(this)" oninput="ppChange(this)">
                    </div>
                    <div class="pp-slot pp-slot2${show2?' visible':''}" data-op="${op.id}" data-day="${day}" data-shift="${shift}">
                        <select class="pp-select" style="${selectStyle(cell.m2)}"
                            data-op="${op.id}" data-day="${day}" data-shift="${shift}" data-field="m2"
                            onchange="ppChange(this)">
                            ${buildOptions(cell.m2)}
                        </select>
                        <input type="number" class="pp-hours-input" min="0" max="12" step="0.5"
                            placeholder="h" value="${escHtml(cell.h2)}"
                            data-op="${op.id}" data-day="${day}" data-shift="${shift}" data-field="h2"
                            onchange="ppChange(this)" oninput="ppChange(this)">
                    </div>
                </div>`;
            });

            html += `</div></td>`;
        });

        html += `</tr>`;
    });

    if (!html) html = `<tr><td colspan="6" style="padding:20px;text-align:center;color:#94a3b8;">No operators configured.</td></tr>`;
    tbody.innerHTML = html;

    // Re-validate all cells
    OPERATORS.forEach(op => {
        DAYS.forEach(day => {
            SHIFTS.forEach(shift => validateCell(op.id, day, shift));
        });
    });

    renderCoverage();
    renderLabour();
    renderDivisionAllocation();
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

    if (field === 'm' || field === 'm2') {
        el.style.cssText = selectStyle(val);
    }

    validateCell(opId, day, shift);
    renderCoverage();
    renderLabour();
    renderDivisionAllocation();
    scheduleSave();
};

window.ppCopyMon = function(opId) { copyMonToWeek(opId); };

// ── Coverage table ────────────────────────────────────────────────────
function renderCoverage() {
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
        const bg = `hsl(${divHue(m.division)},60%,92%)`;
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
    let html = `<table><thead><tr><th>Operator</th><th>Sched AM</th><th>Sched PM</th><th>Diff AM</th><th>Diff PM</th></tr></thead><tbody>`;
    OPERATORS.forEach(op => {
        let logAm = 0, logPm = 0, schedAm = 0, schedPm = 0;
        DAYS.forEach(day => {
            const am = getCell(op.id, day, 'am');
            const pm = getCell(op.id, day, 'pm');
            const amAbsent = (am.m === 'holiday' || am.m === 'sick');
            const pmAbsent = (pm.m === 'holiday' || pm.m === 'sick');

            // Scheduled: exclude days where operator is on holiday/sick
            if (!amAbsent) schedAm += getScheduled(op, day, 'am');
            if (!pmAbsent) schedPm += getScheduled(op, day, 'pm');

            // Logged: only count real machine hours (not holiday/sick slots)
            if (!amAbsent) logAm += (parseFloat(am.h)||0) + (parseFloat(am.h2)||0);
            if (!pmAbsent) logPm += (parseFloat(pm.h)||0) + (parseFloat(pm.h2)||0);
        });

        const diffAm   = Math.round((logAm - schedAm) * 10) / 10;
        const diffPm   = Math.round((logPm - schedPm) * 10) / 10;
        const amColor  = diffAm === 0 ? '#166534' : '#991b1b';
        const pmColor  = diffPm === 0 ? '#166534' : '#991b1b';
        const fmtDiff  = v => (v > 0 ? '+' : '') + v + 'h';

        html += `<tr>
            <td style="font-weight:600;">${escHtml(op.name)}</td>
            <td style="text-align:center;">${schedAm}h</td>
            <td style="text-align:center;">${schedPm}h</td>
            <td style="text-align:center;color:${amColor};font-weight:700;">${fmtDiff(diffAm)}</td>
            <td style="text-align:center;color:${pmColor};font-weight:700;">${fmtDiff(diffPm)}</td>
        </tr>`;
    });
    html += `</tbody></table>`;
    document.getElementById('pp-labour-wrap').innerHTML = html;
}

// ── Division allocation ───────────────────────────────────────────────
function renderDivisionAllocation() {
    // division name → day → total hours (AM + PM combined)
    const alloc = {};
    DIVISIONS.forEach(div => {
        alloc[div.name] = {};
        DAYS.forEach(d => { alloc[div.name][d] = 0; });
    });

    OPERATORS.forEach(op => {
        DAYS.forEach(day => {
            SHIFTS.forEach(shift => {
                const cell = getCell(op.id, day, shift);
                [['m','h'],['m2','h2']].forEach(([mk,hk]) => {
                    const mkey = cell[mk];
                    const hrs  = parseFloat(cell[hk]) || 0;
                    const m    = machineByKey[mkey];
                    if (m && hrs > 0 && alloc[m.division] !== undefined) {
                        alloc[m.division][day] += hrs;
                    }
                });
            });
        });
    });

    let html = `<table style="width:100%;border-collapse:collapse;font-size:0.78rem;">
        <thead><tr style="background:#1e293b;color:#e2e8f0;">
            <th style="padding:8px 14px;text-align:left;font-size:0.75rem;">Area</th>
            ${DAYS.map(d => `<th style="padding:8px 12px;text-align:center;font-size:0.75rem;">${d.charAt(0).toUpperCase()+d.slice(1)}</th>`).join('')}
            <th style="padding:8px 12px;text-align:center;font-size:0.75rem;">Total</th>
        </tr></thead><tbody>`;

    DIVISIONS.forEach((div, i) => {
        const hue  = div.hue;
        const row  = alloc[div.name] || {};
        const total = DAYS.reduce((s, d) => s + (row[d] || 0), 0);
        const rowBg = i % 2 === 0 ? '#f8fafc' : 'white';
        html += `<tr style="background:${rowBg};">
            <td style="padding:8px 14px;font-weight:700;border-bottom:1px solid #f1f5f9;">
                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:hsl(${hue},65%,60%);margin-right:7px;vertical-align:middle;flex-shrink:0;"></span>
                ${escHtml(div.name)}
            </td>`;
        DAYS.forEach(d => {
            const v  = row[d] || 0;
            const bg = v > 0 ? `hsl(${hue},60%,92%)` : '';
            const fg = v > 0 ? `hsl(${hue},55%,28%)` : '#cbd5e1';
            html += `<td style="padding:7px 10px;text-align:center;font-weight:700;background:${bg};color:${fg};border-bottom:1px solid #f1f5f9;">${v > 0 ? v+'h' : '–'}</td>`;
        });
        const totalBg = total > 0 ? `hsl(${hue},55%,88%)` : '';
        const totalFg = total > 0 ? `hsl(${hue},55%,25%)` : '#cbd5e1';
        html += `<td style="padding:7px 12px;text-align:center;font-weight:800;background:${totalBg};color:${totalFg};border-bottom:1px solid #f1f5f9;">${total > 0 ? total+'h' : '–'}</td>`;
        html += `</tr>`;
    });

    html += `</tbody></table>`;
    document.getElementById('pp-division-wrap').innerHTML = html;
}

// ── Print ─────────────────────────────────────────────────────────────
window.printWeek = function() {
    const weekLabel = document.getElementById('pp-week-label').textContent;
    const DAY_LABELS = ['Monday','Tuesday','Wednesday','Thursday','Friday'];

    // Build coverage: machine → day → hours (for summary)
    const cov = {};
    MACHINES.forEach(m => { cov[m.key] = {}; DAYS.forEach(d => { cov[m.key][d] = 0; }); });
    OPERATORS.forEach(op => {
        DAYS.forEach(day => {
            SHIFTS.forEach(shift => {
                const cell = getCell(op.id, day, shift);
                [['m','h'],['m2','h2']].forEach(([mk,hk]) => {
                    const mkey = cell[mk]; const hrs = parseFloat(cell[hk])||0;
                    if (mkey && machineByKey[mkey] && hrs>0) cov[mkey][day] = (cov[mkey][day]||0)+hrs;
                });
            });
        });
    });

    // Build operator rows HTML
    let opRows = '';
    OPERATORS.forEach(op => {
        let cols = '';
        DAYS.forEach(day => {
            let cellHtml = '';
            SHIFTS.forEach(shift => {
                const scheduled = getScheduled(op, day, shift);
                const cell = getCell(op.id, day, shift);
                const shiftLabel = shift.toUpperCase();
                if (scheduled === 0) {
                    cellHtml += `<div class="shift-block shift-${shift} no-work"><span class="shift-lbl">${shiftLabel}</span></div>`;
                    return;
                }
                const entries = [];
                [['m','h'],['m2','h2']].forEach(([mk,hk]) => {
                    const mkey = cell[mk]; const hrs = cell[hk];
                    if (!mkey) return;
                    if (mkey === 'holiday') { entries.push(`<span class="chip holiday">Holiday</span>`); return; }
                    if (mkey === 'sick')    { entries.push(`<span class="chip sick">Sick</span>`); return; }
                    const m = machineByKey[mkey];
                    if (m) {
                        const h = divHue(m.division);
                        entries.push(`<span class="chip" style="background:hsl(${h},65%,85%);color:hsl(${h},60%,22%);">${escHtml(m.name)}${hrs ? ' · '+hrs+'h' : ''}</span>`);
                    }
                });
                cellHtml += `<div class="shift-block shift-${shift}">
                    <span class="shift-lbl">${shiftLabel}</span>
                    ${entries.join('')}
                </div>`;
            });
            cols += `<td class="day-td">${cellHtml}</td>`;
        });
        opRows += `<tr><td class="op-td">${escHtml(op.name)}</td>${cols}</tr>`;
    });

    // Build division summary rows
    const alloc = {};
    DIVISIONS.forEach(div => { alloc[div.name] = {}; DAYS.forEach(d => { alloc[div.name][d] = 0; }); });
    OPERATORS.forEach(op => {
        DAYS.forEach(day => {
            SHIFTS.forEach(shift => {
                const cell = getCell(op.id, day, shift);
                [['m','h'],['m2','h2']].forEach(([mk,hk]) => {
                    const mkey = cell[mk]; const hrs = parseFloat(cell[hk])||0;
                    const m = machineByKey[mkey];
                    if (m && hrs>0 && alloc[m.division]!==undefined) alloc[m.division][day]+=hrs;
                });
            });
        });
    });
    let divRows = '';
    DIVISIONS.forEach(div => {
        const row = alloc[div.name]||{};
        const total = DAYS.reduce((s,d)=>s+(row[d]||0),0);
        if (total===0) return;
        const h = div.hue;
        const dayCells = DAYS.map(d => {
            const v = row[d]||0;
            return v>0 ? `<td style="text-align:center;background:hsl(${h},60%,90%);color:hsl(${h},55%,28%);font-weight:700;">${v}h</td>`
                       : `<td style="text-align:center;color:#cbd5e1;">–</td>`;
        }).join('');
        divRows += `<tr>
            <td style="font-weight:700;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:hsl(${h},65%,55%);margin-right:6px;vertical-align:middle;"></span>${escHtml(div.name)}</td>
            ${dayCells}
            <td style="text-align:center;font-weight:800;background:hsl(${h},55%,86%);color:hsl(${h},55%,25%);">${total}h</td>
        </tr>`;
    });

    const html = `<!DOCTYPE html><html><head><meta charset="utf-8">
<title>Production Plan — ${weekLabel}</title>
<style>
@page { size: A4 landscape; margin: 10mm; }
* { box-sizing: border-box; }
body { font-family: -apple-system, Arial, sans-serif; font-size: 9pt; color: #1e293b; margin: 0; }
h1 { font-size: 13pt; font-weight: 800; margin: 0 0 2mm; }
.subtitle { font-size: 8pt; color: #64748b; margin-bottom: 4mm; }
table { width: 100%; border-collapse: collapse; }
th { background: #1e293b; color: #e2e8f0; padding: 4px 6px; font-size: 8pt; font-weight: 700; text-align: left; }
th.day-th { text-align: center; width: 17%; }
.op-td { padding: 4px 6px; font-weight: 700; font-size: 8pt; background: #f8fafc; border: 1px solid #e2e8f0; vertical-align: top; white-space: nowrap; }
.day-td { padding: 2px 4px; border: 1px solid #e2e8f0; vertical-align: top; }
.shift-block { padding: 2px 2px 3px; }
.shift-am { background: #fffbeb; border-bottom: 1px solid #e8e0c8; }
.shift-pm { background: #eff6ff; }
.no-work { background: #f8fafc; }
.shift-lbl { display: block; font-size: 6.5pt; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 1px; }
.chip { display: inline-block; font-size: 7pt; font-weight: 600; padding: 1px 4px; border-radius: 3px; margin: 1px 1px 0 0; }
.holiday { background: #fef3c7; color: #92400e; }
.sick    { background: #fee2e2; color: #991b1b; }
.div-section { margin-top: 5mm; }
.div-section h2 { font-size: 9pt; font-weight: 700; margin: 0 0 2mm; }
.div-section table { font-size: 8pt; }
.div-section th { font-size: 7.5pt; padding: 3px 6px; }
.div-section td { padding: 3px 6px; border: 1px solid #e2e8f0; }
.footer { margin-top: 4mm; font-size: 7pt; color: #94a3b8; text-align: right; }
</style></head><body>
<h1>Production Plan</h1>
<div class="subtitle">Week: ${weekLabel} &nbsp;·&nbsp; Printed: ${new Date().toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'})}</div>
<table>
    <thead><tr>
        <th style="width:12%;">Operator</th>
        ${DAY_LABELS.map(d=>`<th class="day-th">${d}</th>`).join('')}
    </tr></thead>
    <tbody>${opRows}</tbody>
</table>
<div class="footer">Lockie Group · Production Planner</div>
<script>window.onload=function(){window.print();}<\/script>
</body></html>`;

    const win = window.open('','_blank','width=1100,height=800');
    win.document.write(html);
    win.document.close();
};

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

loadWeek();

})();
</script>

</x-layout>
