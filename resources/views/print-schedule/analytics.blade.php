<x-layout title="Production Analytics — Lockie Portal">

<style>
.an-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 24px;
}
.an-preset-btn {
    padding: 7px 16px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
    font-family: inherit;
}
.an-preset-btn:hover { background: #f1f5f9; }
.an-preset-btn.active {
    background: #f43f5e;
    border-color: #f43f5e;
    color: #fff;
}
.an-charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
@media (max-width: 900px) {
    .an-charts-grid { grid-template-columns: 1fr; }
}
.an-chart-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}
.an-chart-sub {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-bottom: 16px;
}
.an-drill-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 20px;
    animation: fadeIn 0.2s ease;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
.an-drill-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}
.an-drill-sub {
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 20px;
}
.an-drill-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 760px) {
    .an-drill-grid { grid-template-columns: 1fr; }
}
.an-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}
.an-table th {
    text-align: left;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
    padding: 0 8px 8px 0;
    border-bottom: 1px solid #f1f5f9;
}
.an-table th:last-child { text-align: right; }
.an-table td {
    padding: 8px 8px 8px 0;
    border-bottom: 1px solid #f8fafc;
    color: #334155;
    vertical-align: top;
}
.an-table td:last-child { text-align: right; color: #64748b; }
.an-table tr:last-child td { border-bottom: none; }
.an-stat-pill {
    display: inline-block;
    font-weight: 700;
    font-size: 0.8125rem;
    color: #0f172a;
}
.an-empty {
    text-align: center;
    color: #94a3b8;
    padding: 32px 0;
    font-size: 0.875rem;
}
.an-section-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
    margin-bottom: 10px;
}
</style>

<main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Production Analytics</h1>
            <p class="text-slate-500 text-sm mt-1">Packs produced by machine and operator for a chosen period.</p>
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
        <form method="GET" action="{{ route('print.analytics') }}" id="analytics-form">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span style="font-size:0.8125rem;font-weight:600;color:#64748b;white-space:nowrap;">Period:</span>

                <button type="submit" name="preset" value="this_month"
                    class="an-preset-btn{{ $preset === 'this_month' ? ' active' : '' }}">
                    This Month
                </button>
                <button type="submit" name="preset" value="last_month"
                    class="an-preset-btn{{ $preset === 'last_month' ? ' active' : '' }}">
                    Last Month
                </button>
                <button type="button" onclick="toggleCustom()"
                    class="an-preset-btn{{ $preset === 'custom' ? ' active' : '' }}" id="custom-btn">
                    Custom
                </button>

                <div id="custom-inputs" style="display:{{ $preset === 'custom' ? 'flex' : 'none' }};align-items:center;gap:8px;flex-wrap:wrap;">
                    <input type="hidden" name="preset" value="custom" id="custom-preset-input">
                    <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}"
                        class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        onchange="document.getElementById('analytics-form').submit()">
                    <span style="color:#94a3b8;font-size:0.875rem;">→</span>
                    <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}"
                        class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        onchange="document.getElementById('analytics-form').submit()">
                </div>

                <span style="margin-left:auto;font-size:0.8125rem;color:#94a3b8;">
                    {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}
                    @if($dateFrom !== $dateTo)
                        – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                    @endif
                </span>
            </div>
        </form>
    </div>

    {{-- Charts --}}
    <div class="an-charts-grid">
        {{-- Machine chart --}}
        <div class="an-card">
            <div class="an-chart-title">Packs by Machine</div>
            <div class="an-chart-sub">Click a bar to see the breakdown</div>
            <canvas id="machineChart" style="max-height:280px;cursor:pointer;"></canvas>
        </div>

        {{-- Operator chart --}}
        <div class="an-card">
            <div class="an-chart-title">Packs by Operator</div>
            <div class="an-chart-sub">Total packs produced across all machines</div>
            <canvas id="operatorChart" style="max-height:280px;"></canvas>
        </div>
    </div>

    {{-- Machine drill-down panel (hidden until a bar is clicked) --}}
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
                <table class="an-table" id="drill-jobs-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Product</th>
                            <th style="text-align:right">Hrs</th>
                            <th style="text-align:right">Packs</th>
                        </tr>
                    </thead>
                    <tbody id="drill-jobs-body"></tbody>
                </table>
            </div>
            <div>
                <div class="an-section-label">Operators on this machine</div>
                <table class="an-table" id="drill-ops-table">
                    <thead>
                        <tr>
                            <th>Operator</th>
                            <th style="text-align:right">Hrs</th>
                            <th style="text-align:right">Packs</th>
                        </tr>
                    </thead>
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
const machines      = @json($machines);

// --- helpers ---
function machineName(m) {
    return m.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}
function fmt(n) { return n.toLocaleString(); }

// --- machine chart ---
const machineLabels = machines.map(machineName);
const machinePacks  = machines.map(m => machineStats[m]?.packs ?? 0);
const machineColors = [
    '#f43f5e','#fb7185','#e11d48','#be123c','#ff6b8a','#fda4af',
];

