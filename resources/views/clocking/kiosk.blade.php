<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <title>Clocking Kiosk</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        html,body{width:100%;height:100%;overflow:hidden;background:#0f172a;color:#e2e8f0;
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;user-select:none;}

        /* ── Shared ── */
        .screen{position:fixed;inset:0;display:none;flex-direction:column;
            align-items:center;justify-content:center;}
        .screen.active{display:flex;}
        #logo{position:fixed;top:20px;left:24px;opacity:0.4;z-index:10;}
        #logo img{height:28px;width:auto;}
        #status{position:fixed;bottom:18px;right:20px;font-size:0.75rem;display:flex;
            align-items:center;gap:6px;color:#475569;z-index:10;}
        #status-dot{width:8px;height:8px;border-radius:50%;background:#22c55e;flex-shrink:0;}
        #status-dot.offline{background:#f59e0b;}

        /* ── Clock screen ── */
        #clock-screen{background:#0f172a;}
        #clock-time{font-size:min(22vw,18vh);font-weight:200;letter-spacing:-0.02em;
            color:#e2e8f0;line-height:1;font-variant-numeric:tabular-nums;}
        #clock-date{font-size:min(4vw,3.5vh);color:#64748b;margin-top:1vh;font-weight:400;}
        #clock-prompt{font-size:min(2.5vw,2.2vh);color:#334155;margin-top:4vh;letter-spacing:0.05em;text-transform:uppercase;}

        /* ── Confirm screen ── */
        #confirm-screen{background:#0f172a;gap:0;}
        #cf-name{font-size:min(10vw,8vh);font-weight:300;color:#f1f5f9;
            margin-top:min(4vw,3vh);text-align:center;padding:0 5vw;}
        #cf-status-badge{margin-top:2vh;padding:min(1.2vw,0.7vh) min(4vw,2.5vh);
            border-radius:99px;font-size:min(3.5vw,2.8vh);font-weight:800;
            letter-spacing:0.08em;text-transform:uppercase;}
        #cf-status-badge.in{background:rgba(34,197,94,0.18);color:#22c55e;}
        #cf-status-badge.out{background:rgba(100,116,139,0.18);color:#64748b;}
        #cf-since{font-size:min(4vw,3.2vh);color:#475569;margin-top:0.8vh;text-align:center;}
        #cf-divider{width:min(20vw,120px);height:1px;background:#1e293b;margin:3vh 0;}
        #cf-action-wrap{display:flex;align-items:center;gap:min(3vw,2vh);}
        #cf-arrow{font-size:min(8vw,6vh);opacity:0.5;}
        #cf-action{font-size:min(10vw,8vh);font-weight:800;letter-spacing:0.04em;}
        #cf-action.in{color:#22c55e;}
        #cf-action.out{color:#f59e0b;}

        /* Countdown ring */
        #cf-ring-wrap{position:relative;width:min(20vw,100px);height:min(20vw,100px);margin-top:3vh;}
        #cf-ring-wrap svg{width:100%;height:100%;transform:rotate(-90deg);}
        #cf-ring-bg{fill:none;stroke:#1e293b;stroke-width:4;}
        #cf-ring{fill:none;stroke:#94a3b8;stroke-width:4;stroke-linecap:round;
            transition:stroke-dashoffset 0.9s linear,stroke 0.3s;}
        #cf-countdown-num{position:absolute;inset:0;display:flex;align-items:center;
            justify-content:center;font-size:min(8vw,5vh);font-weight:200;color:#94a3b8;}

        #cf-prompt{font-size:min(2.8vw,2.4vh);color:#475569;margin-top:2.5vh;
            letter-spacing:0.04em;text-transform:uppercase;text-align:center;}
        #cf-cancel{font-size:min(2vw,1.8vh);color:#334155;margin-top:1.2vh;
            letter-spacing:0.03em;text-align:center;}

        /* ── Flash overlay (success/error after confirm) ── */
        #overlay{position:fixed;inset:0;display:none;flex-direction:column;
            align-items:center;justify-content:center;z-index:100;}
        #overlay.show{display:flex;}
        #overlay.green{background:#15803d;}
        #overlay.red{background:#991b1b;}
        #ov-icon{font-size:min(15vw,12vh);line-height:1;margin-bottom:2vh;}
        #ov-action{font-size:min(10vw,8vh);font-weight:800;letter-spacing:0.04em;margin-bottom:1.5vh;}
        #ov-name{font-size:min(7vw,6vh);font-weight:400;opacity:0.9;}
        #ov-time{font-size:min(4vw,3.5vh);opacity:0.7;margin-top:1vh;}
    </style>
</head>
<body>

