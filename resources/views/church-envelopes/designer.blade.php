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
        <p style="font-size:0.75rem;color:#94a3b8;margin:0 0 1rem;">Preview shows portrait layout matching InDesign. When held landscape the text reads left-to-right.</p>
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
// Page: 156 × 98 mm landscape. Each half: 78 × 98 mm portrait.
// Envelope reading orientation: 98 mm wide × 78 mm tall (landscape in hand).
// Content is drawn in reading space then rotated 90° CW for print.
const PAGE_W = 156, PAGE_H = 98, ENV_W = 78, ENV_H = 98;
const RW = 98, RH = 78; // reading space dimensions

// ── Auto-load Spiral.jpg ──────────────────────────────────────────────────────
(function () {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function () {
        specialNatW = img.naturalWidth;
        specialNatH = img.naturalHeight;
        const c = document.createElement('canvas');
        c.width = specialNatW; c.height = specialNatH;
        c.getContext('2d').drawImage(img, 0, 0);
        specialImgDataUrl = c.toDataURL('image/jpeg', 0.95);
        document.getElementById('special-img-thumb').src = specialImgDataUrl;
        document.getElementById('special-img-preview').style.display = 'block';
        document.getElementById('special-img-label').textContent = 'Spiral.jpg (default)';
        updatePreview();
    };
    img.onerror = function () {
        document.getElementById('special-img-label').textContent = 'Choose image…';
    };
    img.src = '{{ asset('images/Spiral.jpg') }}';
})();

// ── Image upload ──────────────────────────────────────────────────────────────
function handleImage(input, type) {
    const file = input.files[0];
    if (!file) return;
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
                const vt1 = String(row[vtCols[1]] ?? '').trim();
                const vt6 = String(row[vtCols[6]] ?? '').trim();
                const isSpec = String(row[7] ?? '').trim() !== '' || (vt6 !== '' && vt1 === '');
                const vts = [];
                for (let i = 1; i <= 8; i++) vts.push(String(row[vtCols[i]] ?? '').trim());
                const rawL = row[4], rawR = row[5];
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
function getOfferingLines(row) {
    if (row.isSpecial) return [row.vts[5], row.vts[6]].filter(Boolean);
    return row.vts.filter(Boolean);
}
function buildDate(row) { return [row.day, row.month, row.year].filter(Boolean).join(' '); }
function sanitise(str) { return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'envelopes'; }

// Image box — matched to InDesign guide box (portrait coords, mm)
const IMG_BOX_X = 14, IMG_BOX_Y = 7, IMG_BOX_W = 45, IMG_BOX_H = 36;

function imgAspect(isSpec) {
    const nw = isSpec ? specialNatW : weeklyNatW;
    const nh = isSpec ? specialNatH : weeklyNatH;
    return (nw && nh) ? nw / nh : IMG_BOX_W / IMG_BOX_H;
}
// Compute display W/H within the fixed box, maintaining aspect ratio
function imgDisplayDims(isSpec) {
    const asp = imgAspect(isSpec);
    let dW, dH;
    if (asp >= IMG_BOX_W / IMG_BOX_H) { dW = IMG_BOX_W; dH = IMG_BOX_W / asp; }
    else                               { dH = IMG_BOX_H; dW = IMG_BOX_H * asp; }
    return { dW, dH };
}

// ── Preview (HTML, reading orientation) ───────────────────────────────────────
const S = 1.7; // px per mm for preview

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
    const W = ENV_W * S, H = ENV_H * S;
    const env = document.createElement('div');
    env.style.cssText = `position:relative;width:${W}px;height:${H}px;overflow:hidden;background:transparent;flex-shrink:0;`;

    const isSpec = row.isSpecial;
    const imgSrc = isSpec ? specialImgDataUrl : weeklyImgDataUrl;

    let y = 9 * S;

    const cname = document.createElement('div');
    cname.style.cssText = `position:absolute;left:0;top:${y}px;width:${W}px;font-family:Arial,sans-serif;font-weight:700;font-size:${5.5*S}px;color:#111;text-align:center;line-height:1.25;`;
    cname.textContent = row.church;
    env.appendChild(cname);
    const churchLines = Math.ceil((row.church.length * 5.5 * S * 0.55) / W) || 1;
    y += (6.5 * S) * churchLines + 2 * S;

    if (row.town) {
        const t = document.createElement('div');
        t.style.cssText = `position:absolute;left:0;top:${y}px;width:${W}px;font-family:Arial,sans-serif;font-weight:700;font-size:${4.2*S}px;color:#111;text-align:center;`;
        t.textContent = row.town; env.appendChild(t); y += 5 * S;
    }

    [row.diocese1, row.diocese2, row.diocese3].forEach(d => {
        if (!d) return;
        const dl = document.createElement('div');
        dl.style.cssText = `position:absolute;left:0;top:${y}px;width:${W}px;font-family:Arial,sans-serif;font-size:${3*S}px;color:#333;text-align:center;`;
        dl.textContent = d; env.appendChild(dl); y += 3.8 * S;
    });

    // Image: fixed box from InDesign layout, image scaled to fit within it
    const { dW, dH } = imgDisplayDims(isSpec);
    const imgX   = (IMG_BOX_X + (IMG_BOX_W - dW) / 2) * S;
    const imgTop = (IMG_BOX_Y + (IMG_BOX_H - dH) / 2) * S;
    const imgW   = dW * S, imgHpx = dH * S;

    if (imgSrc) {
        const imgEl = document.createElement('img');
        imgEl.src = imgSrc;
        imgEl.style.cssText = `position:absolute;left:${imgX}px;top:${imgTop}px;width:${imgW}px;height:${imgHpx}px;object-fit:contain;`;
        env.appendChild(imgEl);
    }

    const offeringLines = getOfferingLines(row);
    if (offeringLines.length) {
        const lineH = 4.8 * S, totalH = offeringLines.length * lineH;
        const imgMidY = (IMG_BOX_Y + IMG_BOX_H / 2) * S;
        const textStartY = imgMidY - totalH / 2;
        const textLeft = (IMG_BOX_X + IMG_BOX_W + 4) * S;
        const textWidth = W - textLeft - 3 * S;
        offeringLines.forEach((line, i) => {
            const t = document.createElement('div');
            t.style.cssText = `position:absolute;left:${textLeft}px;top:${textStartY + i*lineH}px;width:${textWidth}px;font-family:Arial,sans-serif;font-style:italic;font-size:${3.8*S}px;color:#111;text-align:center;line-height:1.2;`;
            t.textContent = line; env.appendChild(t);
        });
    }

    if (setNum !== null) {
        const sn = document.createElement('div');
        sn.style.cssText = `position:absolute;left:${5*S}px;bottom:${5*S}px;font-family:Arial,sans-serif;font-weight:700;font-size:${7*S}px;color:#111;`;
        sn.textContent = String(setNum); env.appendChild(sn);
    }

    const date = buildDate(row);
    if (date) {
        const dt = document.createElement('div');
        dt.style.cssText = `position:absolute;right:${4*S}px;bottom:${5*S}px;font-family:Arial,sans-serif;font-size:${3.5*S}px;color:#333;text-align:right;`;
        dt.textContent = date; env.appendChild(dt);
    }

    return env;
}

