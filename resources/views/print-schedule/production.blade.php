@php use App\Models\PrintJob; @endphp
<x-layout title="Production Dashboard — Lockie Portal">

<style>
/* ── Reset nav padding in fullscreen ── */
#prod-root.fullscreen-mode {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: #0f172a;
    overflow: hidden;
    padding: 0;
    display: flex;
    flex-direction: column;
}

/* ── Grid ── */
.prod-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
    padding: 24px;
}

/* ── Fullscreen grid: always 3 cols × 2 rows, fills the viewport ── */
#prod-root.fullscreen-mode .prod-grid {
    flex: 1;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(2, 1fr);
    gap: 12px;
    padding: 12px;
    overflow: hidden;
}

/* ── Machine card ── */
.prod-card {
    border-radius: 16px;
    padding: 22px 24px 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    transition: box-shadow 0.2s;
    min-height: 220px;
    position: relative;
}

/* ── Fullscreen card: no min-height, let grid rows decide ── */
#prod-root.fullscreen-mode .prod-card {
    min-height: 0;
    border-radius: 12px;
    padding: 14px 18px 12px;
    gap: 8px;
}
.prod-card.state-running  { background: #052e16; border: 1.5px solid #16a34a; }
.prod-card.state-paused   { background: #422006; border: 1.5px solid #d97706; }
.prod-card.state-idle     { background: #1e293b; border: 1.5px solid #334155; }

/* ── Card header ── */
.prod-machine-name {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.state-running  .prod-machine-name { color: #4ade80; }
.state-paused   .prod-machine-name { color: #fbbf24; }
.state-idle     .prod-machine-name { color: #64748b; }

.prod-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 9999px;
}
.state-running  .prod-status-badge { background: #16a34a22; color: #4ade80; border: 1px solid #16a34a55; }
.state-paused   .prod-status-badge { background: #d9770622; color: #fbbf24; border: 1px solid #d9770655; }
.state-idle     .prod-status-badge { background: #1e293b;   color: #475569; border: 1px solid #334155; }

.prod-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.state-running  .prod-dot { background: #4ade80; animation: pulse-green 1.5s infinite; }
.state-paused   .prod-dot { background: #fbbf24; }
.state-idle     .prod-dot { background: #475569; }

@keyframes pulse-green {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.3; }
}

/* ── Job info ── */
.prod-job-number {
    font-size: 1.4rem;
    font-weight: 800;
    color: #f1f5f9;
    line-height: 1.1;
}
.prod-product {
    font-size: 0.78rem;
    color: #94a3b8;
    margin-top: 2px;
}
.prod-customer {
    font-size: 0.78rem;
    color: #64748b;
}

/* ── Metrics row ── */
.prod-metrics {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.prod-metric {
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.prod-metric-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #f1f5f9;
}
.prod-metric-label {
    font-size: 0.68rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

/* ── Progress bar ── */
.prod-progress-wrap {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.prod-progress-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.prod-progress-label {
    font-size: 0.68rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.prod-progress-pct {
    font-size: 0.75rem;
    font-weight: 700;
    color: #f1f5f9;
}
.prod-progress-bar {
    height: 6px;
    background: #1e293b;
    border-radius: 9999px;
    overflow: hidden;
    border: 1px solid #ffffff11;
}
.prod-progress-fill {
    height: 100%;
    border-radius: 9999px;
    transition: width 0.5s ease;
}
.prod-progress-counts {
    font-size: 0.68rem;
    color: #475569;
}

/* ── On-track badge ── */
.prod-on-track {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
    white-space: nowrap;
}
.prod-on-track.track-ok     { background: #16a34a22; color: #4ade80; border: 1px solid #16a34a55; }
.prod-on-track.track-risk   { background: #d9770622; color: #fbbf24; border: 1px solid #d9770655; }
.prod-on-track.track-behind { background: #dc262622; color: #f87171; border: 1px solid #dc262655; }

/* ── Pause reason ── */
.prod-pause-reason {
    background: #78350f33;
    border: 1px solid #d9770644;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.8rem;
    color: #fcd34d;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

/* ── Operator + last update ── */
.prod-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid #ffffff11;
}
.prod-operator {
    font-size: 0.78rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 6px;
}
.prod-operator strong { color: #e2e8f0; }
.prod-last-update {
    font-size: 0.7rem;
    color: #475569;
}

/* ── Idle card content ── */
.prod-idle-text {
    font-size: 0.85rem;
    color: #334155;
    margin: auto 0;
}

/* ── Header bar ── */
.prod-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px 0;
    flex-wrap: wrap;
    gap: 12px;
}
.prod-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #f1f5f9;
}
.prod-subtitle {
    font-size: 0.75rem;
    color: #475569;
    margin-top: 2px;
}
.prod-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}
.prod-updated {
    font-size: 0.72rem;
    color: #334155;
}
.prod-btn {
    background: #1e293b;
    border: 1px solid #334155;
    color: #94a3b8;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background 0.15s, color 0.15s;
    white-space: nowrap;
}
.prod-btn:hover { background: #334155; color: #e2e8f0; }

/* ── When not in fullscreen, show normal page chrome ── */
#prod-root:not(.fullscreen-mode) .prod-header {
    padding-top: 0;
}

/* ── Fullscreen: compact header and tighter text ── */
#prod-root.fullscreen-mode .prod-header {
    padding: 8px 14px;
    flex-shrink: 0;
}
#prod-root.fullscreen-mode .prod-card {
    overflow: hidden; /* clip rather than push rows off-screen */
}
#prod-root.fullscreen-mode .prod-job-number  { font-size: 1.05rem; }
#prod-root.fullscreen-mode .prod-metric-value { font-size: 0.88rem; }
#prod-root.fullscreen-mode .prod-metric-label { font-size: 0.62rem; }
#prod-root.fullscreen-mode .prod-machine-name { font-size: 0.65rem; }
#prod-root.fullscreen-mode .prod-product,
#prod-root.fullscreen-mode .prod-customer     { font-size: 0.7rem; margin-top: 0; }
#prod-root.fullscreen-mode .prod-status-badge { font-size: 0.68rem; padding: 2px 8px; }
#prod-root.fullscreen-mode .prod-footer       { padding-top: 4px; font-size: 0.7rem; }
#prod-root.fullscreen-mode .prod-operator     { font-size: 0.7rem; }
#prod-root.fullscreen-mode .prod-last-update  { font-size: 0.65rem; }
#prod-root.fullscreen-mode .prod-on-track     { font-size: 0.62rem; padding: 1px 6px; }
#prod-root.fullscreen-mode .prod-pause-reason { padding: 5px 10px; font-size: 0.72rem; }
#prod-root.fullscreen-mode .prod-progress-label,
#prod-root.fullscreen-mode .prod-progress-counts { font-size: 0.62rem; }
#prod-root.fullscreen-mode .prod-progress-pct { font-size: 0.7rem; }
#prod-root.fullscreen-mode .prod-progress-bar { height: 5px; }
#prod-root.fullscreen-mode .prod-metrics      { gap: 14px; }
#prod-root.fullscreen-mode .prod-progress-wrap { gap: 3px; }
</style>

<main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-8" style="background:#0f172a;min-height:100vh;">

<div id="prod-root">

    <div class="prod-header">
        <div>
            <div class="prod-title">Production Dashboard</div>
            <div class="prod-subtitle">Live machine status &mdash; updates every 20 seconds</div>
        </div>
        <div class="prod-header-right">
            <span class="prod-updated" id="updated-at">—</span>
            <button class="prod-btn" id="fullscreen-btn" onclick="toggleFullscreen()">
                <svg id="fs-icon-enter" style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                <svg id="fs-icon-exit"  style="width:14px;height:14px;display:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/></svg>
                <span id="fs-label">Full Screen</span>
            </button>
        </div>
    </div>

    <div class="prod-grid" id="prod-grid">
        {{-- Skeleton cards on first load --}}
        @foreach(PrintJob::MACHINES as $machine)
        <div class="prod-card state-idle" id="card-{{ $machine }}">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span class="prod-machine-name">{{ PrintJob::BOARDS[$machine] }}</span>
                <span class="prod-status-badge"><span class="prod-dot"></span> Loading…</span>
            </div>
        </div>
        @endforeach
    </div>

</div>

</main>

<script>
const STATUS_URL = '{{ route('print.production.status') }}';
let fullscreen = false;

// ── Fetch and render ──────────────────────────────────────────────────────────
async function fetchStatus() {
    try {
        const res  = await fetch(STATUS_URL);
        const data = await res.json();
        renderMachines(data.machines);
        const ts = new Date(data.updated_at);
        document.getElementById('updated-at').textContent =
            'Updated ' + ts.toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
    } catch (e) {
        document.getElementById('updated-at').textContent = 'Update failed';
    }
}

function timeAgo(isoStr) {
    if (!isoStr) return '—';
    const diff = Math.floor((Date.now() - new Date(isoStr)) / 1000);
    if (diff < 60)  return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    return Math.floor(diff / 3600) + 'h ago';
}

function renderMachines(machines) {
    for (const [machine, info] of Object.entries(machines)) {
        const card = document.getElementById('card-' + machine);
        if (!card) continue;

        card.className = 'prod-card state-' + info.state;

        if (info.state === 'running') {
            card.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span class="prod-machine-name">${esc(info.label)}</span>
                    <span class="prod-status-badge"><span class="prod-dot"></span> Running</span>
                </div>
                <div>
                    <div class="prod-job-number">${esc(info.job_number ?? '—')}</div>
                    <div class="prod-product">${esc(info.product_code ?? '')}</div>
                    <div class="prod-customer">${esc(info.customer ?? '')}</div>
                </div>
                ${progressBar(info)}
                <div class="prod-metrics">
                    ${info.rate_str ? `<div class="prod-metric"><span class="prod-metric-value">${esc(info.rate_str)}</span><span class="prod-metric-label">Actual rate</span></div>` : ''}
                    ${info.target_str ? `<div class="prod-metric"><span class="prod-metric-value" style="color:#475569;">${esc(info.target_str)}</span><span class="prod-metric-label">Target rate</span></div>` : ''}
                </div>
                <div class="prod-footer">
                    <div class="prod-operator">
                        <svg style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        <strong>${esc(info.operator ?? 'Unknown')}</strong>
                        &mdash; started ${timeAgo(info.started_at)}
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        ${onTrackBadge(info.on_track)}
                        <div class="prod-last-update">${info.progress_at ? 'Last update ' + timeAgo(info.progress_at) : 'No updates yet'}</div>
                    </div>
                </div>`;

        } else if (info.state === 'paused') {
            card.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span class="prod-machine-name">${esc(info.label)}</span>
                    <span class="prod-status-badge"><span class="prod-dot"></span> Paused</span>
                </div>
                <div>
                    <div class="prod-job-number">${esc(info.job_number ?? '—')}</div>
                    <div class="prod-product">${esc(info.product_code ?? '')}</div>
                    <div class="prod-customer">${esc(info.customer ?? '')}</div>
                </div>
                <div class="prod-pause-reason">
                    <svg style="width:14px;height:14px;flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>${esc(info.pause_reason)}</span>
                </div>
                <div class="prod-footer">
                    <div class="prod-operator">
                        <svg style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        <strong>${esc(info.operator ?? 'Unknown')}</strong>
                    </div>
                    <div class="prod-last-update">Paused ${timeAgo(info.paused_at)}</div>
                </div>`;

        } else {
            card.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span class="prod-machine-name">${esc(info.label)}</span>
                    <span class="prod-status-badge"><span class="prod-dot"></span> Idle</span>
                </div>
                <div class="prod-idle-text">No active job</div>`;
        }
    }
}

function esc(str) {
    if (str == null) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmt(n) {
    return Number(n).toLocaleString();
}

function progressBar(info) {
    if (info.pct_complete == null || info.order_qty == null) return '';
    const pct       = info.pct_complete;
    const done      = info.progress_packs ?? 0;
    const total     = info.order_qty;
    const fillColor = info.on_track === 'behind'  ? '#ef4444'
                    : info.on_track === 'at_risk'  ? '#f59e0b'
                    : '#16a34a';
    return `
        <div class="prod-progress-wrap">
            <div class="prod-progress-row">
                <span class="prod-progress-label">Progress</span>
                <span class="prod-progress-pct">${pct}%</span>
            </div>
            <div class="prod-progress-bar">
                <div class="prod-progress-fill" style="width:${pct}%;background:${fillColor};"></div>
            </div>
            <div class="prod-progress-counts">${fmt(done)} / ${fmt(total)} packs</div>
        </div>`;
}

function onTrackBadge(status) {
    if (!status) return '';
    const map = {
        on_track: ['track-ok',    '&#10003; At Rate'],
        at_risk:  ['track-risk',  '&#9888; Slightly Slow'],
        behind:   ['track-behind','&#10005; Below Rate'],
    };
    const [cls, label] = map[status] ?? [];
    if (!cls) return '';
    return `<span class="prod-on-track ${cls}">${label}</span>`;
}

// ── Full screen ───────────────────────────────────────────────────────────────
function toggleFullscreen() {
    fullscreen = !fullscreen;
    const root   = document.getElementById('prod-root');
    const layout = document.querySelector('main');
    const sidebar = document.querySelector('nav, aside, [class*="sidebar"], #sidebar');

    if (fullscreen) {
        root.classList.add('fullscreen-mode');
        if (layout) { layout.style.padding = '0'; layout.style.maxWidth = 'none'; }
        document.body.style.overflow = 'hidden';
        document.getElementById('fs-icon-enter').style.display = 'none';
        document.getElementById('fs-icon-exit').style.display  = '';
        document.getElementById('fs-label').textContent = 'Exit Full Screen';
        // Try native fullscreen too
        document.documentElement.requestFullscreen?.().catch(() => {});
    } else {
        root.classList.remove('fullscreen-mode');
        if (layout) { layout.style.padding = ''; layout.style.maxWidth = ''; }
        document.body.style.overflow = '';
        document.getElementById('fs-icon-enter').style.display = '';
        document.getElementById('fs-icon-exit').style.display  = 'none';
        document.getElementById('fs-label').textContent = 'Full Screen';
        document.exitFullscreen?.().catch(() => {});
    }
}

// Exit fullscreen if user presses Escape
document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement && fullscreen) {
        toggleFullscreen();
    }
});

// ── Start polling ─────────────────────────────────────────────────────────────
fetchStatus();
setInterval(fetchStatus, 20000);
</script>

</x-layout>