<div id="logo"><img src="{{ asset('images/logo.png') }}" alt=""></div>
<div id="status"><div id="status-dot"></div><span id="status-text">Online</span></div>

<!-- Test input (remove before go-live) -->
<div id="test-bar" style="position:fixed;bottom:50px;left:50%;transform:translateX(-50%);
    display:flex;gap:8px;align-items:center;z-index:50;background:rgba(15,23,42,0.85);
    border:1px solid #334155;border-radius:10px;padding:8px 12px;">
    <span style="font-size:0.7rem;color:#475569;letter-spacing:0.05em;text-transform:uppercase;">Test UID</span>
    <input id="test-uid" type="text" inputmode="numeric" maxlength="20" placeholder="e.g. 1234567890"
        style="width:160px;padding:5px 9px;background:#1e293b;border:1px solid #334155;border-radius:6px;
               color:#e2e8f0;font-family:monospace;font-size:0.875rem;outline:none;"
        onkeydown="if(event.key==='Enter'){simulateFob();event.preventDefault();}">
    <button onclick="simulateFob()"
        style="padding:5px 12px;background:#334155;border:none;border-radius:6px;
               color:#94a3b8;font-size:0.75rem;cursor:pointer;">
        Tap
    </button>
</div>

<!-- Clock (idle) -->
<div id="clock-screen" class="screen active">
    <div id="clock-time">00:00</div>
    <div id="clock-date"></div>
    <div id="clock-prompt">Tap your fob to clock in or out</div>
</div>

<!-- Confirm (waiting for second tap) -->
<div id="confirm-screen" class="screen">
    <div id="cf-name"></div>
    <div id="cf-status-badge"></div>
    <div id="cf-since"></div>
    <div id="cf-divider"></div>
    <div id="cf-action-wrap">
        <div id="cf-arrow">→</div>
        <div id="cf-action"></div>
    </div>
    <div id="cf-ring-wrap">
        <svg viewBox="0 0 64 64">
            <circle id="cf-ring-bg" cx="32" cy="32" r="28"/>
            <circle id="cf-ring"    cx="32" cy="32" r="28"/>
        </svg>
        <div id="cf-countdown-num">7</div>
    </div>
    <div id="cf-prompt">Tap your fob again to confirm</div>
    <div id="cf-cancel">Tap a different fob to cancel</div>
</div>

<!-- Flash overlay -->
<div id="overlay">
    <div id="ov-icon"></div>
    <div id="ov-action"></div>
    <div id="ov-name"></div>
    <div id="ov-time"></div>
</div>

<script>
// ── Config ────────────────────────────────────────────────────────────────────
const PUNCH_URL  = @json(route('clocking.kiosk.punch'));
const ROSTER_URL = @json(route('clocking.kiosk.roster'));
const CSRF       = document.querySelector('meta[name="csrf-token"]').content;
const OVERLAY_MS = 3000;
const CONFIRM_S  = 7;
const DEBOUNCE_S = 60;
const ROSTER_INTERVAL_MS = 60_000;
const RING_CIRC  = 2 * Math.PI * 28; // SVG circle r=28

// ── Audio ─────────────────────────────────────────────────────────────────────
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
function playBeep(freq, dur, type = 'sine', vol = 0.25) {
    const osc = audioCtx.createOscillator(), gain = audioCtx.createGain();
    osc.connect(gain); gain.connect(audioCtx.destination);
    osc.type = type; osc.frequency.value = freq;
    gain.gain.setValueAtTime(vol, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + dur);
    osc.start(); osc.stop(audioCtx.currentTime + dur);
}
function soundPrompt()  { playBeep(660, 0.1); }
function soundSuccess() { playBeep(880, 0.12); setTimeout(() => playBeep(1320, 0.15), 100); }
function soundError()   { playBeep(200, 0.35, 'sawtooth', 0.2); }

// ── Clock ─────────────────────────────────────────────────────────────────────
const LONDON_TZ = 'Europe/London';
const _timeFmt = new Intl.DateTimeFormat('en-GB', { timeZone: LONDON_TZ, hour: '2-digit', minute: '2-digit', hour12: false });
const _dateFmt = new Intl.DateTimeFormat('en-GB', { timeZone: LONDON_TZ, weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const _shortFmt = new Intl.DateTimeFormat('en-GB', { timeZone: LONDON_TZ, hour: '2-digit', minute: '2-digit', hour12: false });

function updateClock() {
    const now = new Date();
    document.getElementById('clock-time').textContent = _timeFmt.format(now);
    document.getElementById('clock-date').textContent = _dateFmt.format(now);
}
updateClock();
setInterval(updateClock, 1000);

// ── Wake Lock ─────────────────────────────────────────────────────────────────
async function acquireWakeLock() {
    if (!('wakeLock' in navigator)) return;
    try {
        const lock = await navigator.wakeLock.request('screen');
        lock.addEventListener('release', () => setTimeout(acquireWakeLock, 2000));
    } catch(_) {}
}
acquireWakeLock();
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') acquireWakeLock();
});

