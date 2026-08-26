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
        <p style="color:#64748b;font-size:0.875rem;margin:0;">Upload a generated spreadsheet to preview and download a print-ready PDF — 2-up (156 × 98 mm landscape page, two 78 × 98 mm portrait halves side by side, content rotated to match InDesign artwork).</p>
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
        <p style="font-size:0.75rem;color:#94a3b8;margin:0 0 1rem;">Preview shows print layout (content rotated as it will appear on the printed sheet). Each page: two 78×98mm portrait halves side by side.</p>
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
// Weekly image
let weeklyImgDataUrl = null, weeklyNatW = 0, weeklyNatH = 0;
// Special image
let specialImgDataUrl = null, specialNatW = 0, specialNatH = 0;

// PDF page: 156mm wide × 98mm tall (landscape)
// Each envelope half: 78mm wide × 98mm tall (portrait)
// Envelopes are used landscape (98mm wide × 78mm tall), so content is rotated 90° CW in print
const PAGE_W = 156, PAGE_H = 98, ENV_W = 78, ENV_H = 98;
// Reading space per envelope: 98mm wide × 78mm tall
const RW = 98, RH = 78;

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
                weeklyImgDataUrl = url;
                weeklyNatW = probe.naturalWidth;
                weeklyNatH = probe.naturalHeight;
                document.getElementById('weekly-img-label').textContent = file.name;
                document.getElementById('weekly-img-thumb').src = url;
                document.getElementById('weekly-img-preview').style.display = 'block';
            } else {
                specialImgDataUrl = url;
                specialNatW = probe.naturalWidth;
                specialNatH = probe.naturalHeight;
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
            header.forEach((h, i) => {
                const m = String(h || '').match(/^VT(\d+)$/i);
                if (m) vtCols[parseInt(m[1])] = i;
            });
            for (let i = 1; i <= 8; i++) vtCols[i] = vtCols[i] ?? (12 + i);

            parsedRows = [];
            raw.slice(1).forEach(row => {
                if (row.every(v => v === '' || v === null || v === undefined)) return;
                const vt1    = String(row[vtCols[1]] ?? '').trim();
                const vt6    = String(row[vtCols[6]] ?? '').trim();
                const spiral = String(row[7] ?? '').trim();
                const isSpec = spiral !== '' || (vt6 !== '' && vt1 === '');
                const vts    = [];
                for (let i = 1; i <= 8; i++) vts.push(String(row[vtCols[i]] ?? '').trim());

                const rawL = row[4], rawR = row[5];
                parsedRows.push({
                    day:      String(row[1] ?? '').trim(),
                    month:    String(row[2] ?? '').trim(),
                    year:     String(row[3] ?? '').trim(),
                    setLeft:  (rawL !== '' && rawL !== null) ? parseInt(rawL)  : null,
                    setRight: (rawR !== '' && rawR !== null) ? parseInt(rawR) : null,
                    isSpecial: isSpec,
                    church:   String(row[8]  ?? '').trim(),
                    town:     String(row[9]  ?? '').trim(),
                    diocese1: String(row[10] ?? '').trim(),
                    diocese2: String(row[11] ?? '').trim(),
                    diocese3: String(row[12] ?? '').trim(),
                    vts,
                });
            });

            if (!parsedRows.length) throw new Error('No data rows found');
            const first   = parsedRows.find(r => !r.isSpecial) || parsedRows[0];
            const weekly  = parsedRows.filter(r => !r.isSpecial).length;
            const special = parsedRows.filter(r => r.isSpecial).length;
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

// ── Canvas rendering ──────────────────────────────────────────────────────────
// Render one envelope in READING orientation (98mm × 78mm) on a canvas,
// then rotate 90° CW to get the print orientation (78mm × 98mm in PDF).
const CPPM = 4; // canvas pixels per mm

function getOfferingLines(row) {
    if (row.isSpecial) return [row.vts[5], row.vts[6]].filter(Boolean);
    return row.vts.filter(Boolean);
}
function buildDate(row) {
    return [row.day, row.month, row.year].filter(Boolean).join(' ');
}

// Draw envelope in reading orientation (RW × RH mm) on a canvas, return Promise<canvas>
function drawEnvReading(row, setNum) {
    const isSpec = row.isSpecial;
    const imgUrl = isSpec ? specialImgDataUrl : weeklyImgDataUrl;
    const natW   = isSpec ? specialNatW : weeklyNatW;
    const natH   = isSpec ? specialNatH : weeklyNatH;

    const CW = RW * CPPM, CH = RH * CPPM;
    const c  = document.createElement('canvas');
    c.width = CW; c.height = CH;
    const ctx = c.getContext('2d');

    function px(mm) { return mm * CPPM; }

    let y = px(7);

    // Church name — bold, centred
    ctx.fillStyle = '#0f0f0f';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    ctx.font = `bold ${px(5)}px Arial,sans-serif`;
    const maxTw = px(RW - 8);
    const churchLines = wrapText(ctx, row.church.toUpperCase(), maxTw);
    for (const line of churchLines) {
        ctx.fillText(line, CW / 2, y);
        y += px(6);
    }
    y += px(1);

    // Town — bold, centred
    if (row.town) {
        ctx.font = `bold ${px(4)}px Arial,sans-serif`;
        ctx.fillText(row.town.toUpperCase(), CW / 2, y);
        y += px(5.5);
    }

    // Diocese lines — normal, centred
    ctx.fillStyle = '#282828';
    ctx.font = `${px(3)}px Arial,sans-serif`;
    for (const d of [row.diocese1, row.diocese2, row.diocese3]) {
        if (d) { ctx.fillText(d, CW / 2, y); y += px(3.8); }
    }

    // Image area
    const imgTop = Math.max(y + px(3), px(20));
    const IMG_W  = px(33); // image width in pixels (≈33mm)
    const aspect = (natW && natH) ? natW / natH : 1;
    const IMG_H  = IMG_W / aspect;
    const imgX   = px(5);

    const offeringLines = getOfferingLines(row);
    const date = buildDate(row);

    function finishCanvas() {
        // Offering text — italic, centred in right portion
        if (offeringLines.length) {
            ctx.fillStyle = '#0f0f0f';
            ctx.font = `italic ${px(3.5)}px Arial,sans-serif`;
            ctx.textAlign = 'center';
            const textLeft = imgX + IMG_W + px(4);
            const textCx   = (textLeft + px(RW - 4)) / 2;
            const lineH    = px(4.5);
            const totalH   = offeringLines.length * lineH;
            const startY   = imgTop + IMG_H / 2 - totalH / 2;
            offeringLines.forEach((line, i) => {
                ctx.fillText(line, textCx, startY + i * lineH);
            });
        }

        // Set number — bold, bottom-left
        if (setNum !== null) {
            ctx.fillStyle = '#0f0f0f';
            ctx.font = `bold ${px(7)}px Arial,sans-serif`;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'bottom';
            ctx.fillText(String(setNum), px(5), CH - px(5));
        }

        // Date — bottom-right
        if (date) {
            ctx.fillStyle = '#282828';
            ctx.font = `${px(3.5)}px Arial,sans-serif`;
            ctx.textAlign = 'right';
            ctx.textBaseline = 'bottom';
            ctx.fillText(date, px(RW - 5), CH - px(5));
        }
    }

    return new Promise(resolve => {
        if (imgUrl) {
            const imgEl = new Image();
            imgEl.onload = () => {
                ctx.drawImage(imgEl, imgX, imgTop, IMG_W, IMG_H);
                finishCanvas();
                resolve(c);
            };
            imgEl.onerror = () => { finishCanvas(); resolve(c); };
            imgEl.src = imgUrl;
        } else {
            // Placeholder rectangle
            ctx.fillStyle = 'rgba(0,0,0,0.08)';
            ctx.fillRect(imgX, imgTop, IMG_W, IMG_H);
            finishCanvas();
            resolve(c);
        }
    });
}

function wrapText(ctx, text, maxWidth) {
    const words = text.split(' ');
    const lines = [];
    let cur = '';
    for (const w of words) {
        const test = cur ? cur + ' ' + w : w;
        if (ctx.measureText(test).width > maxWidth && cur) {
            lines.push(cur); cur = w;
        } else { cur = test; }
    }
    if (cur) lines.push(cur);
    return lines;
}

// Rotate canvas 90° CW → new canvas (H×W)
function rotateCW(srcCanvas) {
    const dst = document.createElement('canvas');
    dst.width  = srcCanvas.height;
    dst.height = srcCanvas.width;
    const ctx  = dst.getContext('2d');
    ctx.translate(dst.width, 0);
    ctx.rotate(Math.PI / 2);
    ctx.drawImage(srcCanvas, 0, 0);
    return dst;
}

// ── Preview ───────────────────────────────────────────────────────────────────
const PREV_SCALE = 2.2; // px per mm for preview display

async function updatePreview() {
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

        // Render both halves as canvases, rotate CW, display side by side
        const [leftC, rightC] = await Promise.all([
            drawEnvReading(row, row.setLeft),
            drawEnvReading(row, row.setRight),
        ]);
        const leftRot  = rotateCW(leftC);
        const rightRot = rotateCW(rightC);

        // Page container: two portrait halves side by side, scaled for display
        const pageDiv = document.createElement('div');
        pageDiv.style.cssText = `display:inline-flex;background:#d1d5db;gap:1px;border:1px solid #9ca3af;border-radius:3px;overflow:hidden;`;

        for (const rotCanvas of [leftRot, rightRot]) {
            const img = document.createElement('img');
            img.src = rotCanvas.toDataURL('image/png');
            // Each half: ENV_W × ENV_H mm displayed at PREV_SCALE px/mm
            img.style.cssText = `width:${ENV_W * PREV_SCALE}px;height:${ENV_H * PREV_SCALE}px;display:block;image-rendering:auto;`;
            pageDiv.appendChild(img);
        }
        wrap.appendChild(pageDiv);
        container.appendChild(wrap);
    }
}

