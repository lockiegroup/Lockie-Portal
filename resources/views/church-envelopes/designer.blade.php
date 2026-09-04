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
    return row.vts.filter(Boolean).slice(0, 2);
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
    // Show the envelope in READING orientation — how it looks when held in hand.
    // This matches the InDesign artwork exactly.
    //
    // PDF coordinate → reading coordinate transform (90° CW rotation of landscape half):
    //   reading_x = y_pdf          (PDF y-axis → reading left-right)
    //   reading_y = 78 - x_pdf     (PDF x-axis inverted → reading top-bottom)
    //
    // Reading card dimensions: 98mm wide × 78mm tall (landscape)
    const RW = ENV_H * S;  // 98mm × 1.7 = 166.6px
    const RH = ENV_W * S;  // 78mm × 1.7 = 132.6px

    const isSpec = row.isSpecial;
    const imgSrc = isSpec ? specialImgDataUrl : weeklyImgDataUrl;

    const env = document.createElement('div');
    env.style.cssText = `position:relative;width:${RW}px;height:${RH}px;overflow:hidden;background:white;flex-shrink:0;`;

    // Coordinate mapping for reading-orientation preview (landscape card 98×78mm):
    //   reading_y = (78 - x_pdf) * S   — vertical position of the text strip
    //   reading_x_center = (y_pdf / 98) * RW — horizontal center of text
    //     church (y_pdf=49) → center at 49/98 * RW = 50% (middle)
    //     all others (y_pdf=70) → center at 70/98 * RW ≈ 71% (right portion, below image)
    const rY = (x_pdf) => (78 - x_pdf) * S;
    const CHURCH_CX = (49 / 98) * RW;   // center-x for church band
    const OTHER_CX  = (70 / 98) * RW;   // center-x for all other bands

    // halfMaxMM: max half-width in mm each side of y_pdf=70 centre (maps to reading x)
    function band(text, x_pdf, y_pdf_center, fsizePx, bold, italic, color, halfMaxMM) {
        const cx = (y_pdf_center === 49) ? CHURCH_CX : OTHER_CX;
        const halfW = (halfMaxMM !== undefined ? halfMaxMM : 44) * S;
        const el = document.createElement('div');
        el.style.cssText = `position:absolute;` +
            `top:${rY(x_pdf)}px;` +
            `left:${Math.max(0, cx - halfW)}px;width:${Math.min(RW, halfW * 2)}px;` +
            `font-family:Arial,sans-serif;font-weight:${bold?'700':'400'};` +
            `font-style:${italic?'italic':'normal'};font-size:${fsizePx}px;` +
            `color:${color||'#111'};text-align:center;white-space:nowrap;overflow:hidden;line-height:1;`;
        el.textContent = text;
        env.appendChild(el);
    }

    // Church — centred at y_pdf=49, spans full card width (44mm each side)
    band(row.church.toUpperCase(), 70, 49, 6 * S, true, false, '#111', 44);

    // Town — centred at y_pdf=70 (lower portion, below image), 30mm cap
    if (row.town) band(row.town.toUpperCase(), 42, 70, 4.5 * S, true, false, '#111', 15);

    // Diocese lines — 15mm each side = 30mm max span
    [row.diocese1, row.diocese2, row.diocese3].filter(Boolean).forEach((d, i) => {
        band(d, 30 - i * 4, 70, 3.2 * S, false, false, '#555', 15);
    });

    // Offering lines — 15mm each side = 30mm max span
    const offeringLines = getOfferingLines(row);
    offeringLines.forEach((line, i) => {
        band(line, 20 - i * 4, 70, 3.8 * S, false, true, '#111', 15);
    });

    // Date — 15mm each side
    const date = buildDate(row);
    if (date) band(date.toUpperCase(), 8, 70, 4.5 * S, true, false, '#111', 15);

    // Cross image — PDF: x=14–59, y=7–43 (image box centre).
    // In reading coords: reading_x = y_pdf, reading_y = 78 - x_pdf.
    // The image dimensions swap: reading_width = dH, reading_height = dW.
    const { dW, dH } = imgDisplayDims(isSpec);
    const imgPdfCx = IMG_BOX_X + (IMG_BOX_W - dW) / 2;
    const imgPdfCy = IMG_BOX_Y + (IMG_BOX_H - dH) / 2;
    if (imgSrc) {
        const imgEl = document.createElement('img');
        imgEl.src = imgSrc;
        const rLeft = imgPdfCy * S;
        const rTop  = (78 - imgPdfCx - dW) * S;
        imgEl.style.cssText = `position:absolute;left:${rLeft}px;top:${rTop}px;` +
            `width:${dH * S}px;height:${dW * S}px;object-fit:contain;`;
        env.appendChild(imgEl);
    }

    // Set number — small, top-right corner of reading card
    if (setNum !== null) {
        const sn = document.createElement('div');
        sn.style.cssText = `position:absolute;right:${3*S}px;top:${2*S}px;` +
            `font-family:Arial,sans-serif;font-weight:700;font-size:${5*S}px;color:#111;`;
        sn.textContent = String(setNum);
        env.appendChild(sn);
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

function drawEnvPdf(doc, row, xBase, setNum, imgCanvasData) {
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

    // Set number — top-left corner, upright
    if (setNum !== null) {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(18);
        doc.setTextColor(17, 17, 17);
        doc.text(String(setNum), xBase + 5, 16);
    }

    // Church name — rightmost column, full height, rotated 90° CW
    // fitFont: scale up to fill ~88mm column height, cap at 13pt (matches InDesign)
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(17, 17, 17);
    {
        const churchText = row.church.toUpperCase();
        const targetMM = 88; // column height available
        const maxPt = 13;
        let pt = maxPt;
        doc.setFontSize(pt);
        while (pt > 6 && doc.getTextWidth(churchText) > targetMM) {
            pt -= 0.5;
            doc.setFontSize(pt);
        }
    }
    doc.text(row.church.toUpperCase(), xBase + 70, 49, { angle: -90, align: 'center' });

    // Town — second column from right
    // y=70 places text in lower portion (y=57–82mm), safely below image box (y=7–43mm)
    if (row.town) {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.text(row.town.toUpperCase(), xBase + 42, 70, { angle: -90, align: 'center' });
    }

    // Diocese lines — stepping left from town, max span 30mm (±15mm from y=70)
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(51, 51, 51);
    [row.diocese1, row.diocese2, row.diocese3].filter(Boolean).forEach((d, i) => {
        let pt = 6.5;
        doc.setFontSize(pt);
        while (pt > 4 && doc.getTextWidth(d) > 30) {
            pt -= 0.25;
            doc.setFontSize(pt);
        }
        doc.text(d, xBase + 30 - i * 4, 70, { angle: -90, align: 'center' });
    });

    // Date — leftmost column
    const date = buildDate(row);
    if (date) {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.setTextColor(17, 17, 17);
        doc.text(date.toUpperCase(), xBase + 8, 70, { angle: -90, align: 'center' });
    }

    // Offering lines — between date and diocese, max span 30mm
    const offeringLines = getOfferingLines(row);
    if (offeringLines.length) {
        doc.setFont('helvetica', 'italic');
        doc.setTextColor(17, 17, 17);
        offeringLines.forEach((line, i) => {
            let pt = 7.5;
            doc.setFontSize(pt);
            while (pt > 4.5 && doc.getTextWidth(line) > 30) {
                pt -= 0.25;
                doc.setFontSize(pt);
            }
            doc.text(line, xBase + 20 - i * 4, 70, { angle: -90, align: 'center' });
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