// ── Screen switcher ───────────────────────────────────────────────────────────
function showScreen(id) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    document.getElementById(id).classList.add('active');
}

// ── Flash overlay ─────────────────────────────────────────────────────────────
let overlayTimer = null;
function showOverlay(type, icon, action, name, time) {
    const el = document.getElementById('overlay');
    el.className = 'show ' + type;
    document.getElementById('ov-icon').textContent   = icon;
    document.getElementById('ov-action').textContent = action;
    document.getElementById('ov-name').textContent   = name;
    document.getElementById('ov-time').textContent   = time;
    if (overlayTimer) clearTimeout(overlayTimer);
    overlayTimer = setTimeout(() => {
        el.className = '';
        showScreen('clock-screen');
    }, OVERLAY_MS);
}

// ── IndexedDB punch queue ─────────────────────────────────────────────────────
let db = null;
function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open('clocking_v1', 1);
        req.onupgradeneeded = e => {
            const d = e.target.result;
            if (!d.objectStoreNames.contains('punches'))
                d.createObjectStore('punches', { keyPath: 'clientUuid' });
        };
        req.onsuccess = e => resolve(e.target.result);
        req.onerror   = ()  => reject(req.error);
    });
}
function dbPut(punch) {
    if (!db) return;
    db.transaction('punches', 'readwrite').objectStore('punches').put(punch);
}
function dbGetPending() {
    return new Promise(resolve => {
        if (!db) return resolve([]);
        const req = db.transaction('punches', 'readonly').objectStore('punches').getAll();
        req.onsuccess = () => resolve((req.result || []).filter(p => !p.synced));
    });
}
function dbMarkSynced(uuid) {
    if (!db) return;
    const store = db.transaction('punches', 'readwrite').objectStore('punches');
    const req = store.get(uuid);
    req.onsuccess = () => { const p = req.result; if (p) { p.synced = true; store.put(p); } };
}

// ── Roster ────────────────────────────────────────────────────────────────────
let roster = {};
let online = true;

function setOnline(val) {
    online = val;
    document.getElementById('status-dot').className = val ? '' : 'offline';
    dbGetPending().then(ps => {
        const q = ps.length;
        document.getElementById('status-text').textContent =
            val ? (q ? `Online — ${q} syncing` : 'Online') : `Offline${q ? ` — ${q} queued` : ''}`;
    });
}

async function fetchRoster() {
    try {
        const res = await fetch(ROSTER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: '{}',
        });
        if (!res.ok) return;
        const json = await res.json();
        if (json.fobs) { roster = json.fobs; setOnline(true); }
    } catch(_) { setOnline(false); }
}
fetchRoster();
setInterval(fetchRoster, ROSTER_INTERVAL_MS);

// ── Sync queue ────────────────────────────────────────────────────────────────
let syncing = false;
async function syncQueue() {
    if (syncing) return;
    syncing = true;
    try {
        const pending = await dbGetPending();
        if (!pending.length) { setOnline(true); return; }
        for (const punch of pending) {
            try {
                const res = await fetch(PUNCH_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({
                        fob_uid: punch.fobUid, direction: punch.direction,
                        punched_at: punch.punchedAt, client_uuid: punch.clientUuid,
                    }),
                });
                if (res.ok || res.status === 409) {
                    dbMarkSynced(punch.clientUuid);
                    setOnline(true);
                    const json = await res.json().catch(() => null);
                    if (json?.ok && roster[punch.fobUid]) {
                        roster[punch.fobUid].state = punch.direction;
                        roster[punch.fobUid].since = punch.punchedAt;
                    }
                }
            } catch(_) { setOnline(false); break; }
        }
    } finally { syncing = false; }
}
setInterval(syncQueue, 5000);