// ── PDF Generation ────────────────────────────────────────────────────────────
async function generatePDF() {
    if (!parsedRows.length) return;
    const btn = document.getElementById('generate-btn');
    const st  = document.getElementById('generate-status');
    btn.disabled = true; btn.textContent = 'Generating…';
    st.style.display = 'block'; st.style.color = '#64748b';
    st.textContent = `Building ${parsedRows.length} pages…`;
    await new Promise(r => setTimeout(r, 50));

    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'mm', format: [PAGE_W, PAGE_H], orientation: 'landscape' });
        const church = (parsedRows.find(r => !r.isSpecial) || parsedRows[0])?.church || 'envelopes';

        for (let idx = 0; idx < parsedRows.length; idx++) {
            const row = parsedRows[idx];
            if (idx > 0) doc.addPage();

            st.textContent = `Building page ${idx + 1} of ${parsedRows.length}…`;
            await new Promise(r => setTimeout(r, 0)); // yield

            // Render each envelope in reading orientation, rotate CW, insert as image
            const [leftC, rightC] = await Promise.all([
                drawEnvReading(row, row.setLeft),
                drawEnvReading(row, row.setRight),
            ]);
            const leftRot  = rotateCW(leftC);
            const rightRot = rotateCW(rightC);

            // Insert into PDF (each half: ENV_W × ENV_H mm)
            doc.addImage(leftRot.toDataURL('image/png'),  'PNG', 0,     0, ENV_W, ENV_H, undefined, 'NONE');
            doc.addImage(rightRot.toDataURL('image/png'), 'PNG', ENV_W, 0, ENV_W, ENV_H, undefined, 'NONE');
        }

        doc.save(sanitise(church) + '-envelopes.pdf');
        st.textContent = `Done — ${parsedRows.length} pages saved.`;
        st.style.color = '#166534';
    } catch(e) {
        st.textContent = 'PDF error: ' + e.message;
        st.style.color = '#991b1b';
    } finally {
        btn.disabled = false; btn.textContent = 'Generate & Download PDF';
    }
}

function sanitise(str) {
    return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'envelopes';
}
</script>
</x-layout>