// ── PDF Generation ────────────────────────────────────────────────────────────
// jsPDF native text (vector, infinitely sharp) for all text elements.
// Canvas only for the small image box (18.8×32.6mm) — tiny PNG, no size issues.
// Coordinates match the HTML preview (portrait 78×98mm per half, no angle rotation).

function loadImage(src) {
    return new Promise(resolve => {
        const img = new Image();
        img.onload  = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = src;
    });
}

// Build a small canvas for just the image box (18.8×32.6mm at high res)
async function buildImgCanvas(isSpec, imgDataUrl) {
    if (!imgDataUrl) return null;
    const ICPPM = 8; // high res for the small image only
    const { dW, dH } = imgDisplayDims(isSpec);
    const cW = Math.round(dW * ICPPM), cH = Math.round(dH * ICPPM);
    const canvas = document.createElement('canvas');
    canvas.width = cW; canvas.height = cH;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, cW, cH);
    const imgEl = await loadImage(imgDataUrl);
    if (imgEl) ctx.drawImage(imgEl, 0, 0, cW, cH);
    return { canvas, dW, dH };
}

// Scale font so text exactly fills targetMM (matches InDesign text-box auto-fit).
function fitFont(doc, text, targetMM, nominalPt, minPt, maxPt) {
    doc.setFontSize(nominalPt);
    const w = doc.getTextWidth(text);
    if (!w) return nominalPt;
    return Math.min(maxPt, Math.max(minPt, nominalPt * targetMM / w));
}