// ── UUID ──────────────────────────────────────────────────────────────────────
function uuid4() {
    return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
        (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
}

// ── Confirm screen helpers ────────────────────────────────────────────────────
const ring = document.getElementById('cf-ring');
ring.style.strokeDasharray  = RING_CIRC;
ring.style.strokeDashoffset = 0;

function setRing(fraction) { // fraction: 1 = full, 0 = empty
    ring.style.strokeDashoffset = RING_CIRC * (1 - fraction);
    // Turn red in last 2 seconds
    ring.style.stroke = fraction < (2 / CONFIRM_S) ? '#ef4444' : '#94a3b8';
}

let confirmState = null;

function startConfirm(uid) {
    const entry = roster[uid];
    if (!entry) {
        soundError();
        showOverlay('red', '✗', 'UNKNOWN FOB', uid, '');
        return;
    }

    const empId    = entry.employee_id;
    const name     = entry.name;
    const curState = entry.state || 'out';
    const direction = curState === 'in' ? 'out' : 'in';

    // Client-side debounce
    const last = lastPunchByEmployee[empId];
    if (last && last.direction === direction && (Date.now() - last.ts) < DEBOUNCE_S * 1000) {
        soundError();
        const at = _shortFmt.format(new Date(last.isoTime));
        showOverlay('red', '⏱', `Already ${direction.toUpperCase()} at ${at}`, name, '');
        return;
    }

    // Status badge + since line
    const badge = document.getElementById('cf-status-badge');
    const since = document.getElementById('cf-since');
    if (curState === 'in') {
        badge.textContent  = 'Currently Clocked In';
        badge.className    = 'in';
        since.textContent  = entry.since ? 'since ' + _shortFmt.format(new Date(entry.since)) : '';
    } else {
        badge.textContent  = 'Currently Clocked Out';
        badge.className    = 'out';
        since.textContent  = entry.since ? 'last out at ' + _shortFmt.format(new Date(entry.since)) : 'not yet clocked in today';
    }

    document.getElementById('cf-name').textContent = name;
    const actionEl = document.getElementById('cf-action');
    actionEl.textContent  = direction === 'in' ? 'CLOCK IN' : 'CLOCK OUT';
    actionEl.className    = direction;

    let remaining = CONFIRM_S;
    document.getElementById('cf-countdown-num').textContent = remaining;
    setRing(1);
    showScreen('confirm-screen');
    soundPrompt();

    const isoUtc     = new Date().toISOString();
    const localTime  = _shortFmt.format(new Date());
    const clientUuid = uuid4();

    const countdownInterval = setInterval(() => {
        remaining--;
        document.getElementById('cf-countdown-num').textContent = remaining;
        setRing(remaining / CONFIRM_S);
        if (remaining <= 0) cancelConfirm();
    }, 1000);

    confirmState = { uid, empId, name, direction, clientUuid, isoUtc, localTime, entry, countdownInterval };
}

function cancelConfirm() {
    if (!confirmState) return;
    clearInterval(confirmState.countdownInterval);
    confirmState = null;
    showScreen('clock-screen');
}

function recordPunch() {
    if (!confirmState) return;
    const { uid, empId, name, direction, clientUuid, isoUtc, localTime, entry, countdownInterval } = confirmState;
    clearInterval(countdownInterval);
    confirmState = null;

    // Update local roster state
    entry.state = direction;
    entry.since = isoUtc;
    lastPunchByEmployee[empId] = { direction, ts: Date.now(), isoTime: isoUtc };

    soundSuccess();
    showOverlay('green', '✓', direction === 'in' ? 'CLOCKED IN' : 'CLOCKED OUT', name, localTime);

    const punch = { clientUuid, fobUid: uid, direction, punchedAt: isoUtc, synced: false };
    dbPut(punch);
    syncQueue();
}

// ── RFID capture ──────────────────────────────────────────────────────────────
let lastPunchByEmployee = {};
let inputBuffer = '';
let inputTimer  = null;

document.addEventListener('keydown', e => {
    // Let the test input handle its own keypresses
    if (document.activeElement && document.activeElement.id === 'test-uid') return;

    if (e.key !== 'Enter') e.preventDefault();

    if (e.key === 'Enter') {
        const uid = inputBuffer.trim();
        inputBuffer = '';
        if (inputTimer) { clearTimeout(inputTimer); inputTimer = null; }
        if (uid.length >= 8 && uid.length <= 14 && /^\d+$/.test(uid)) {
            handleFob(uid);
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

// ── Fob handler (two-tap flow) ────────────────────────────────────────────────
function handleFob(uid) {
    if (audioCtx.state === 'suspended') audioCtx.resume();

    // Overlay showing (post-punch flash) — ignore
    if (document.getElementById('overlay').classList.contains('show')) return;

    if (confirmState) {
        if (confirmState.uid === uid) {
            recordPunch(); // second tap same fob — confirm
        } else {
            cancelConfirm();
            startConfirm(uid); // different fob — start fresh
        }
        return;
    }

    startConfirm(uid);
}

// ── Test helper (remove before go-live) ──────────────────────────────────────
function simulateFob() {
    const input = document.getElementById('test-uid');
    const uid   = (input?.value || '').trim();
    if (uid) { handleFob(uid); input.value = ''; input.blur(); }
}

// ── Boot ──────────────────────────────────────────────────────────────────────
openDb().then(d => { db = d; syncQueue(); }).catch(() => {});
</script>
</body>
</html>
