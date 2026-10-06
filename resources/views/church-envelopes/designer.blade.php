<x-layout title="Church Envelope Designer — Lockie Portal">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&display=swap">
<main style="max-width:1100px;margin:0 auto;padding:2rem 1.5rem;">

    <div style="margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:0.5rem;flex-wrap:wrap;">
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">Church Envelope Designer</h1>
            <a href="{{ route('church-envelopes.index') }}"
               style="font-size:0.8125rem;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:0.3rem;padding:0.3rem 0.75rem;border:1px solid #e2e8f0;border-radius:6px;background:#fff;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Envelope Generator
            </a>
        </div>
        <p style="color:#64748b;font-size:0.875rem;margin:0;">Upload a generated spreadsheet to preview and download a print-ready PDF — 2-up (156 × 98 mm landscape, two 78 × 98 mm portrait halves, content rotated 90° to match InDesign artwork, transparent background).</p>
    </div>

    {{-- Upload --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">1. Upload Spreadsheet</h2>
        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;">
            <label style="flex:1;min-width:200px;display:flex;align-items:center;gap:0.5rem;background:#f8fafc;border:1.5px dashed #cbd5e1;border-radius:8px;padding:0.75rem 1rem;cursor:pointer;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                <span id="file-label" style="font-size:0.875rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Choose .xlsx file…</span>
                <input type="file" id="xlsx-file" accept=".xlsx,.xls" style="display:none;"
                       onchange="document.getElementById('file-label').textContent=this.files[0]?.name||'Choose .xlsx file…'">
            </label>
            <button onclick="parseFile()"
                style="padding:0.6rem 1.25rem;background:#1e293b;color:#fff;border:none;border-radius:8px;font-size:0.875rem;font-weight:600;cursor:pointer;white-space:nowrap;">
                Load File
            </button>
        </div>
        <p id="parse-status" style="display:none;font-size:0.8125rem;margin:0.75rem 0 0;padding:0.5rem 0.75rem;border-radius:6px;"></p>
    </div>

    {{-- Images --}}
    <div id="options-panel" style="display:none;background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.75rem;">2. Images</h2>
        <div id="parse-summary" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:0.75rem 1rem;margin-bottom:1.25rem;font-size:0.8125rem;color:#166534;"></div>
        {{-- Typography --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.4rem;">Church Name Font</label>
                <select id="church-font" onchange="churchFontChoice=this.value;updatePreview()"
                    style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;background:#f8fafc;color:#374151;cursor:pointer;">
                    <option value="times">Times New Roman</option>
                    <option value="arial">Arial</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.4rem;">Town Font</label>
                <select id="town-font" onchange="townFontChoice=this.value;updatePreview()"
                    style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;background:#f8fafc;color:#374151;cursor:pointer;">
                    <option value="times">Times New Roman</option>
                    <option value="arial">Arial</option>
                </select>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem;">
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.4rem;">Diocese Font</label>
                <select id="diocese-font" onchange="dioceseFontChoice=this.value;updatePreview()"
                    style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;background:#f8fafc;color:#374151;cursor:pointer;">
                    <option value="times">Times New Roman</option>
                    <option value="arial">Arial</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.4rem;">Verse Font</label>
                <select id="verse-font" onchange="verseFontChoice=this.value;updatePreview()"
                    style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;background:#f8fafc;color:#374151;cursor:pointer;">
                    <option value="times">Times New Roman</option>
                    <option value="arial">Arial</option>
                </select>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.4rem;">Weekly Envelope Image <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                <label style="display:flex;align-items:center;gap:0.5rem;background:#f8fafc;border:1.5px dashed #cbd5e1;border-radius:8px;padding:0.6rem 0.75rem;cursor:pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span id="weekly-img-label" style="font-size:0.8125rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Choose image…</span>
                    <input type="file" id="weekly-img" accept="image/*" style="display:none;" onchange="handleImage(this,'weekly')">
                </label>
                <div id="weekly-img-preview" style="margin-top:0.5rem;display:none;">
                    <img id="weekly-img-thumb" style="height:60px;border-radius:6px;border:1px solid #e2e8f0;object-fit:contain;background:#f8fafc;">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.4rem;">Special Envelope Image <span style="font-weight:400;color:#94a3b8;">(auto-loaded)</span></label>
                <label style="display:flex;align-items:center;gap:0.5rem;background:#f8fafc;border:1.5px dashed #cbd5e1;border-radius:8px;padding:0.6rem 0.75rem;cursor:pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span id="special-img-label" style="font-size:0.8125rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Loading…</span>
                    <input type="file" id="special-img" accept="image/*" style="display:none;" onchange="handleImage(this,'special')">
                </label>
                <div id="special-img-preview" style="margin-top:0.5rem;display:none;">
                    <img id="special-img-thumb" style="height:60px;border-radius:6px;border:1px solid #e2e8f0;object-fit:contain;background:#f8fafc;">
                </div>
            </div>
        </div>
    </div>

    {{-- Preview --}}
    <div id="preview-panel" style="display:none;background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.5rem;">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0;">3. Preview <span id="preview-count" style="font-weight:400;font-size:0.8125rem;color:#94a3b8;"></span></h2>
            <button onclick="updatePreview()"
                style="padding:0.4rem 0.875rem;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:7px;font-size:0.8125rem;cursor:pointer;">
                Refresh Preview
            </button>
        </div>
        <p style="font-size:0.75rem;color:#94a3b8;margin:0 0 1rem;">Preview shows reading orientation — how the envelope looks when held in hand. Matches InDesign artwork.</p>
        <div id="preview-cards" style="display:flex;flex-direction:column;gap:1.25rem;"></div>
        <p style="font-size:0.75rem;color:#94a3b8;margin:0.75rem 0 0;">Showing first 6 pages. PDF will include all rows.</p>
    </div>

    {{-- Generate --}}
    <div id="generate-section" style="display:none;">
        <button onclick="generatePDF()" id="generate-btn"
            style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.75rem 1.75rem;background:#0f172a;color:#fff;border:none;border-radius:10px;font-size:0.9375rem;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(15,23,42,0.2);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Generate &amp; Download PDF
        </button>
        <p id="generate-status" style="display:none;font-size:0.8125rem;margin:0.75rem 0 0;color:#64748b;"></p>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script>
// ── Version marker (check in browser DevTools → Sources to confirm latest build) ──
const DESIGNER_VERSION = 'contain-box-v8-2026-10-01';
console.log('[envelope-designer] version:', DESIGNER_VERSION);

// ── State ─────────────────────────────────────────────────────────────────────
let parsedRows = [];
let spreadsheetBasename = 'envelopes';
let weeklyImgDataUrl = null, weeklyNatW = 0, weeklyNatH = 0;
let specialImgDataUrl = null, specialNatW = 0, specialNatH = 0;
let churchFontChoice  = 'times'; // 'arial' | 'times'
let townFontChoice    = 'times';
let dioceseFontChoice = 'times';
let verseFontChoice   = 'times';

// PDF layout constants (mm)
// Page: 156×98 landscape. Two portrait halves: 78×98 each.
// All text: angle:-90 (90° CW) → reads upright on the portrait envelope.
// Images: pre-rotated 90° CW on canvas so they face the same way as text.
// Centred text helper: startY = (SLOT_H − doc.getTextWidth(text)) / 2
const PAGE_W = 156, PAGE_H = 98;
const SLOT_W = 78, SLOT_H = 98;
const SLOT_RIGHT_X = 77.75;

// Weekly image frame (PDF landscape coords): x 12–50, y 5–44 mm (38×39 mm)
const IMG_BOX_X = 12.0, IMG_BOX_Y = 5.0, IMG_BOX_W = 38.0, IMG_BOX_H = 39.0;
// Centred image frame used when there are no verse lines: spans the body of the face
const IMG_CENT_X = 13.0, IMG_CENT_Y = 3.0, IMG_CENT_W = 44.0, IMG_CENT_H = 92.0;
// Special foot-logo frame: x 47.9–59.5, y 79.0–90.6 mm (nudged from InDesign)
const SPEC_LOGO_X = 47.9, SPEC_LOGO_Y = 79.0, SPEC_LOGO_W = 11.6, SPEC_LOGO_H = 11.6;

const S = 1.7; // px/mm for HTML preview

// ── Auto-load Spiral.jpg ──────────────────────────────────────────────────────
(function () {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function () {
        specialNatW = img.naturalWidth; specialNatH = img.naturalHeight;
        const c = document.createElement('canvas');
        c.width = specialNatW; c.height = specialNatH;
        c.getContext('2d').drawImage(img, 0, 0);
        specialImgDataUrl = c.toDataURL('image/png');
        document.getElementById('special-img-thumb').src = specialImgDataUrl;
        document.getElementById('special-img-preview').style.display = 'block';
        document.getElementById('special-img-label').textContent = 'Spiral.jpg (default)';
        updatePreview();
    };
    img.onerror = function () { document.getElementById('special-img-label').textContent = 'Choose image…'; };
    img.src = '{{ asset('images/Spiral.jpg') }}';
})();

// ── Image upload ──────────────────────────────────────────────────────────────
function handleImage(input, type) {
    const file = input.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const url = e.target.result;
        const probe = new Image();
        probe.onload = function() {
            if (type === 'weekly') {
                weeklyImgDataUrl = url; weeklyNatW = probe.naturalWidth; weeklyNatH = probe.naturalHeight;
                document.getElementById('weekly-img-label').textContent = file.name;
                document.getElementById('weekly-img-thumb').src = url;
                document.getElementById('weekly-img-preview').style.display = 'block';
            } else {
                specialImgDataUrl = url; specialNatW = probe.naturalWidth; specialNatH = probe.naturalHeight;
                document.getElementById('special-img-label').textContent = file.name;
                document.getElementById('special-img-thumb').src = url;
                document.getElementById('special-img-preview').style.display = 'block';
            }
            updatePreview();
        };
        probe.src = url;
    };
    reader.readAsDataURL(file);
}

// ── Parse spreadsheet ─────────────────────────────────────────────────────────
function parseFile() {
    const file = document.getElementById('xlsx-file').files[0];
    if (!file) { showStatus('parse-status', 'Please choose a file first.', 'error'); return; }
    spreadsheetBasename = file.name.replace(/\.[^.]+$/, '') || 'envelopes';
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const wb  = XLSX.read(e.target.result, { type: 'array' });
            const ws  = wb.Sheets[wb.SheetNames[0]];
            const raw = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
            if (raw.length < 2) throw new Error('Empty spreadsheet');
            const header = raw[0];
            const vtCols = {};
            header.forEach((h, i) => { const m = String(h||'').match(/^VT(\d+)$/i); if (m) vtCols[parseInt(m[1])] = i; });
            for (let i = 1; i <= 8; i++) vtCols[i] = vtCols[i] ?? (12 + i);
            parsedRows = [];
            raw.slice(1).forEach(row => {
                if (row.every(v => v === '' || v === null || v === undefined)) return;
                const colG = String(row[6] ?? '').trim();
                const colH = String(row[7] ?? '').trim();
                const isSpec = colH !== '' && colG === '';
                const vts = [];
                for (let i = 1; i <= 8; i++) vts.push(String(row[vtCols[i]] ?? '').trim());
                const rawL = row[5], rawR = row[4]; // col F = left, col E = right
                parsedRows.push({
                    day: String(row[1]??'').trim(), month: String(row[2]??'').trim(), year: String(row[3]??'').trim(),
                    setLeft:  (rawL !== '' && rawL !== null) ? parseInt(rawL)  : null,
                    setRight: (rawR !== '' && rawR !== null) ? parseInt(rawR) : null,
                    isSpecial: isSpec,
                    church: String(row[8]??'').trim(), town: String(row[9]??'').trim(),
                    diocese1: String(row[10]??'').trim(), diocese2: String(row[11]??'').trim(), diocese3: String(row[12]??'').trim(),
                    vts,
                });
            });
            if (!parsedRows.length) throw new Error('No data rows found');
            const first  = parsedRows.find(r => !r.isSpecial) || parsedRows[0];
            const weekly = parsedRows.filter(r => !r.isSpecial).length;
            const special= parsedRows.filter(r => r.isSpecial).length;
            document.getElementById('parse-summary').innerHTML =
                `<strong>${parsedRows.length} pages</strong> — <strong>${first.church}</strong>, ${first.town} — ${weekly} weekly, ${special} special.`;
            showStatus('parse-status', `Loaded ${parsedRows.length} rows successfully.`, 'success');
            document.getElementById('options-panel').style.display    = 'block';
            document.getElementById('preview-panel').style.display    = 'block';
            document.getElementById('generate-section').style.display = 'block';
            updatePreview();
        } catch(err) {
            showStatus('parse-status', 'Could not read the file: ' + err.message, 'error');
        }
    };
    reader.readAsArrayBuffer(file);
}

function showStatus(id, msg, type) {
    const el = document.getElementById(id);
    el.textContent = msg; el.style.display = 'block';
    el.style.background = type === 'error' ? '#fef2f2' : '#f0fdf4';
    el.style.color      = type === 'error' ? '#991b1b' : '#166534';
    el.style.border     = type === 'error' ? '1px solid #fecaca' : '1px solid #bbf7d0';
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function buildDate(row) { return [row.day, row.month, row.year].filter(Boolean).join(' '); }
function sanitise(str) { return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'envelopes'; }

// Fit natural-orientation image (no rotation) into preview reading-frame (boxW×boxH)
function fitNatural(nw, nh, boxW, boxH) {
    if (!nw || !nh) return { dW: boxW, dH: boxH };
    const scale = Math.min(boxW / nw, boxH / nh);
    return { dW: nw * scale, dH: nh * scale };
}
// Fit after 90° CW rotation: natural dims nW×nH → rotated visual nH×nW → fit into PDF boxW×boxH
function fitRotated(nw, nh, boxW, boxH) {
    if (!nw || !nh) return { dW: boxW, dH: boxH };
    const scale = Math.min(boxW / nh, boxH / nw);
    return { dW: nh * scale, dH: nw * scale };
}

// ── HTML Preview (reading orientation: 98mm wide × 78mm tall) ─────────────────
function updatePreview() {
    if (!parsedRows.length) return;
    const container = document.getElementById('preview-cards');
    container.innerHTML = '';
    const count = Math.min(parsedRows.length, 6);
    document.getElementById('preview-count').textContent = `(${parsedRows.length} pages total)`;
    for (let i = 0; i < count; i++) {
        const row = parsedRows[i];
        const wrap = document.createElement('div');
        const lbl = document.createElement('div');
        lbl.style.cssText = 'font-size:0.7rem;color:#94a3b8;margin-bottom:4px;';
        lbl.textContent = `Page ${i + 1} — ${row.church}${row.isSpecial ? ' (special)' : ''}`;
        wrap.appendChild(lbl);
        const page = document.createElement('div');
        page.style.cssText = 'display:inline-flex;background:#d1d5db;gap:1px;border:1px solid #9ca3af;border-radius:3px;overflow:hidden;';
        page.appendChild(buildEnvHtml(row, row.setLeft));
        page.appendChild(buildEnvHtml(row, row.setRight));
        wrap.appendChild(page);
        container.appendChild(wrap);
    }
}

function buildEnvHtml(row, setNum) {
    // Reading orientation: 98mm wide × 78mm tall (landscape in hand)
    // PDF-to-reading transform: reading_x = PDF_y, reading_y = SLOT_W − PDF_x
    const RW = SLOT_H * S;  // 98 × 1.7 = 166.6 px
    const RH = SLOT_W * S;  // 78 × 1.7 = 132.6 px
    const rY = xPdf => (SLOT_W - xPdf) * S;           // reading top from PDF x-baseline
    const rCX = yPdf => (yPdf / SLOT_H) * RW;         // reading center-x from PDF y-baseline

    const isSpec = row.isSpecial;
    const env = document.createElement('div');
    env.style.cssText = `position:relative;width:${RW}px;height:${RH}px;overflow:hidden;background:white;flex-shrink:0;`;

    // band: place centred text with PDF x-baseline → reading vertical, yCentre → reading horizontal.
    // top is baseline-anchored: div top = rY(xPdf) - fsizePx*0.82 so text doesn't overlap
    // at tight spacings (e.g. town 61.3 vs diocese 58.7 = 2.6mm apart).
    function band(text, xPdf, yCentre, fsizePx, bold, fontFamily, maxHalfMM) {
        const cx = rCX(yCentre);
        const halfW = (maxHalfMM ?? 44) * S;
        const baseline = rY(xPdf);
        const el = document.createElement('div');
        el.style.cssText = `position:absolute;top:${baseline - fsizePx * 0.82}px;` +
            `left:${Math.max(0, cx - halfW)}px;width:${Math.min(RW, halfW * 2)}px;` +
            `font-family:${fontFamily||'Arial,sans-serif'};font-weight:${bold?'700':'400'};` +
            `font-size:${fsizePx}px;color:#111;text-align:center;white-space:nowrap;overflow:visible;line-height:1;`;
        el.textContent = text;
        env.appendChild(el);
    }

    // ── Image ────────────────────────────────────────────────────────────────
    if (!isSpec && weeklyImgDataUrl) {
        const hasVt = row.vts.some(v => v !== '');
        // Reading frame dims (PDF frame rotated into reading orientation: swap W/H)
        // With verses → upper frame; no verses → centred body frame.
        const pBX = hasVt ? IMG_BOX_X  : IMG_CENT_X;
        const pBY = hasVt ? IMG_BOX_Y  : IMG_CENT_Y;
        const pBW = hasVt ? IMG_BOX_W  : IMG_CENT_W;
        const pBH = hasVt ? IMG_BOX_H  : IMG_CENT_H;
        // In reading orientation: PDF x→reading y (inverted), PDF y→reading x.
        // Frame bounds: reading x = IMG_BOX_Y…IMG_BOX_Y+H, reading y = SLOT_W−(X+W)…SLOT_W−X
        const frameCX = (pBY + pBH / 2) * S;                         // reading x centre
        const frameCY = (SLOT_W - (pBX + pBW / 2)) * S;              // reading y centre
        const fW = pBH * S, fH = pBW * S;                            // reading frame px dims
        const imgEl = document.createElement('img');
        imgEl.src = weeklyImgDataUrl;
        imgEl.style.cssText = `position:absolute;overflow:hidden;` +
            `left:${frameCX - fW/2}px;top:${frameCY - fH/2}px;` +
            `width:${fW}px;height:${fH}px;` +
            `object-fit:${hasVt ? 'cover' : 'contain'};background:#fff;`;
        env.appendChild(imgEl);
    }
    if (isSpec && specialImgDataUrl) {
        // Special foot logo: reading frame = SPEC_LOGO_H wide × SPEC_LOGO_W tall
        const frameCX = (SPEC_LOGO_Y + SPEC_LOGO_H / 2) * S;
        const frameCY = (SLOT_W - (SPEC_LOGO_X + SPEC_LOGO_W / 2)) * S;
        const fW = SPEC_LOGO_H * S, fH = SPEC_LOGO_W * S;
        const imgEl = document.createElement('img');
        imgEl.src = specialImgDataUrl;
        imgEl.style.cssText = `position:absolute;` +
            `left:${frameCX - fW/2}px;top:${frameCY - fH/2}px;` +
            `width:${fW}px;height:${fH}px;object-fit:cover;`;
        env.appendChild(imgEl);
    }

    const f = c => c === 'times' ? "'Times New Roman',serif" : 'Arial,sans-serif';

    // ── Text — all centred on face (yCentre=49); gift text/date at yCentre=68.5 ──
    // Church — 15pt bold, x-baseline=65.9, centred on face
    band(row.church, 65.9, 49, 6*S, true, f(churchFontChoice), 44);

    // Town — 11pt bold, x=61.3, centred on face
    if (row.town) band(row.town, 61.3, 49, 4.5*S, true, f(townFontChoice), 44);

    // Diocese — 6pt, x=58.7/56.1/53.5, centred on face (first line = highest x)
    [row.diocese1, row.diocese2, row.diocese3].filter(Boolean).forEach((d, i) => {
        band(d, 58.7 - i*2.6, 49, 2.8*S, false, f(dioceseFontChoice), 44);
    });

    if (isSpec) {
        // Special: blackletter title lines VT6-8, x=38.4/29.8/21.2, centred on face
        [row.vts[5], row.vts[6], row.vts[7]].filter(Boolean).forEach((t, i) => {
            band(t, 38.4 - i*8.6, 49, 5*S, false, "'UnifrakturMaguntia',serif", 44);
        });
    } else {
        // Gift text: VT1-8, 9pt, 3.9mm leading, block centred on x=31.5; centred at 68.5mm
        const vts = row.vts.filter(Boolean);
        const n = vts.length;
        vts.forEach((line, i) => {
            const xPdf = Math.max(11.2, 31.5 + ((n - 1) / 2 - i) * 3.9);
            band(line, xPdf, 68.5, 3.8*S, false, f(verseFontChoice), 44);
        });
    }

    // Date — 11pt, x=8.2, centred at 68.5mm (same column as gift text)
    const date = buildDate(row);
    if (date) band(date.toUpperCase(), 8.2, 68.5, 4.5*S, false, null, 44);

    // Set number — x=6.9, y=10.3 from top → reading: bottom-left
    if (setNum !== null) {
        const sn = document.createElement('div');
        sn.style.cssText = `position:absolute;` +
            `left:${10.3*S}px;bottom:${(SLOT_W-6.9)*S - 6*S}px;` +
            `font-family:Arial,sans-serif;font-weight:400;font-size:${4.5*S}px;color:#111;`;
        sn.textContent = String(setNum);
        env.appendChild(sn);
    }

    return env;
}

// ── PDF helpers ───────────────────────────────────────────────────────────────
function loadImage(src) {
    return new Promise(resolve => {
        const img = new Image();
        img.onload = () => resolve(img); img.onerror = () => resolve(null); img.src = src;
    });
}

function bufToBase64(buf) {
    const bytes = new Uint8Array(buf);
    let b = '', chunk = 32768;
    for (let i = 0; i < bytes.byteLength; i += chunk)
        b += String.fromCharCode(...bytes.subarray(i, Math.min(i + chunk, bytes.byteLength)));
    return btoa(b);
}

async function loadFont(doc, url, filename, fontName, style) {
    try {
        const resp = await fetch(url);
        if (!resp.ok) return false;
        const b64 = bufToBase64(await resp.arrayBuffer());
        doc.addFileToVFS(filename, b64);
        doc.addFont(filename, fontName, style);
        // Verify the font is usable; jsPDF may silently register an invalid font
        // (e.g. "No unicode cmap") that crashes getTextWidth/text later.
        doc.setFont(fontName, style);
        doc.getTextWidth('A');
        doc.setFont('helvetica', 'normal');
        return true;
    } catch(e) { console.warn('Font load failed:', filename, e.message); return false; }
}

// Returns tight bounding box {sx,sy,sw,sh} of all pixels with alpha>0.
// Returns null if the image has no transparent pixels (treat as fully opaque).
function opaqueBounds(imgEl) {
    const iw = imgEl.naturalWidth, ih = imgEl.naturalHeight;
    const tmp = document.createElement('canvas');
    tmp.width = iw; tmp.height = ih;
    tmp.getContext('2d').drawImage(imgEl, 0, 0, iw, ih);
    const data = tmp.getContext('2d').getImageData(0, 0, iw, ih).data;
    let minX = iw, maxX = -1, minY = ih, maxY = -1;
    for (let y = 0; y < ih; y++) {
        for (let x = 0; x < iw; x++) {
            if (data[(y * iw + x) * 4 + 3] > 0) {
                if (x < minX) minX = x;
                if (x > maxX) maxX = x;
                if (y < minY) minY = y;
                if (y > maxY) maxY = y;
            }
        }
    }
    if (maxX < 0) return null; // fully transparent
    if (minX === 0 && minY === 0 && maxX === iw - 1 && maxY === ih - 1) return null; // already tight
    return { sx: minX, sy: minY, sw: maxX - minX + 1, sh: maxY - minY + 1 };
}

// Pre-rotate image 90° CW and scale into box.
// fill=true  → cover/crop (Math.max): frame is fully covered, edges may be clipped.
// fill=false → contain/fit (Math.min): whole image visible, white bars on short axis.
// Trims transparent padding before scaling so only opaque content drives the scale.
async function buildRotatedImgCanvas(imgDataUrl, nw, nh, boxW, boxH, fill = true) {
    if (!imgDataUrl || !nw || !nh) return null;
    const ICPPM = 8;
    const imgEl = await loadImage(imgDataUrl);
    if (!imgEl) return null;

    // Crop to opaque content — transparent padding inflates dimensions and shrinks fill.
    const bounds = opaqueBounds(imgEl);
    const sx = bounds ? bounds.sx : 0;
    const sy = bounds ? bounds.sy : 0;
    const sw = bounds ? bounds.sw : nw; // cropped width
    const sh = bounds ? bounds.sh : nh; // cropped height

    const cW = Math.round(boxW * ICPPM);
    const cH = Math.round(boxH * ICPPM);
    // Reading-space dims: reading face is rotated 90° from PDF canvas, so swap axes.
    // PDF x-axis → reading y-axis, PDF y-axis → reading x-axis.
    // readW = cH (PDF height becomes reading width), readH = cW (PDF width becomes reading height).
    const readW = cH, readH = cW;

    // Step 1: scale opaque content into reading-space canvas (portrait reading orientation).
    const readCanvas = document.createElement('canvas');
    readCanvas.width = readW; readCanvas.height = readH;
    const rc = readCanvas.getContext('2d');
    rc.fillStyle = '#fff';
    rc.fillRect(0, 0, readW, readH);
    const rScale = fill ? Math.max(readW / sw, readH / sh) : Math.min(readW / sw, readH / sh);
    const rdW = sw * rScale, rdH = sh * rScale;
    rc.drawImage(imgEl, sx, sy, sw, sh, (readW - rdW) / 2, (readH - rdH) / 2, rdW, rdH);

    // Step 2: rotate reading canvas 90° CW onto PDF-space canvas (cW × cH).
    const canvas = document.createElement('canvas');
    canvas.width = cW; canvas.height = cH;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, cW, cH);
    ctx.translate(cW, 0);
    ctx.rotate(Math.PI / 2);
    ctx.drawImage(readCanvas, 0, 0);

    return { canvas, dW: boxW, dH: boxH };
}

// ── PDF drawing ───────────────────────────────────────────────────────────────
// Resolve the PDF font name+style to use based on user's font choice and desired weight.
// 'arial' → use the loaded Helvetica/Arimo fonts; 'times' → use jsPDF built-in Times.
function pdfFont(choice, fonts, weight) {
    if (choice === 'times') {
        return { name: 'times', style: weight === 'bold' ? 'bold' : 'normal' };
    }
    if (weight === 'bold')   return { name: fonts.bold,    style: 'bold' };
    if (weight === 'medium') return { name: fonts.medium,  style: 'normal' };
    return                          { name: fonts.regular, style: 'normal' };
}

function drawEnvPdf(doc, row, slotX, setNum, weeklyCanvasBox, weeklyCanvasCent, specialLogoCanvas, fonts) {
    const isSpec = row.isSpecial;
    const hasVt  = row.vts.some(v => v !== '');

    doc.setTextColor(0, 0, 0);
    const cY = text => (SLOT_H - doc.getTextWidth(text)) / 2; // centred along 98mm face

    // ── Image ─────────────────────────────────────────────────────────────────
    if (!isSpec) {
        // No verses → centre image on face; otherwise use standard upper frame.
        const wc = hasVt ? weeklyCanvasBox : weeklyCanvasCent;
        if (wc) {
            const bX = hasVt ? IMG_BOX_X  : IMG_CENT_X;
            const bY = hasVt ? IMG_BOX_Y  : IMG_CENT_Y;
            const bW = hasVt ? IMG_BOX_W  : IMG_CENT_W;
            const bH = hasVt ? IMG_BOX_H  : IMG_CENT_H;
            // Diagnostics: verify canvas dims and placement in browser console.
            // JPEG + no alias: avoids any jsPDF PNG-specific sizing or alias-cache issue.
            try { doc.addImage(wc.canvas.toDataURL('image/jpeg', 0.9), 'JPEG', slotX + bX, bY, bW, bH); } catch(e){ console.error('[wkImg] addImage failed:', e); }
        }
    }
    if (isSpec && specialLogoCanvas) {
        const { canvas, dW, dH } = specialLogoCanvas;
        try { doc.addImage(canvas.toDataURL('image/png'), 'PNG', slotX + SPEC_LOGO_X, SPEC_LOGO_Y, dW, dH, 'spl', 'NONE'); } catch(_){}
    }

    // ── Set number — 20pt Medium, slotX+6.9, startY=10.3 ────────────────────
    if (setNum !== null) {
        doc.setFont(fonts.medium, 'normal'); doc.setFontSize(20);
        doc.text(String(setNum), slotX + 6.9, 10.3, { angle: -90 });
    }

    // ── Date — 11pt Medium, slotX+8.2, centred at 68.5mm ────────────────────
    const date = buildDate(row);
    if (date) {
        doc.setFont(fonts.medium, 'normal'); doc.setFontSize(11);
        const dateY = 68.5 - doc.getTextWidth(date.toUpperCase()) / 2;
        doc.text(date.toUpperCase(), slotX + 8.2, dateY, { angle: -90 });
    }

    if (isSpec) {
        // ── Special: Blackletter title lines VT6-8, ~20pt, leading 8.6mm ────
        // Line 1 (VT6) at slotX+38.4, line 2 at slotX+29.8, line 3 at slotX+21.2
        const titleLines = [row.vts[5], row.vts[6], row.vts[7]].filter(Boolean);
        doc.setFont(fonts.blackletter, 'normal');
        titleLines.forEach((line, i) => {
            let pt = 20; doc.setFontSize(pt);
            while (pt > 8 && doc.getTextWidth(line) > 88) { pt -= 0.5; doc.setFontSize(pt); }
            doc.text(line, slotX + 38.4 - i * 8.6, cY(line), { angle: -90 });
        });
    } else {
        // ── Gift text: VT1-8, 9pt Regular, 3.9mm leading, block centred on slotX+31.5
        // First line at highest x; clamp low end to keep ≥3mm clear of date (8.2+3=11.2)
        const vts = row.vts.filter(Boolean);
        const n = vts.length;
        if (n) {
            const vf = pdfFont(verseFontChoice, fonts, 'regular');
            doc.setFont(vf.name, vf.style);
            vts.forEach((line, i) => {
                let pt = 9; doc.setFontSize(pt);
                while (pt > 5 && doc.getTextWidth(line) > 88) { pt -= 0.25; doc.setFontSize(pt); }
                const xOff = Math.max(slotX + 11.2, slotX + 31.5 + ((n - 1) / 2 - i) * 3.9);
                // Centred at 68.5mm from top (below image frame which ends at 35.9mm)
                const vtY = 68.5 - doc.getTextWidth(line) / 2;
                doc.text(line, xOff, vtY, { angle: -90 });
            });
        }
    }

    // ── Diocese — 6pt Medium, slotX+58.7 (−2.6/line), centred ───────────────
    const diocF = pdfFont(dioceseFontChoice, fonts, 'medium');
    doc.setFont(diocF.name, diocF.style); doc.setFontSize(6);
    [row.diocese1, row.diocese2, row.diocese3].filter(Boolean).forEach((d, i) => {
        let pt = 6; doc.setFontSize(pt);
        while (pt > 4 && doc.getTextWidth(d) > 88) { pt -= 0.25; doc.setFontSize(pt); }
        doc.text(d, slotX + 58.7 - i * 2.6, cY(d), { angle: -90 });
    });

    // ── Town — 11pt Bold, slotX+61.3, centred ────────────────────────────────
    if (row.town) {
        const townF = pdfFont(townFontChoice, fonts, 'bold');
        doc.setFont(townF.name, townF.style); doc.setFontSize(11);
        doc.text(row.town, slotX + 61.3, cY(row.town), { angle: -90 });
    }

    // ── Church — 15pt Bold, slotX+65.9, centred, auto-shrink ─────────────────
    const churchF = pdfFont(churchFontChoice, fonts, 'bold');
    doc.setFont(churchF.name, churchF.style);
    let churchPt = 15; doc.setFontSize(churchPt);
    while (churchPt > 6 && doc.getTextWidth(row.church) > 88) { churchPt -= 0.5; doc.setFontSize(churchPt); }
    doc.text(row.church, slotX + 65.9, cY(row.church), { angle: -90 });
}

// ── Generate PDF ──────────────────────────────────────────────────────────────
async function generatePDF() {
    if (!parsedRows.length) return;
    const btn = document.getElementById('generate-btn');
    const st  = document.getElementById('generate-status');
    btn.disabled = true; btn.textContent = 'Generating…';
    st.style.display = 'block'; st.style.color = '#64748b';
    st.textContent = 'Loading fonts…';
    await new Promise(r => setTimeout(r, 30));

    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'mm', format: [PAGE_W, PAGE_H], orientation: 'landscape' });

        // Load and embed fonts.
        // Priority for body: HelveticaNeue-* (user-supplied) → Arimo (bundled) → helvetica.
        // Medium weight used for number, date, diocese; Bold for church+town; Regular for gift text.
        const fonts = { regular: 'helvetica', medium: 'helvetica', bold: 'helvetica', blackletter: 'helvetica' };
        const base = window.location.origin;

        // Try Helvetica Neue first (user supplies these TTFs into public/fonts/)
        const hnRegOk = await loadFont(doc, base+'/fonts/HelveticaNeue-Regular.ttf', 'HelveticaNeue-Regular.ttf', 'HelveticaNeue', 'normal');
        const hnMedOk = await loadFont(doc, base+'/fonts/HelveticaNeue-Medium.ttf',  'HelveticaNeue-Medium.ttf',  'HelveticaNeueM', 'normal');
        const hnBldOk = await loadFont(doc, base+'/fonts/HelveticaNeue-Bold.ttf',    'HelveticaNeue-Bold.ttf',    'HelveticaNeue', 'bold');
        if (hnRegOk) fonts.regular = 'HelveticaNeue';
        if (hnMedOk) fonts.medium  = 'HelveticaNeueM';
        if (hnBldOk) fonts.bold    = 'HelveticaNeue';

        // Fall back to Arimo (metric Helvetica clone, bundled) for any weight not loaded
        if (!hnRegOk) {
            const ok = await loadFont(doc, base+'/fonts/Arimo-Regular.ttf', 'Arimo-Regular.ttf', 'Arimo', 'normal');
            if (ok) fonts.regular = 'Arimo';
        }
        if (!hnMedOk) {
            const ok = await loadFont(doc, base+'/fonts/Arimo-Medium.ttf', 'Arimo-Medium.ttf', 'ArimoMed', 'normal');
            if (ok) fonts.medium = 'ArimoMed';
        }
        if (!hnBldOk) {
            const ok = await loadFont(doc, base+'/fonts/Arimo-Bold.ttf', 'Arimo-Bold.ttf', 'Arimo', 'bold');
            if (ok) fonts.bold = 'Arimo';
        }

        // Old English Text MT (user-supplied) → UnifrakturMaguntia (bundled) → helvetica
        const oeOk  = await loadFont(doc, base+'/fonts/OldeEnglish.ttf', 'OldeEnglish.ttf', 'OldeEnglish', 'normal');
        if (oeOk) { fonts.blackletter = 'OldeEnglish'; }
        else {
            const blkOk = await loadFont(doc, base+'/fonts/UnifrakturMaguntia.ttf', 'UnifrakturMaguntia.ttf', 'UnifrakturMaguntia', 'normal');
            if (blkOk) fonts.blackletter = 'UnifrakturMaguntia';
        }

        st.textContent = 'Preparing images…';
        await new Promise(r => setTimeout(r, 0));

        // Pre-render canvases once (reused across all pages).
        // Two weekly canvases: box frame (used when verse text present) and centred frame (no verses).
        const weeklyCanvasBox  = weeklyImgDataUrl
            ? await buildRotatedImgCanvas(weeklyImgDataUrl, weeklyNatW, weeklyNatH, IMG_BOX_W,  IMG_BOX_H,  false) // contain: whole picture, no crop
            : null;
        const weeklyCanvasCent = weeklyImgDataUrl
            ? await buildRotatedImgCanvas(weeklyImgDataUrl, weeklyNatW, weeklyNatH, IMG_CENT_W, IMG_CENT_H, false) // contain: whole picture
            : null;
        const specialLogoCanvas = specialImgDataUrl
            ? await buildRotatedImgCanvas(specialImgDataUrl, specialNatW, specialNatH, SPEC_LOGO_W, SPEC_LOGO_H)
            : null;

        for (let idx = 0; idx < parsedRows.length; idx++) {
            if (idx > 0) doc.addPage();
            if (idx % 10 === 0) {
                st.textContent = `Rendering page ${idx + 1} of ${parsedRows.length}…`;
                await new Promise(r => setTimeout(r, 0));
            }
            const row = parsedRows[idx];
            drawEnvPdf(doc, row, 0,            row.setLeft,  weeklyCanvasBox, weeklyCanvasCent, specialLogoCanvas, fonts);
            drawEnvPdf(doc, row, SLOT_RIGHT_X, row.setRight, weeklyCanvasBox, weeklyCanvasCent, specialLogoCanvas, fonts);
        }

        st.textContent = `Done — ${parsedRows.length} pages saved.`;
        st.style.color = '#166534';
        doc.save(spreadsheetBasename + '.pdf');
    } catch (e) {
        st.textContent = 'PDF error: ' + e.message;
        st.style.color = '#991b1b';
    } finally {
        btn.disabled = false; btn.textContent = 'Generate & Download PDF';
    }
}
</script>
</x-layout>
