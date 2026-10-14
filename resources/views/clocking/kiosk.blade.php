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

        /* Clock screen */
        #clock-screen{position:fixed;inset:0;display:flex;flex-direction:column;
            align-items:center;justify-content:center;background:#0f172a;}
        #clock-time{font-size:min(22vw,18vh);font-weight:200;letter-spacing:-0.02em;
            color:#e2e8f0;line-height:1;font-variant-numeric:tabular-nums;}
        #clock-date{font-size:min(4vw,3.5vh);color:#64748b;margin-top:1vh;font-weight:400;}
        #clock-prompt{font-size:min(2.5vw,2.2vh);color:#334155;margin-top:4vh;letter-spacing:0.05em;text-transform:uppercase;}

        /* Logo top-left */
        #logo{position:fixed;top:20px;left:24px;opacity:0.4;}
        #logo img{height:28px;width:auto;}

        /* Status indicator bottom-right */
        #status{position:fixed;bottom:18px;right:20px;font-size:0.75rem;display:flex;
            align-items:center;gap:6px;color:#475569;}
        #status-dot{width:8px;height:8px;border-radius:50%;background:#22c55e;flex-shrink:0;}
        #status-dot.offline{background:#f59e0b;}

        /* Overlay (confirmation / error) */
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

<div id="clock-screen">
    <div id="logo"><img src="{{ asset('images/logo.png') }}" alt=""></div>
    <div id="clock-time">00:00</div>
    <div id="clock-date"></div>
    <div id="clock-prompt">Tap your fob to clock in or out</div>
</div>

<div id="status">
    <div id="status-dot"></div>
    <span id="status-text">Online</span>
</div>

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
const OVERLAY_MS = 3000;   // how long to show the confirmation overlay
const DEBOUNCE_S = 60;     // mirror server-side: ignore same-direction taps within 60s
const ROSTER_INTERVAL_MS = 60_000;

// ── Audio (Web Audio API, no external files) ──────────────────────────────────
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
function playBeep(freq, dur, type = 'sine', vol = 0.25) {
    const osc  = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain); gain.connect(audioCtx.destination);
    osc.type      = type;
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(vol, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + dur);
    osc.start(); osc.stop(audioCtx.currentTime + dur);
}
function soundSuccess() { playBeep(880, 0.12); setTimeout(() => playBeep(1320, 0.15), 100); }
function soundError()   { playBeep(200, 0.35, 'sawtooth', 0.2); }

// ── Clock ─────────────────────────────────────────────────────────────────────
const LONDON_TZ = 'Europe/London';
function nowLondon() { return new Date(new Date().toLocaleString('en-GB', { timeZone: LONDON_TZ })); }

function updateClock() {
    const now = nowLondon();
    const hh  = String(now.getHours()).padStart(2, '0');
    const mm  = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('clock-time').textContent = hh + ':' + mm;
    document.getElementById('clock-date').textContent = now.toLocaleDateString('en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: LONDON_TZ,
    });
}
updateClock();
setInterval(updateClock, 1000);

// ── Screen Wake Lock ──────────────────────────────────────────────────────────
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

// ── Overlay ───────────────────────────────────────────────────────────────────
let overlayTimer = null;
function showOverlay(type, icon, action, name, time) {
    const el = document.getElementById('overlay');
    el.className = 'show ' + type;
    document.getElementById('ov-icon').textContent   = icon;
    document.getElementById('ov-action').textContent = action;
    document.getElementById('ov-name').textContent   = name;
    document.getElementById('ov-time').textContent   = time;
    if (overlayTimer) clearTimeout(overlayTimer);
    overlayTimer = setTimeout(() => { el.className = ''; }, OVERLAY_MS);
}

// ── IndexedDB punch queue ─────────────────────────────────────────────────────
let db = null;
function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open('clocking_v1', 1);
        req.onupgradeneeded = e => {
            const d = e.target.result;
            if (!d.objectStoreNames.contains('punches')) {
                d.createObjectStore('punches', { keyPath: 'clientUuid' });
            }
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
        const tx  = db.transaction('punches', 'readonly');
        const req = tx.objectStore('punches').getAll();
        req.onsuccess = () => resolve((req.result || []).filter(p => !p.synced));
    });
}
function dbMarkSynced(uuid) {
    if (!db) return;
    const tx    = db.transaction('punches', 'readwrite');
    const store = tx.objectStore('punches');
    const req   = store.get(uuid);
    req.onsuccess = () => {
        const p = req.result;
        if (p) { p.synced = true; store.put(p); }
    };
}