function drawEnvPdf(doc, row, xBase, setNum, imgCanvasData) {
    // Matches InDesign portrait 78×98mm layout.
    // angle:-90 = 90° CW rotation. With this rotation, cap-height extends RIGHTWARD
    // from the baseline x, so all x values are the LEFT (baseline) edge of the column,
    // not the centre — this prevents text from overflowing the half boundary.
    const isSpec = row.isSpecial;

    // Image — upper-left guide box
    if (imgCanvasData) {
        const { canvas, dW, dH } = imgCanvasData;
        const imgX = xBase + IMG_BOX_X + (IMG_BOX_W - dW) / 2;
        const imgY = IMG_BOX_Y + (IMG_BOX_H - dH) / 2;
        try {
            const alias = (isSpec ? 'sp' : 'wk') + Math.round(dW * 10);
            doc.addImage(canvas.toDataURL('image/jpeg', 0.92), 'JPEG',
                         imgX, imgY, dW, dH, alias, 'NONE');
        } catch(_) {}
    }

    // Set number — top-left corner, upright (no rotation)
    if (setNum !== null) {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(22);
        doc.setTextColor(17, 17, 17);
        doc.text(String(setNum), xBase + 5, 17);
    }

    // Church name — rightmost column (baseline x=70, cap →77mm max), full height
    const churchStr = row.church.toUpperCase();
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(17, 17, 17);
    const churchFS = fitFont(doc, churchStr, 82, 14, 8, 17);
    doc.setFontSize(churchFS);
    doc.text(churchStr, xBase + 70, 49, { angle: -90, align: 'center' });

    // Town — column left of church (baseline x=61), lower portion
    if (row.town) {
        const townStr = row.town.toUpperCase();
        doc.setFont('helvetica', 'bold');
        const townFS = fitFont(doc, townStr, 40, 9, 6, 12);
        doc.setFontSize(townFS);
        doc.text(townStr, xBase + 61, 70, { angle: -90, align: 'center' });
    }

    // Diocese lines — stepping left from x=55, lower portion
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(51, 51, 51);
    [row.diocese1, row.diocese2, row.diocese3].filter(Boolean).forEach((d, i) => {
        const diocFS = fitFont(doc, d, 40, 6.5, 4, 8);
        doc.setFontSize(diocFS);
        doc.text(d, xBase + 55 - i * 4.5, 70, { angle: -90, align: 'center' });
    });

    // Date — left column (baseline x=7), lower portion, bold
    const date = buildDate(row);
    if (date) {
        const dateStr = date.toUpperCase();
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(17, 17, 17);
        const dateFS = fitFont(doc, dateStr, 40, 12, 8, 15);
        doc.setFontSize(dateFS);
        doc.text(dateStr, xBase + 7, 70, { angle: -90, align: 'center' });
    }

    // Offering lines — stepping right from x=19, each line its own column
    const offeringLines = getOfferingLines(row);
    if (offeringLines.length) {
        doc.setFont('helvetica', 'italic');
        doc.setTextColor(17, 17, 17);
        offeringLines.forEach((line, i) => {
            const oFS = fitFont(doc, line, 40, 7.5, 5, 10);
            doc.setFontSize(oFS);
            doc.text(line, xBase + 19 + i * 7, 70, { angle: -90, align: 'center' });
        });
    }
}

async function generatePDF() {
    if (!parsedRows.length) return;
    const btn = document.getElementById('generate-btn');
    const st  = document.getElementById('generate-status');
    btn.disabled = true; btn.textContent = 'Generating…';
    st.style.display = 'block'; st.style.color = '#64748b';
    st.textContent = 'Preparing images…';
    await new Promise(r => setTimeout(r, 30));

    try {
        // Pre-render image canvases once (reused across all pages)
        const weeklyImgData  = weeklyImgDataUrl  ? await buildImgCanvas(false, weeklyImgDataUrl)  : null;
        const specialImgData = specialImgDataUrl ? await buildImgCanvas(true,  specialImgDataUrl) : null;

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'mm', format: [PAGE_W, PAGE_H], orientation: 'landscape' });
        const church = (parsedRows.find(r => !r.isSpecial) || parsedRows[0])?.church || 'envelopes';

        for (let idx = 0; idx < parsedRows.length; idx++) {
            if (idx > 0) doc.addPage();
            if (idx % 10 === 0) {
                st.textContent = `Rendering page ${idx + 1} of ${parsedRows.length}…`;
                await new Promise(r => setTimeout(r, 0));
            }
            const row = parsedRows[idx];
            const imgData = row.isSpecial ? specialImgData : weeklyImgData;
            drawEnvPdf(doc, row, 0,     row.setLeft,  imgData);
            drawEnvPdf(doc, row, ENV_W, row.setRight, imgData);
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
