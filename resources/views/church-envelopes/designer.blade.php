<x-layout title="Church Envelope Designer — Lockie Portal">
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
// ── State ─────────────────────────────────────────────────────────────────────
let parsedRows = [];
let weeklyImgDataUrl = null, weeklyNatW = 0, weeklyNatH = 0;
let specialImgDataUrl = null, specialNatW = 0, specialNatH = 0;

// PDF layout constants (mm)
// Page: 156×98 landscape. Two portrait halves: 78×98 each.
// All text: angle:-90 (90° CW) → reads upright on the portrait envelope.
// Images: pre-rotated 90° CW on canvas so they face the same way as text.
// Centred text helper: startY = (SLOT_H − doc.getTextWidth(text)) / 2
const PAGE_W = 156, PAGE_H = 98;
const SLOT_W = 78, SLOT_H = 98;
const SLOT_RIGHT_X = 77.75;

// Weekly image frame (portrait PDF coords): x 18.2–50.8, y 17.1–35.9 mm
const IMG_BOX_X = 18.2, IMG_BOX_Y = 17.1, IMG_BOX_W = 32.6, IMG_BOX_H = 18.8;
// Special foot-logo frame: x 50.9–62.5, y 80.4–92.0 mm
const SPEC_LOGO_X = 50.9, SPEC_LOGO_Y = 80.4, SPEC_LOGO_W = 11.6, SPEC_LOGO_H = 11.6;

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

    // band: place centred text at a PDF (xBaseline, yCenter) position
    function band(text, xPdf, yCentre, fsizePx, bold, fontFamily, maxHalfMM) {
        const cx = rCX(yCentre);
        const halfW = (maxHalfMM ?? 44) * S;
        const el = document.createElement('div');
        el.style.cssText = `position:absolute;top:${rY(xPdf)}px;` +
            `left:${Math.max(0, cx - halfW)}px;width:${Math.min(RW, halfW * 2)}px;` +
            `font-family:${fontFamily||'Arial,sans-serif'};font-weight:${bold?'700':'400'};` +
            `font-size:${fsizePx}px;color:#111;text-align:center;white-space:nowrap;overflow:hidden;line-height:1;`;
        el.textContent = text;
        env.appendChild(el);
    }

    // ── Image ────────────────────────────────────────────────────────────────
    if (!isSpec && weeklyImgDataUrl) {
        // Weekly image: natural (portrait) shown in reading portrait frame
        // Reading frame: 18.8mm wide × 32.6mm tall (from PDF frame rotated into reading)
        const prevBoxW = IMG_BOX_H, prevBoxH = IMG_BOX_W; // reading frame dims
        const { dW, dH } = fitNatural(weeklyNatW, weeklyNatH, prevBoxW, prevBoxH);
        // Frame centre in reading coords:  cx=IMG_BOX_Y+IMG_BOX_H/2, cy from PDF x centre
        const frameCX = (IMG_BOX_Y + IMG_BOX_H / 2) * S;
        const frameCY = (SLOT_W - (IMG_BOX_X + IMG_BOX_W / 2)) * S;
        const imgEl = document.createElement('img');
        imgEl.src = weeklyImgDataUrl;
        imgEl.style.cssText = `position:absolute;` +
            `left:${frameCX - dW/2*S}px;top:${frameCY - dH/2*S}px;` +
            `width:${dW*S}px;height:${dH*S}px;object-fit:contain;`;
        env.appendChild(imgEl);
    }
    if (isSpec && specialImgDataUrl) {
        // Special foot logo: natural image in small reading frame
        const prevBoxW = SPEC_LOGO_H, prevBoxH = SPEC_LOGO_W;
        const { dW, dH } = fitNatural(specialNatW, specialNatH, prevBoxW, prevBoxH);
        const frameCX = (SPEC_LOGO_Y + SPEC_LOGO_H / 2) * S;
        const frameCY = (SLOT_W - (SPEC_LOGO_X + SPEC_LOGO_W / 2)) * S;
        const imgEl = document.createElement('img');
        imgEl.src = specialImgDataUrl;
        imgEl.style.cssText = `position:absolute;` +
            `left:${frameCX - dW/2*S}px;top:${frameCY - dH/2*S}px;` +
            `width:${dW*S}px;height:${dH*S}px;object-fit:contain;`;
        env.appendChild(imgEl);
    }

    // ── Text (all centred on yCentre = PDF y-baseline) ────────────────────
    // Church — 15pt bold, PDF x-baseline=65.9, centred on face (y=49)
    band(row.church, 65.9, 49, 6*S, true, null, 44);

    // Town — 11pt bold, x=61.3, y=70
    if (row.town) band(row.town, 61.3, 70, 4.5*S, true, null, 44);

    // Diocese — 6pt, x=58.7/56.1/53.5, y=70 (first line = highest x)
    [row.diocese1, row.diocese2, row.diocese3].filter(Boolean).forEach((d, i) => {
        band(d, 58.7 - i*2.6, 70, 2.8*S, false, null, 44);
    });

    if (isSpec) {
        // Special: blackletter title lines VT6-8, x=38.4/29.8/21.2
        [row.vts[5], row.vts[6], row.vts[7]].filter(Boolean).forEach((t, i) => {
            band(t, 38.4 - i*8.6, 49, 5*S, false, "'UnifrakturMaguntia',serif", 44);
        });
    } else {
        // Gift text: VT1-8, 9pt, 3.9mm leading, block centred on x=31.5
        const vts = row.vts.filter(Boolean);
        const n = vts.length;
        vts.forEach((line, i) => {
            const xPdf = Math.max(11.2, 31.5 + ((n - 1) / 2 - i) * 3.9);
            band(line, xPdf, 70, 3.8*S, false, null, 44);
        });
    }

    // Date — 11pt, x=8.2, y=70
    const date = buildDate(row);
    if (date) band(date.toUpperCase(), 8.2, 70, 4.5*S, false, null, 44);

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
        return true;
    } catch(e) { console.warn('Font load failed:', filename, e.message); return false; }
}