// ── In-memory roster ──────────────────────────────────────────────────────────
// { [fobUid]: { employee_id, name, state:'in'|'out', since:isoString|null } }
let roster = {};
let online = true;

function setOnline(val) {
    online = val;
    document.getElementById('status-dot').className = val ? '' : 'offline';
    // Count pending
    dbGetPending().then(ps => {
        const q = ps.length;
        document.getElementById('status-text').textContent =
            val ? (q ? `Online — ${q} syncing` : 'Online') : `Offline${q ? ` — ${q} queued` : ''}`;
    });
}

async function fetchRoster() {
    try {
        const res  = await fetch(ROSTER_URL, {
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
                const res  = await fetch(PUNCH_URL, {
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
                    // If server returns updated roster state, apply it
                    const json = await res.json().catch(() => null);
                    if (json?.ok && roster[punch.fobUid]) {
                        roster[punch.fobUid].state = punch.direction;
                        roster[punch.fobUid].since = punch.punchedAt;
                    }
                } else if (res.status >= 500) {
                    // Server error: leave in queue
                }
            } catch(_) { setOnline(false); break; }
        }
    } finally { syncing = false; }
}
setInterval(syncQueue, 5000);

// ── UUID generator ────────────────────────────────────────────────────────────
function uuid4() {
    return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
        (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
}

// ── RFID fob input capture ────────────────────────────────────────────────────
let inputBuffer = '';
let inputTimer  = null;

document.addEventListener('keydown', e => {
    // Prevent any default keyboard action (e.g. scroll, zoom) on the kiosk
    if (e.key !== 'Enter') e.preventDefault();

    if (e.key === 'Enter') {
        const uid = inputBuffer.trim();
        inputBuffer = '';
        if (inputTimer) { clearTimeout(inputTimer); inputTimer = null; }
        if (uid.length >= 8 && uid.length <= 14 && /^\d+$/.test(uid)) {
            handleFob(uid);
        }
        // else: partial/garbage — discard silently
        return;
    }

    if (e.key.length === 1 && /\d/.test(e.key)) {
        inputBuffer += e.key;
        if (inputTimer) clearTimeout(inputTimer);
        inputTimer = setTimeout(() => { inputBuffer = ''; inputTimer = null; }, 500);
    } else if (e.key.length === 1) {
        // Non-digit character — garbage, reset buffer
        inputBuffer = '';
        if (inputTimer) { clearTimeout(inputTimer); inputTimer = null; }
    }
});

// ── Fob handler ───────────────────────────────────────────────────────────────
let lastPunchByEmployee = {}; // { [employeeId]: {direction, ts} } — client debounce

function handleFob(uid) {
    // Resume AudioContext on first interaction (browser autoplay policy)
    if (audioCtx.state === 'suspended') audioCtx.resume();

    const entry = roster[uid];

    if (!entry) {
        soundError();
        showOverlay('red', '✗', 'UNKNOWN FOB', uid, '');
        return;
    }

    const empId     = entry.employee_id;
    const name      = entry.name;
    const curState  = entry.state || 'out'; // default assume out
    const direction = curState === 'in' ? 'out' : 'in';

    // Client-side debounce (mirrors server: 60 s same direction)
    const last = lastPunchByEmployee[empId];
    if (last && last.direction === direction && (Date.now() - last.ts) < DEBOUNCE_S * 1000) {
        soundError();
        const at = new Date(last.isoTime).toLocaleTimeString('en-GB', {
            hour: '2-digit', minute: '2-digit', timeZone: LONDON_TZ,
        });
        showOverlay('red', '⏱', `Already ${direction.toUpperCase()} at ${at}`, name, '');
        return;
    }

    // Determine display time in Europe/London
    const now       = new Date();
    const isoUtc    = now.toISOString();
    const localTime = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', timeZone: LONDON_TZ });
    const clientUuid = uuid4();

    // Update local state immediately
    entry.state = direction;
    entry.since = isoUtc;
    lastPunchByEmployee[empId] = { direction, ts: Date.now(), isoTime: isoUtc };

    // Show confirmation overlay immediately
    soundSuccess();
    showOverlay(
        'green',
        direction === 'in' ? '✓' : '✓',
        direction === 'in' ? 'CLOCKED IN' : 'CLOCKED OUT',
        name,
        localTime,
    );

    // Queue punch in IndexedDB and start sync
    const punch = { clientUuid, fobUid: uid, direction, punchedAt: isoUtc, synced: false };
    dbPut(punch);
    syncQueue();
}

// ── Boot ──────────────────────────────────────────────────────────────────────
openDb().then(d => { db = d; syncQueue(); }).catch(() => {});
</script>
</body>
</html>
