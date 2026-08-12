<x-layout title="Production Analytics — Lockie Portal">

<style>
.an-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 24px;
}
.an-preset-btn {
    display: inline-flex;
    align-items: center;
    padding: 7px 16px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
    font-family: inherit;
    white-space: nowrap;
}
.an-preset-btn:hover { background: #f1f5f9; }
.an-preset-btn.active { background: #f43f5e; border-color: #f43f5e; color: #fff; }
.an-charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .an-charts-grid { grid-template-columns: 1fr; } }
.an-chart-title { font-size: 0.9rem; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
.an-chart-sub   { font-size: 0.75rem; color: #94a3b8; margin-bottom: 12px; }

/* Filter chips */
.an-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 12px;
}
.an-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.12s;
    font-family: inherit;
    white-space: nowrap;
    user-select: none;
}
.an-chip.on  { background: #fff; border-color: #94a3b8; color: #334155; }
.an-chip.off { background: #f1f5f9; border-color: #e2e8f0; color: #cbd5e1; text-decoration: line-through; }
.an-chip-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

/* Legend */
.an-legend { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; flex-wrap: wrap; }
.an-legend-item { display: flex; align-items: center; gap: 5px; font-size: 0.75rem; color: #64748b; }
.an-legend-dot  { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

/* Summary rows below chart */
.an-summary { margin-top: 16px; border-top: 1px solid #f1f5f9; padding-top: 14px; display: flex; flex-direction: column; gap: 8px; }
.an-sum-row {
    display: grid;
    grid-template-columns: 90px 1fr auto auto;
    align-items: center;
    gap: 10px;
    font-size: 0.78rem;
    transition: opacity 0.15s;
}
.an-sum-row.hidden { display: none; }
.an-sum-name { color: #475569; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.an-sum-bar-track { height: 6px; border-radius: 3px; background: #f1f5f9; overflow: hidden; }
.an-sum-bar-fill  { height: 100%; border-radius: 3px; background: #f43f5e; transition: width 0.4s ease; }
.an-sum-bar-fill.op { background: #6366f1; }
.an-sum-nums { color: #94a3b8; font-size: 0.72rem; white-space: nowrap; }

/* % badges */
.an-pct-badge { display: inline-block; font-size: 0.7rem; font-weight: 700; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }
.an-pct-green { background: #dcfce7; color: #16a34a; }
.an-pct-amber { background: #fef3c7; color: #d97706; }
.an-pct-red   { background: #fee2e2; color: #dc2626; }
.an-pct-none  { background: #f1f5f9; color: #94a3b8; }

/* Breakdown / idle tags */
.an-sum-tags { display: flex; gap: 4px; flex-wrap: wrap; grid-column: 1 / -1; }
.an-tag { display: inline-block; font-size: 0.67rem; font-weight: 600; padding: 1px 6px; border-radius: 20px; white-space: nowrap; cursor: pointer; }
.an-tag:hover { filter: brightness(0.92); }
.an-tag-breakdown { background: #fee2e2; color: #b91c1c; }
.an-tag-idle      { background: #fef3c7; color: #b45309; }

/* Events popup */
.an-events-popup {
    position: fixed; z-index: 9999;
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.14);
    padding: 0; min-width: 300px; max-width: 400px;
    font-size: 0.8rem; color: #334155; overflow: hidden;
}
.an-ep-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 14px 10px; border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
}
.an-ep-header h4 { margin: 0; font-size: 0.82rem; font-weight: 700; color: #1e293b; }
.an-ep-close { cursor: pointer; color: #94a3b8; font-size: 1.1rem; line-height: 1; padding: 0 2px; }
.an-ep-close:hover { color: #475569; }
.an-ep-body { max-height: 260px; overflow-y: auto; padding: 6px 0; }
.an-ep-row {
    display: grid; grid-template-columns: 44px 1fr;
    gap: 6px 10px; padding: 8px 14px; align-items: start;
}
.an-ep-row:not(:last-child) { border-bottom: 1px solid #f8fafc; }
.an-ep-dur {
    font-size: 0.85rem; font-weight: 700; color: #1e293b;
    padding-top: 1px; text-align: right;
}
.an-ep-detail-time { font-size: 0.73rem; color: #64748b; }
.an-ep-detail-job  { font-size: 0.75rem; color: #475569; margin-top: 2px; }
.an-ep-detail-reason { font-size: 0.73rem; color: #b91c1c; margin-top: 2px; font-style: italic; }
.an-ep-empty { padding: 16px 14px; color: #94a3b8; font-size: 0.78rem; }

/* Drill-down */
.an-drill-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 20px;
    animation: fadeIn 0.18s ease;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
.an-drill-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
.an-drill-sub   { font-size: 0.8rem; color: #64748b; margin-bottom: 20px; }
.an-drill-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media (max-width: 760px) { .an-drill-grid { grid-template-columns: 1fr; } }

/* Tables */
.an-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
.an-table th {
    text-align: left; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; color: #94a3b8; padding: 0 8px 8px 0; border-bottom: 1px solid #f1f5f9;
}
.an-table th.r, .an-table td.r { text-align: right; }
.an-table td {
    padding: 8px 8px 8px 0; border-bottom: 1px solid #f8fafc; color: #334155; vertical-align: top;
}
.an-table td.r { color: #64748b; }
.an-table tr:last-child td { border-bottom: none; }
.an-empty { text-align: center; color: #94a3b8; padding: 32px 0; font-size: 0.875rem; }
.an-section-label {
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 10px;
}
</style>

<main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Production Analytics</h1>
            <p class="text-slate-500 text-sm mt-1">Packs produced vs target by machine and operator.</p>
        </div>
        <a href="{{ route('print.machine-log') }}"
            style="display:inline-flex;align-items:center;gap:6px;font-size:0.875rem;padding:8px 14px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;color:#475569;text-decoration:none;transition:background 0.15s;"
            onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
            <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Machine Log
        </a>
    </div>

    {{-- Date range controls --}}
    <div class="an-card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:0.8125rem;font-weight:600;color:#64748b;white-space:nowrap;">Period:</span>
            <a href="{{ route('print.analytics', ['preset' => 'this_month']) }}"
               class="an-preset-btn{{ $preset === 'this_month' ? ' active' : '' }}">This Month</a>
            <a href="{{ route('print.analytics', ['preset' => 'last_month']) }}"
               class="an-preset-btn{{ $preset === 'last_month' ? ' active' : '' }}">Last Month</a>
            <button type="button" onclick="toggleCustom()"
                class="an-preset-btn{{ $preset === 'custom' ? ' active' : '' }}" id="custom-btn">
                Custom range
            </button>
            <div id="custom-form-wrap" style="display:{{ $preset === 'custom' ? 'flex' : 'none' }};align-items:center;gap:8px;flex-wrap:wrap;">
                <form method="GET" action="{{ route('print.analytics') }}" style="display:contents;">
                    <input type="hidden" name="preset" value="custom">
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        onchange="this.form.submit()">
                    <span style="color:#94a3b8;font-size:0.875rem;">→</span>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                        class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        onchange="this.form.submit()">
                </form>
            </div>
            <span style="margin-left:auto;font-size:0.8125rem;color:#94a3b8;white-space:nowrap;">
                {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}
                @if($dateFrom !== $dateTo) – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }} @endif
            </span>
        </div>
    </div>

    {{-- Charts --}}
    <div class="an-charts-grid">

        {{-- Machine card --}}
        <div class="an-card">
            <div class="an-chart-title">Packs by Machine</div>
            <div class="an-chart-sub">Click a machine bar to see job and operator breakdown</div>

            {{-- Machine filter chips --}}
            <div class="an-chips" id="machine-chips"></div>

            <div class="an-legend">
                <div class="an-legend-item"><div class="an-legend-dot" style="background:#f43f5e;"></div> Produced</div>
                <div class="an-legend-item"><div class="an-legend-dot" style="background:#cbd5e1;"></div> Target</div>
            </div>
            <canvas id="machineChart" style="max-height:240px;cursor:pointer;"></canvas>

            {{-- Machine summary --}}
            <div class="an-summary" id="machine-summary"></div>
        </div>

        {{-- Operator card --}}
        <div class="an-card">
            <div class="an-chart-title">Packs by Operator</div>
            <div class="an-chart-sub">Total packs produced and target across all machines</div>

            {{-- Operator filter chips --}}
            <div class="an-chips" id="op-chips"></div>

            <div class="an-legend">
                <div class="an-legend-item"><div class="an-legend-dot" style="background:#6366f1;"></div> Produced</div>
                <div class="an-legend-item"><div class="an-legend-dot" style="background:#cbd5e1;"></div> Target</div>
            </div>
            <canvas id="operatorChart" style="max-height:240px;"></canvas>

            {{-- Operator summary --}}
            <div class="an-summary" id="op-summary"></div>
        </div>
    </div>

    {{-- Machine drill-down --}}
    <div id="drill-panel" class="an-drill-panel" style="display:none;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
            <div>
                <div class="an-drill-title" id="drill-title">—</div>
                <div class="an-drill-sub" id="drill-sub">—</div>
            </div>
            <button onclick="closeDrill()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;" title="Close">
                <svg style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="an-drill-grid">
            <div>
                <div class="an-section-label">Jobs run</div>
                <table class="an-table">
                    <thead><tr>
                        <th>Customer</th><th>Product</th>
                        <th class="r">Hrs</th><th class="r">Produced</th><th class="r">Target</th><th class="r">%</th>
                    </tr></thead>
                    <tbody id="drill-jobs-body"></tbody>
                </table>
            </div>
            <div>
                <div class="an-section-label">Operators on this machine</div>
                <table class="an-table">
                    <thead><tr>
                        <th>Operator</th>
                        <th class="r">Hrs</th><th class="r">Produced</th><th class="r">Target</th><th class="r">%</th>
                    </tr></thead>
                    <tbody id="drill-ops-body"></tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const machineStats  = @json($machineStats);
const operatorStats = @json($operatorStats);
const allMachines   = @json($machines);

// ── helpers ──────────────────────────────────────────────────────────────────
function machineName(m) { return m.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()); }
function fmt(n) { return Number(n).toLocaleString(); }
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function pctBadge(packs, target) {
    if (!target) return '<span class="an-pct-badge an-pct-none">—</span>';
    const pct = Math.round(packs / target * 100);
    const cls = pct >= 95 ? 'an-pct-green' : pct >= 75 ? 'an-pct-amber' : 'an-pct-red';
    return `<span class="an-pct-badge ${cls}">${pct}%</span>`;
}
function pctClass(packs, target) {
    if (!target) return 'an-pct-none';
    const p = packs / target * 100;
    return p >= 95 ? 'an-pct-green' : p >= 75 ? 'an-pct-amber' : 'an-pct-red';
}

// ── machine filter state ──────────────────────────────────────────────────────
const machineBaseColors   = ['#f43f5e','#fb7185','#e11d48','#be123c','#ff6b8a','#fda4af'];
const machineSelectColors = ['#be123c','#9f1239','#881337','#881337','#be123c','#9f1239'];
let hiddenMachines = new Set();
let activeDrillMachine = null;

// Only include machines that have any data
const machines = allMachines.filter(m => (machineStats[m]?.packs ?? 0) > 0 || (machineStats[m]?.target ?? 0) > 0);

// ── machine chips ─────────────────────────────────────────────────────────────
const machineChipsEl = document.getElementById('machine-chips');
machines.forEach((m, i) => {
    const btn = document.createElement('button');
    btn.className = 'an-chip on';
    btn.id = `mchip-${m}`;
    btn.innerHTML = `<span class="an-chip-dot" style="background:${machineBaseColors[i % machineBaseColors.length]}"></span>${machineName(m)}`;
    btn.onclick = () => toggleMachineFilter(m);
    machineChipsEl.appendChild(btn);
});

function toggleMachineFilter(m) {
    if (hiddenMachines.has(m)) hiddenMachines.delete(m);
    else hiddenMachines.add(m);
    document.getElementById(`mchip-${m}`).className = `an-chip ${hiddenMachines.has(m) ? 'off' : 'on'}`;
    refreshMachineChart();
    refreshMachineSummary();
    if (activeDrillMachine && hiddenMachines.has(activeDrillMachine)) closeDrill();
}

// ── machine chart ─────────────────────────────────────────────────────────────
function visibleMachines() { return machines.filter(m => !hiddenMachines.has(m)); }

const machineCtx = document.getElementById('machineChart').getContext('2d');
const machineChart = new Chart(machineCtx, {
    type: 'bar',
    data: buildMachineData(),
    options: {
        responsive: true,
        maintainAspectRatio: true,
        onClick: (evt, elements) => {
            if (!elements.length) return;
            const vm = visibleMachines();
            const m  = vm[elements[0].index];
            if (m) openDrill(m);
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const m = visibleMachines()[ctx.dataIndex];
                        const s = machineStats[m] ?? {};
                        return ctx.datasetIndex === 0
                            ? ` Produced: ${fmt(ctx.parsed.y)} packs`
                            : ` Target: ${fmt(ctx.parsed.y)} packs  (${s.hours ?? 0} hrs run)`;
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { color: '#475569', font: { size: 11 } } }
        }
    }
});

function buildMachineData() {
    const vm = visibleMachines();
    return {
        labels: vm.map(machineName),
        datasets: [
            {
                label: 'Produced',
                data: vm.map(m => machineStats[m]?.packs ?? 0),
                backgroundColor: vm.map((m, i) =>
                    m === activeDrillMachine ? machineSelectColors[i % machineSelectColors.length] : machineBaseColors[i % machineBaseColors.length]
                ),
                borderRadius: 5, borderSkipped: false, order: 1,
            },
            {
                label: 'Target',
                data: vm.map(m => machineStats[m]?.target ?? 0),
                backgroundColor: 'rgba(203,213,225,0.55)',
                borderRadius: 5, borderSkipped: false, order: 2,
            }
        ]
    };
}

function refreshMachineChart() {
    const d = buildMachineData();
    machineChart.data.labels = d.labels;
    machineChart.data.datasets[0].data = d.datasets[0].data;
    machineChart.data.datasets[0].backgroundColor = d.datasets[0].backgroundColor;
    machineChart.data.datasets[1].data = d.datasets[1].data;
    machineChart.update();
}

// ── machine summary ───────────────────────────────────────────────────────────
const machineSummaryEl = document.getElementById('machine-summary');

function buildMachineSummaryRow(m) {
    const s   = machineStats[m] ?? {};
    const pct = s.target > 0 ? Math.round(s.packs / s.target * 100) : null;
    const fill = pct !== null ? Math.min(100, pct) : 0;
    const cls  = pctClass(s.packs, s.target);
    const row  = document.createElement('div');
    row.className = `an-sum-row${hiddenMachines.has(m) ? ' hidden' : ''}`;
    row.id = `msum-${m}`;
    const extraBits = [];
    if ((s.breakdown_hours ?? 0) > 0) extraBits.push(`<span class="an-tag an-tag-breakdown" data-machine="${m}" data-type="breakdown">${s.breakdown_hours}h breakdown</span>`);
    if ((s.idle_hours ?? 0) > 0)      extraBits.push(`<span class="an-tag an-tag-idle" data-machine="${m}" data-type="idle">${s.idle_hours}h idle</span>`);
    row.innerHTML = `
        <span class="an-sum-name" title="${machineName(m)}">${machineName(m)}</span>
        <div class="an-sum-bar-track"><div class="an-sum-bar-fill" style="width:${fill}%"></div></div>
        <span class="an-sum-nums">${fmt(s.packs)} / ${fmt(s.target ?? 0)}</span>
        <span class="an-pct-badge ${cls}">${pct !== null ? pct + '%' : '—'}</span>
        ${extraBits.length ? '<span class="an-sum-tags">' + extraBits.join('') + '</span>' : ''}`;
    // Attach click handlers after innerHTML (can't use onclick= with CSP)
    row.querySelectorAll('.an-tag[data-machine]').forEach(tag => {
        tag.addEventListener('click', e => showEventsPopup(e, tag.dataset.machine, tag.dataset.type));
    });
    return row;
}

machines.forEach(m => machineSummaryEl.appendChild(buildMachineSummaryRow(m)));

function refreshMachineSummary() {
    machines.forEach(m => {
        const row = document.getElementById(`msum-${m}`);
        if (row) row.className = `an-sum-row${hiddenMachines.has(m) ? ' hidden' : ''}`;
    });
}

// ── operator filter state ─────────────────────────────────────────────────────
const opBaseColors = ['#6366f1','#818cf8','#4f46e5','#4338ca','#7c3aed','#8b5cf6','#a78bfa'];
let hiddenOps = new Set(); // by index

// ── operator chips ────────────────────────────────────────────────────────────
const opChipsEl = document.getElementById('op-chips');
operatorStats.forEach((op, i) => {
    const btn = document.createElement('button');
    btn.className = 'an-chip on';
    btn.id = `ochip-${i}`;
    btn.innerHTML = `<span class="an-chip-dot" style="background:${opBaseColors[i % opBaseColors.length]}"></span>${esc(op.name)}`;
    btn.onclick = () => toggleOpFilter(i);
    opChipsEl.appendChild(btn);
});

function toggleOpFilter(i) {
    if (hiddenOps.has(i)) hiddenOps.delete(i);
    else hiddenOps.add(i);
    document.getElementById(`ochip-${i}`).className = `an-chip ${hiddenOps.has(i) ? 'off' : 'on'}`;
    refreshOpChart();
    refreshOpSummary();
}

// ── operator chart ────────────────────────────────────────────────────────────
function visibleOpIdxs() { return operatorStats.map((_, i) => i).filter(i => !hiddenOps.has(i)); }

const opCtx = document.getElementById('operatorChart').getContext('2d');
const opChart = new Chart(opCtx, {
    type: 'bar',
    data: buildOpData(),
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const vi  = visibleOpIdxs();
                        const op  = operatorStats[vi[ctx.dataIndex]] ?? {};
                        return ctx.datasetIndex === 0
                            ? ` Produced: ${fmt(ctx.parsed.y)} packs`
                            : ` Target: ${fmt(ctx.parsed.y)} packs  (${op.hours ?? 0} hrs run)`;
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { color: '#475569', font: { size: 11 }, maxRotation: 30 } }
        }
    }
});

function buildOpData() {
    const vi = visibleOpIdxs();
    return {
        labels: vi.map(i => operatorStats[i].name),
        datasets: [
            {
                label: 'Produced',
                data: vi.map(i => operatorStats[i].packs),
                backgroundColor: vi.map(i => opBaseColors[i % opBaseColors.length]),
                borderRadius: 5, borderSkipped: false, order: 1,
            },
            {
                label: 'Target',
                data: vi.map(i => operatorStats[i].target),
                backgroundColor: 'rgba(203,213,225,0.55)',
                borderRadius: 5, borderSkipped: false, order: 2,
            }
        ]
    };
}

function refreshOpChart() {
    const d = buildOpData();
    opChart.data.labels = d.labels;
    opChart.data.datasets[0].data = d.datasets[0].data;
    opChart.data.datasets[0].backgroundColor = d.datasets[0].backgroundColor;
    opChart.data.datasets[1].data = d.datasets[1].data;
    opChart.update();
}

// ── operator summary ──────────────────────────────────────────────────────────
const opSummaryEl = document.getElementById('op-summary');

function buildOpSummaryRow(op, i) {
    const pct  = op.target > 0 ? Math.round(op.packs / op.target * 100) : null;
    const fill = pct !== null ? Math.min(100, pct) : 0;
    const cls  = pctClass(op.packs, op.target);
    const row  = document.createElement('div');
    row.className = `an-sum-row${hiddenOps.has(i) ? ' hidden' : ''}`;
    row.id = `osum-${i}`;
    row.innerHTML = `
        <span class="an-sum-name" title="${esc(op.name)}">${esc(op.name)}</span>
        <div class="an-sum-bar-track"><div class="an-sum-bar-fill op" style="width:${fill}%"></div></div>
        <span class="an-sum-nums">${fmt(op.packs)} / ${fmt(op.target)}</span>
        <span class="an-pct-badge ${cls}">${pct !== null ? pct + '%' : '—'}</span>`;
    return row;
}

operatorStats.forEach((op, i) => opSummaryEl.appendChild(buildOpSummaryRow(op, i)));

function refreshOpSummary() {
    operatorStats.forEach((_, i) => {
        const row = document.getElementById(`osum-${i}`);
        if (row) row.className = `an-sum-row${hiddenOps.has(i) ? ' hidden' : ''}`;
    });
}

// ── drill-down ────────────────────────────────────────────────────────────────
function openDrill(machine) {
    activeDrillMachine = machine;
    const stats = machineStats[machine] ?? {};
    const pct   = stats.target > 0 ? Math.round(stats.packs / stats.target * 100) : null;

    document.getElementById('drill-title').textContent = machineName(machine);
    document.getElementById('drill-sub').textContent =
        `${fmt(stats.packs)} produced · ${fmt(stats.target)} target` +
        (pct !== null ? ` · ${pct}% of target` : '') +
        `  (${stats.hours ?? 0} hrs run)`;

    const jobsTbody = document.getElementById('drill-jobs-body');
    jobsTbody.innerHTML = '';
    const jobs = stats.jobs ?? [];
    if (!jobs.length) {
        jobsTbody.innerHTML = '<tr><td colspan="6" class="an-empty">No jobs recorded</td></tr>';
    } else {
        jobs.forEach(j => {
            const tr = document.createElement('tr');
            tr.innerHTML =
                `<td><span style="font-weight:600;color:#0f172a;">${esc(j.customer)}</span>
                     ${j.order ? `<br><span style="font-size:0.72rem;color:#94a3b8;">${esc(j.order)}</span>` : ''}</td>
                 <td class="r" style="color:#64748b;">${esc(j.product) || '—'}</td>
                 <td class="r">${j.hours}</td>
                 <td class="r"><strong>${fmt(j.packs)}</strong></td>
                 <td class="r">${fmt(j.target)}</td>
                 <td class="r">${pctBadge(j.packs, j.target)}</td>`;
            jobsTbody.appendChild(tr);
        });
    }

    const opsTbody = document.getElementById('drill-ops-body');
    opsTbody.innerHTML = '';
    const ops = stats.operators ?? [];
    if (!ops.length) {
        opsTbody.innerHTML = '<tr><td colspan="5" class="an-empty">No operator data</td></tr>';
    } else {
        ops.forEach(op => {
            const tr = document.createElement('tr');
            tr.innerHTML =
                `<td style="font-weight:600;color:#0f172a;">${esc(op.name)}</td>
                 <td class="r">${op.hours}</td>
                 <td class="r"><strong>${fmt(op.packs)}</strong></td>
                 <td class="r">${fmt(op.target)}</td>
                 <td class="r">${pctBadge(op.packs, op.target)}</td>`;
            opsTbody.appendChild(tr);
        });
    }

    refreshMachineChart(); // re-highlight selected bar
    const panel = document.getElementById('drill-panel');
    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeDrill() {
    activeDrillMachine = null;
    document.getElementById('drill-panel').style.display = 'none';
    refreshMachineChart();
}

// ── custom range toggle ───────────────────────────────────────────────────────
function toggleCustom() {
    const wrap = document.getElementById('custom-form-wrap');
    const btn  = document.getElementById('custom-btn');
    const open = wrap.style.display !== 'none';
    wrap.style.display = open ? 'none' : 'flex';
    btn.classList.toggle('active', !open);
}

// ── Events popup (breakdown / idle tags) ─────────────────────────────────────
const evPopup = document.createElement('div');
evPopup.className = 'an-events-popup';
evPopup.style.display = 'none';
document.body.appendChild(evPopup);

document.addEventListener('click', e => {
    if (!evPopup.contains(e.target) && !e.target.closest('.an-tag')) {
        evPopup.style.display = 'none';
    }
});

function fmtMins(mins) {
    if (mins < 60) return mins + 'm';
    const h = Math.floor(mins / 60), m = mins % 60;
    return h + 'h' + (m ? ' ' + m + 'm' : '');
}

function showEventsPopup(e, machine, type) {
    e.stopPropagation();
    const s      = machineStats[machine] ?? {};
    const events = type === 'breakdown' ? (s.breakdown_events ?? []) : (s.idle_events ?? []);
    const title  = type === 'breakdown' ? 'Breakdown events' : 'Idle between jobs';

    let bodyHtml = '';
    if (!events.length) {
        bodyHtml = `<div class="an-ep-empty">No events recorded.</div>`;
    } else if (type === 'breakdown') {
        bodyHtml = events.map(ev => `
            <div class="an-ep-row">
                <div class="an-ep-dur">${fmtMins(ev.mins)}</div>
                <div>
                    <div class="an-ep-detail-time">${ev.from} → ${ev.to}</div>
                    <div class="an-ep-detail-job">${esc(ev.job)}</div>
                    ${ev.reason ? `<div class="an-ep-detail-reason">${esc(ev.reason)}</div>` : ''}
                </div>
            </div>`).join('');
    } else {
        bodyHtml = events.map(ev => `
            <div class="an-ep-row">
                <div class="an-ep-dur">${fmtMins(ev.mins)}</div>
                <div>
                    <div class="an-ep-detail-time">${ev.from} → ${ev.to}</div>
                    <div class="an-ep-detail-job">${esc(ev.from_job)}</div>
                    <div class="an-ep-detail-job" style="color:#94a3b8;">→ ${esc(ev.to_job)}</div>
                </div>
            </div>`).join('');
    }

    evPopup.innerHTML = `
        <div class="an-ep-header">
            <h4>${title} — ${machineName(machine)}</h4>
            <span class="an-ep-close">✕</span>
        </div>
        <div class="an-ep-body">${bodyHtml}</div>`;

    evPopup.querySelector('.an-ep-close').addEventListener('click', () => { evPopup.style.display = 'none'; });

    // Position below the tag, flip up if too close to bottom
    evPopup.style.display = 'block';
    const rect = e.target.getBoundingClientRect();
    const pw = evPopup.offsetWidth, ph = evPopup.offsetHeight;
    let left = rect.left;
    let top  = rect.bottom + 8;
    if (left + pw > window.innerWidth - 12) left = window.innerWidth - pw - 12;
    if (left < 8) left = 8;
    if (top + ph > window.innerHeight - 12) top = rect.top - ph - 8;
    evPopup.style.left = left + 'px';
    evPopup.style.top  = top + 'px';
}
</script>

</x-layout>