// Pre-rotate image 90° CW on canvas + flatten onto white.
// dW/dH are the PDF box dimensions (canvas is dW*ICPPM × dH*ICPPM pixels).
async function buildRotatedImgCanvas(imgDataUrl, nw, nh, boxW, boxH) {
    if (!imgDataUrl) return null;
    const ICPPM = 8;
    const { dW, dH } = fitRotated(nw, nh, boxW, boxH);
    const cW = Math.round(dW * ICPPM), cH = Math.round(dH * ICPPM);
    const canvas = document.createElement('canvas');
    canvas.width = cW; canvas.height = cH;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, cW, cH);
    const imgEl = await loadImage(imgDataUrl);
    if (imgEl) {
        // 90° CW: translate to center, rotate CW (+π/2 in canvas coords = CW)
        ctx.save();
        ctx.translate(cW / 2, cH / 2);
        ctx.rotate(Math.PI / 2);
        // After CW rotation: x→down, y→left. Draw image filling canvas.
        ctx.drawImage(imgEl, -cH / 2, -cW / 2, cH, cW);
        ctx.restore();
    }
    return { canvas, dW, dH };
}

// ── PDF drawing ───────────────────────────────────────────────────────────────
function drawEnvPdf(doc, row, slotX, setNum, weeklyCanvas, specialLogoCanvas, fonts) {
    const isSpec = row.isSpecial;

    doc.setTextColor(0, 0, 0);
    const cY = text => (SLOT_H - doc.getTextWidth(text)) / 2; // centred along 98mm face

    // ── Image ─────────────────────────────────────────────────────────────────
    if (!isSpec && weeklyCanvas) {
        const { canvas, dW, dH } = weeklyCanvas;
        const imgX = slotX + IMG_BOX_X + (IMG_BOX_W - dW) / 2;
        const imgY = IMG_BOX_Y + (IMG_BOX_H - dH) / 2;
        try { doc.addImage(canvas.toDataURL('image/png'), 'PNG', imgX, imgY, dW, dH, 'wk'+Math.round(dW*10), 'NONE'); } catch(_){}
    }
    if (isSpec && specialLogoCanvas) {
        const { canvas, dW, dH } = specialLogoCanvas;
        const imgX = slotX + SPEC_LOGO_X + (SPEC_LOGO_W - dW) / 2;
        const imgY = SPEC_LOGO_Y + (SPEC_LOGO_H - dH) / 2;
        try { doc.addImage(canvas.toDataURL('image/png'), 'PNG', imgX, imgY, dW, dH, 'spl'+Math.round(dW*10), 'NONE'); } catch(_){}
    }

    // ── Set number — 20pt Medium, slotX+6.9, startY=10.3 ────────────────────
    if (setNum !== null) {
        doc.setFont(fonts.regular, 'normal'); doc.setFontSize(20);
        doc.text(String(setNum), slotX + 6.9, 10.3, { angle: -90 });
    }

    // ── Date — 11pt Medium, slotX+8.2, startY=57.5 ───────────────────────────
    const date = buildDate(row);
    if (date) {
        doc.setFont(fonts.regular, 'normal'); doc.setFontSize(11);
        doc.text(date.toUpperCase(), slotX + 8.2, 57.5, { angle: -90 });
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
            doc.setFont(fonts.regular, 'normal');
            vts.forEach((line, i) => {
                let pt = 9; doc.setFontSize(pt);
                while (pt > 5 && doc.getTextWidth(line) > 88) { pt -= 0.25; doc.setFontSize(pt); }
                const xOff = Math.max(slotX + 11.2, slotX + 31.5 + ((n - 1) / 2 - i) * 3.9);
                doc.text(line, xOff, cY(line), { angle: -90 });
            });
        }
    }

    // ── Diocese — 6pt Medium, slotX+58.7 (−2.6/line), centred ───────────────
    doc.setFont(fonts.regular, 'normal'); doc.setFontSize(6);
    [row.diocese1, row.diocese2, row.diocese3].filter(Boolean).forEach((d, i) => {
        let pt = 6; doc.setFontSize(pt);
        while (pt > 4 && doc.getTextWidth(d) > 88) { pt -= 0.25; doc.setFontSize(pt); }
        doc.text(d, slotX + 58.7 - i * 2.6, cY(d), { angle: -90 });
    });

    // ── Town — 11pt Bold, slotX+61.3, centred ────────────────────────────────
    if (row.town) {
        doc.setFont(fonts.bold, 'bold'); doc.setFontSize(11);
        doc.text(row.town, slotX + 61.3, cY(row.town), { angle: -90 });
    }

    // ── Church — 15pt Bold, slotX+65.9, centred, auto-shrink ─────────────────
    doc.setFont(fonts.bold, 'bold');
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

        // Load and embed fonts; fall back to built-in helvetica if files unavailable
        const fonts = { regular: 'helvetica', bold: 'helvetica', blackletter: 'helvetica' };
        const base = window.location.origin;
        const latoRegOk = await loadFont(doc, base+'/fonts/Lato-Regular.ttf', 'Lato-Regular.ttf', 'Lato', 'normal');
        const latoBldOk = await loadFont(doc, base+'/fonts/Lato-Bold.ttf',    'Lato-Bold.ttf',    'Lato', 'bold');
        const blkOk     = await loadFont(doc, base+'/fonts/UnifrakturMaguntia.ttf', 'UnifrakturMaguntia.ttf', 'UnifrakturMaguntia', 'normal');
        if (latoRegOk) { fonts.regular = 'Lato'; }
        if (latoBldOk) { fonts.bold    = 'Lato'; }
        if (blkOk)     { fonts.blackletter = 'UnifrakturMaguntia'; }

        st.textContent = 'Preparing images…';
        await new Promise(r => setTimeout(r, 0));

        // Pre-render canvases once (reused across all pages)
        const weeklyCanvas = weeklyImgDataUrl
            ? await buildRotatedImgCanvas(weeklyImgDataUrl, weeklyNatW, weeklyNatH, IMG_BOX_W, IMG_BOX_H)
            : null;
        const specialLogoCanvas = specialImgDataUrl
            ? await buildRotatedImgCanvas(specialImgDataUrl, specialNatW, specialNatH, SPEC_LOGO_W, SPEC_LOGO_H)
            : null;

        const church = (parsedRows.find(r => !r.isSpecial) || parsedRows[0])?.church || 'envelopes';

        for (let idx = 0; idx < parsedRows.length; idx++) {
            if (idx > 0) doc.addPage();
            if (idx % 10 === 0) {
                st.textContent = `Rendering page ${idx + 1} of ${parsedRows.length}…`;
                await new Promise(r => setTimeout(r, 0));
            }
            const row = parsedRows[idx];
            drawEnvPdf(doc, row, 0,            row.setLeft,  weeklyCanvas, specialLogoCanvas, fonts);
            drawEnvPdf(doc, row, SLOT_RIGHT_X, row.setRight, weeklyCanvas, specialLogoCanvas, fonts);
        }

        st.textContent = `Done — ${parsedRows.length} pages saved.`;
        st.style.color = '#166534';
        doc.save(sanitise(church) + '-envelopes.pdf');
    } catch (e) {
        st.textContent = 'PDF error: ' + e.message;
        st.style.color = '#991b1b';
    } finally {
        btn.disabled = false; btn.textContent = 'Generate & Download PDF';
    }
}
</script>
</x-layout>