const machineCtx = document.getElementById('machineChart').getContext('2d');
const machineChart = new Chart(machineCtx, {
    type: 'bar',
    data: {
        labels: machineLabels,
        datasets: [{
            label: 'Packs',
            data: machinePacks,
            backgroundColor: machines.map((_, i) => machineColors[i % machineColors.length]),
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        onClick: (evt, elements) => {
            if (elements.length) openDrill(machines[elements[0].index]);
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${fmt(ctx.parsed.y)} packs  (${machineStats[machines[ctx.dataIndex]]?.hours ?? 0} hrs)`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: { color: '#94a3b8', font: { size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#475569', font: { size: 11 } }
            }
        }
    }
});

// --- operator chart ---
const opLabels = operatorStats.map(op => op.name);
const opPacks  = operatorStats.map(op => op.packs);
const opColors = [
    '#6366f1','#818cf8','#4f46e5','#4338ca','#7c3aed','#8b5cf6','#a78bfa',
];

const operatorCtx = document.getElementById('operatorChart').getContext('2d');
new Chart(operatorCtx, {
    type: 'bar',
    data: {
        labels: opLabels,
        datasets: [{
            label: 'Packs',
            data: opPacks,
            backgroundColor: operatorStats.map((_, i) => opColors[i % opColors.length]),
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${fmt(ctx.parsed.y)} packs  (${operatorStats[ctx.dataIndex]?.hours ?? 0} hrs)`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: { color: '#94a3b8', font: { size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#475569', font: { size: 11 }, maxRotation: 30 }
            }
        }
    }
});

// --- drill-down ---
let activeMachine = null;

function openDrill(machine) {
    activeMachine = machine;
    const stats = machineStats[machine] ?? {};
    const panel = document.getElementById('drill-panel');

    document.getElementById('drill-title').textContent = machineName(machine);
    document.getElementById('drill-sub').textContent =
        `${fmt(stats.packs ?? 0)} packs · ${stats.hours ?? 0} hrs run`;

    // Jobs
    const jobsTbody = document.getElementById('drill-jobs-body');
    jobsTbody.innerHTML = '';
    const jobs = stats.jobs ?? [];
    if (!jobs.length) {
        jobsTbody.innerHTML = '<tr><td colspan="4" class="an-empty">No jobs recorded</td></tr>';
    } else {
        jobs.forEach(j => {
            const tr = document.createElement('tr');
            tr.innerHTML =
                `<td><span style="font-weight:600;color:#0f172a;">${esc(j.customer)}</span>
                     ${j.order ? `<br><span style="font-size:0.72rem;color:#94a3b8;">${esc(j.order)}</span>` : ''}</td>
                 <td style="color:#64748b;">${esc(j.product) || '—'}</td>
                 <td style="text-align:right;">${j.hours}</td>
                 <td style="text-align:right;"><span class="an-stat-pill">${fmt(j.packs)}</span></td>`;
            jobsTbody.appendChild(tr);
        });
    }

    // Operators
    const opsTbody = document.getElementById('drill-ops-body');
    opsTbody.innerHTML = '';
    const ops = stats.operators ?? [];
    if (!ops.length) {
        opsTbody.innerHTML = '<tr><td colspan="3" class="an-empty">No operator data</td></tr>';
    } else {
        ops.forEach(op => {
            const tr = document.createElement('tr');
            tr.innerHTML =
                `<td style="font-weight:600;color:#0f172a;">${esc(op.name)}</td>
                 <td style="text-align:right;">${op.hours}</td>
                 <td style="text-align:right;"><span class="an-stat-pill">${fmt(op.packs)}</span></td>`;
            opsTbody.appendChild(tr);
        });
    }

    // Highlight active bar
    const idx = machines.indexOf(machine);
    machineChart.data.datasets[0].backgroundColor = machines.map((_, i) =>
        i === idx ? '#be123c' : machineColors[i % machineColors.length]
    );
    machineChart.data.datasets[0].borderWidth = machines.map((_, i) => i === idx ? 3 : 0);
    machineChart.data.datasets[0].borderColor = machines.map((_, i) => i === idx ? '#0f172a' : 'transparent');
    machineChart.update();

    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeDrill() {
    activeMachine = null;
    document.getElementById('drill-panel').style.display = 'none';
    machineChart.data.datasets[0].backgroundColor = machines.map((_, i) => machineColors[i % machineColors.length]);
    machineChart.data.datasets[0].borderWidth = 0;
    machineChart.update();
}

function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// --- custom date toggle ---
function toggleCustom() {
    const inputs = document.getElementById('custom-inputs');
    const btn    = document.getElementById('custom-btn');
    const hidden = document.getElementById('custom-preset-input');
    const isOpen = inputs.style.display !== 'none';
    if (isOpen) {
        inputs.style.display = 'none';
        btn.classList.remove('active');
    } else {
        inputs.style.display = 'flex';
        btn.classList.add('active');
        // Remove preset hidden inputs from the form so the custom preset is used
        document.querySelectorAll('button[name="preset"]').forEach(b => b.removeAttribute('name'));
        hidden.disabled = false;
    }
}

// On load: if preset is custom, disable the preset buttons so they don't override
@if($preset === 'custom')
document.querySelectorAll('button[name="preset"]').forEach(b => b.setAttribute('name', '_disabled_preset'));
@endif
</script>

</x-layout>
