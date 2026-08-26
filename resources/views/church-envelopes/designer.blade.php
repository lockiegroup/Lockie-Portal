<x-layout title="Church Envelope Designer — Lockie Portal">
<main style="max-width:960px;margin:0 auto;padding:2rem 1.5rem;">

    {{-- Page header --}}
    <div style="margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:0.5rem;flex-wrap:wrap;">
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">Church Envelope Designer</h1>
            <a href="{{ route('church-envelopes.index') }}"
               style="font-size:0.8125rem;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:0.3rem;padding:0.3rem 0.75rem;border:1px solid #e2e8f0;border-radius:6px;background:#fff;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Envelope Generator
            </a>
        </div>
        <p style="color:#64748b;font-size:0.875rem;margin:0;">Upload a generated spreadsheet to preview and download a print-ready PDF — 2-up (98 × 156 mm pages).</p>
    </div>

    {{-- Upload section --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">1. Upload Spreadsheet</h2>
        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;">
            <label style="flex:1;min-width:200px;display:flex;align-items:center;gap:0.5rem;background:#f8fafc;border:1.5px dashed #cbd5e1;border-radius:8px;padding:0.75rem 1rem;cursor:pointer;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                <span id="file-label" style="font-size:0.875rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Choose .xlsx file…</span>
                <input type="file" id="xlsx-file" accept=".xlsx,.xls" style="display:none;" onchange="handleFileSelect(this)">
            </label>
            <button onclick="parseFile()" id="parse-btn"
                style="padding:0.6rem 1.25rem;background:#1e293b;color:#fff;border:none;border-radius:8px;font-size:0.875rem;font-weight:600;cursor:pointer;white-space:nowrap;">
                Load File
            </button>
        </div>
        <p id="parse-status" style="display:none;font-size:0.8125rem;margin:0.75rem 0 0;padding:0.5rem 0.75rem;border-radius:6px;"></p>
    </div>

    {{-- Options panel (revealed after parse) --}}
    <div id="options-panel" style="display:none;background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">2. Design Options</h2>

        {{-- Parsed summary --}}
        <div id="parse-summary" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:0.75rem 1rem;margin-bottom:1.25rem;font-size:0.8125rem;color:#166534;"></div>

        {{-- Background colour --}}
        <div style="margin-bottom:1.25rem;">
            <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.5rem;">Background Colour</label>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
                <button class="colour-btn" onclick="selectColour(this,'#dde8f4')" data-colour="#dde8f4" style="width:36px;height:36px;border-radius:8px;border:3px solid transparent;background:#dde8f4;cursor:pointer;" title="Light Blue"></button>
                <button class="colour-btn" onclick="selectColour(this,'#d4edda')" data-colour="#d4edda" style="width:36px;height:36px;border-radius:8px;border:3px solid transparent;background:#d4edda;cursor:pointer;" title="Light Green"></button>
                <button class="colour-btn" onclick="selectColour(this,'#fef9c3')" data-colour="#fef9c3" style="width:36px;height:36px;border-radius:8px;border:3px solid transparent;background:#fef9c3;cursor:pointer;" title="Pale Yellow"></button>
                <button class="colour-btn" onclick="selectColour(this,'#fce8e8')" data-colour="#fce8e8" style="width:36px;height:36px;border-radius:8px;border:3px solid transparent;background:#fce8e8;cursor:pointer;" title="Pale Pink"></button>
                <button class="colour-btn" onclick="selectColour(this,'#ede9fe')" data-colour="#ede9fe" style="width:36px;height:36px;border-radius:8px;border:3px solid transparent;background:#ede9fe;cursor:pointer;" title="Pale Lavender"></button>
                <button class="colour-btn" onclick="selectColour(this,'#ffffff')" data-colour="#ffffff" style="width:36px;height:36px;border-radius:8px;border:3px solid #e2e8f0;background:#ffffff;cursor:pointer;" title="White"></button>
                <div style="display:flex;align-items:center;gap:0.4rem;margin-left:0.25rem;">
                    <label style="font-size:0.8125rem;color:#64748b;">Custom:</label>
                    <input type="color" id="custom-colour" value="#dde8f4" onchange="selectCustomColour(this.value)"
                        style="width:36px;height:36px;border:1px solid #e2e8f0;border-radius:6px;padding:2px;cursor:pointer;">
                </div>
            </div>
        </div>

        {{-- Image uploads --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.4rem;">Weekly Envelope Image <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                <label style="display:flex;align-items:center;gap:0.5rem;background:#f8fafc;border:1.5px dashed #cbd5e1;border-radius:8px;padding:0.6rem 0.75rem;cursor:pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span id="weekly-img-label" style="font-size:0.8125rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Choose image…</span>
                    <input type="file" id="weekly-img" accept="image/*" style="display:none;" onchange="handleImage(this,'weekly')">
                </label>
                <div id="weekly-img-preview" style="margin-top:0.5rem;display:none;">
                    <img id="weekly-img-thumb" style="height:60px;border-radius:6px;border:1px solid #e2e8f0;object-fit:contain;background:#f8fafc;">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.4rem;">Special Envelope Image <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                <label style="display:flex;align-items:center;gap:0.5rem;background:#f8fafc;border:1.5px dashed #cbd5e1;border-radius:8px;padding:0.6rem 0.75rem;cursor:pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span id="special-img-label" style="font-size:0.8125rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Choose image…</span>
                    <input type="file" id="special-img" accept="image/*" style="display:none;" onchange="handleImage(this,'special')">
                </label>
                <div id="special-img-preview" style="margin-top:0.5rem;display:none;">
                    <img id="special-img-thumb" style="height:60px;border-radius:6px;border:1px solid #e2e8f0;object-fit:contain;background:#f8fafc;">
                </div>
            </div>
        </div>
    </div>

    {{-- Preview panel --}}
    <div id="preview-panel" style="display:none;background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem;">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0;">3. Preview <span id="preview-count" style="font-weight:400;font-size:0.8125rem;color:#94a3b8;"></span></h2>
            <button onclick="updatePreview()"
                style="padding:0.4rem 0.875rem;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:7px;font-size:0.8125rem;cursor:pointer;">
                Refresh Preview
            </button>
        </div>
        <div id="preview-cards" style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:flex-start;overflow-x:auto;"></div>
        <p style="font-size:0.75rem;color:#94a3b8;margin:0.75rem 0 0;">Showing first 6 pages. The PDF will include all rows.</p>
    </div>

    {{-- Generate button --}}
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
let parsedRows       = [];
let bgColour         = '#dde8f4';
let weeklyImgDataUrl = null;
let specialImgDataUrl = null;

// ── File handling ─────────────────────────────────────────────────────────────
function handleFileSelect(input) {
    const name = input.files[0]?.name || 'Choose .xlsx file…';
    document.getElementById('file-label').textContent = name;
}

function handleImage(input, type) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        if (type === 'weekly') {
            weeklyImgDataUrl = e.target.result;
            document.getElementById('weekly-img-label').textContent = file.name;
            document.getElementById('weekly-img-thumb').src = e.target.result;
            document.getElementById('weekly-img-preview').style.display = 'block';
        } else {
            specialImgDataUrl = e.target.result;
            document.getElementById('special-img-label').textContent = file.name;
            document.getElementById('special-img-thumb').src = e.target.result;
            document.getElementById('special-img-preview').style.display = 'block';
        }
        updatePreview();
    };
    reader.readAsDataURL(file);
}

function parseFile() {
    const file = document.getElementById('xlsx-file').files[0];
    if (!file) { showStatus('parse-status', 'Please choose a file first.', 'error'); return; }

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const wb   = XLSX.read(e.target.result, { type: 'array' });
            const ws   = wb.Sheets[wb.SheetNames[0]];
            const raw  = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
            if (raw.length < 2) throw new Error('Empty spreadsheet');

            const header = raw[0];
            // Find VT column indices
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
                const isSpecial = spiral !== '' || (vt6 !== '' && vt1 === '');

                const vts = [];
                for (let i = 1; i <= 8; i++) vts.push(String(row[vtCols[i]] ?? '').trim());

                const setLeft  = row[4];
                const setRight = row[5];

                parsedRows.push({
                    lineNum:    row[0],
                    day:        String(row[1] ?? '').trim(),
                    month:      String(row[2] ?? '').trim(),
                    year:       String(row[3] ?? '').trim(),
                    setLeft:    setLeft !== '' && setLeft !== null ? parseInt(setLeft) : null,
                    setRight:   setRight !== '' && setRight !== null ? parseInt(setRight) : null,
                    hasSpecialImg: spiral !== '',
                    church:     String(row[8] ?? '').trim(),
                    town:       String(row[9] ?? '').trim(),
                    diocese1:   String(row[10] ?? '').trim(),
                    diocese2:   String(row[11] ?? '').trim(),
                    diocese3:   String(row[12] ?? '').trim(),
                    vts:        vts,
                    isSpecial:  isSpecial,
                });
            });

            if (!parsedRows.length) throw new Error('No data rows found');

            const first = parsedRows.find(r => !r.isSpecial) || parsedRows[0];
            const weeklyCnt  = parsedRows.filter(r => !r.isSpecial).length;
            const specialCnt = parsedRows.filter(r => r.isSpecial).length;
            document.getElementById('parse-summary').innerHTML =
                `<strong>${parsedRows.length} pages</strong> loaded &mdash; ` +
                `<strong>${first.church}</strong>, ${first.town} &mdash; ` +
                `${weeklyCnt} weekly envelope${weeklyCnt !== 1 ? 's' : ''}, ${specialCnt} special${specialCnt !== 1 ? 's' : ''}.`;

            showStatus('parse-status', `Loaded ${parsedRows.length} rows successfully.`, 'success');
            document.getElementById('options-panel').style.display  = 'block';
            document.getElementById('preview-panel').style.display  = 'block';
            document.getElementById('generate-section').style.display = 'block';

            // Auto-select first colour button
            document.querySelector('.colour-btn')?.click();
            updatePreview();
        } catch(err) {
            showStatus('parse-status', 'Could not read the file: ' + err.message, 'error');
        }
    };
    reader.readAsArrayBuffer(file);
}

function showStatus(id, msg, type) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.style.display = 'block';
    el.style.background = type === 'error' ? '#fef2f2' : '#f0fdf4';
    el.style.color       = type === 'error' ? '#991b1b' : '#166534';
    el.style.border      = type === 'error' ? '1px solid #fecaca' : '1px solid #bbf7d0';
}

// ── Colour selection ──────────────────────────────────────────────────────────
function selectColour(btn, colour) {
    bgColour = colour;
    document.querySelectorAll('.colour-btn').forEach(b => b.style.borderColor = 'transparent');
    btn.style.borderColor = '#0f172a';
    document.getElementById('custom-colour').value = colour;
    updatePreview();
}
function selectCustomColour(colour) {
    bgColour = colour;
    document.querySelectorAll('.colour-btn').forEach(b => b.style.borderColor = 'transparent');
    updatePreview();
}

// ── Preview ───────────────────────────────────────────────────────────────────
function updatePreview() {
    if (!parsedRows.length) return;
    const container = document.getElementById('preview-cards');
    container.innerHTML = '';
    const count = Math.min(parsedRows.length, 6);
    document.getElementById('preview-count').textContent = `(${parsedRows.length} pages total)`;

    for (let i = 0; i < count; i++) {
        const row = parsedRows[i];
        const wrap = document.createElement('div');
        wrap.style.cssText = 'flex-shrink:0;';

        // Page label
        const label = document.createElement('div');
        label.style.cssText = 'font-size:0.7rem;color:#94a3b8;margin-bottom:4px;text-align:center;';
        label.textContent = `Page ${i + 1}`;
        wrap.appendChild(label);

        // Page container (2 envelopes stacked)
        const page = document.createElement('div');
        // Scale: 98mm × 156mm displayed at ~0.37x = 260px (wide) × 208px (half) each
        const SCALE = 2.0; // px per mm
        page.style.cssText = `width:${98*SCALE}px;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;background:#e2e8f0;display:flex;flex-direction:column;gap:1px;`;

        [row.setLeft, row.setRight].forEach(setNum => {
            const env = buildEnvelopeHtml(row, setNum, SCALE);
            page.appendChild(env);
        });

        wrap.appendChild(page);
        container.appendChild(wrap);
    }
}

function buildEnvelopeHtml(row, setNum, SCALE) {
    const S = SCALE;
    const W = 98 * S, H = 78 * S;
    const env = document.createElement('div');
    env.style.cssText = `position:relative;width:${W}px;height:${H}px;background:${bgColour};overflow:hidden;flex-shrink:0;`;

    const isSpecial = row.isSpecial;
    const img = isSpecial ? specialImgDataUrl : weeklyImgDataUrl;

    // Church name
    const church = document.createElement('div');
    church.style.cssText = `position:absolute;left:${5*S}px;top:${8*S}px;width:${86*S}px;font-weight:700;font-size:${5.5*S}px;color:#1a1a1a;line-height:1.2;text-transform:uppercase;`;
    church.textContent = row.church || '';
    env.appendChild(church);

    // Town
    const town = document.createElement('div');
    town.style.cssText = `position:absolute;left:${5*S}px;top:${17*S}px;width:${86*S}px;font-weight:700;font-size:${4.5*S}px;color:#1a1a1a;`;
    town.textContent = row.town || '';
    env.appendChild(town);

    // Diocese lines
    let dioceseY = 22;
    [row.diocese1, row.diocese2, row.diocese3].forEach(line => {
        if (!line) return;
        const d = document.createElement('div');
        d.style.cssText = `position:absolute;left:${5*S}px;top:${dioceseY*S}px;width:${86*S}px;font-size:${3.5*S}px;color:#333;`;
        d.textContent = line;
        env.appendChild(d);
        dioceseY += 4.5;
    });

    // Image (left side, vertically centred in bottom half)
    const imgX = 4, imgY = 33, imgW = 26, imgH = 32;
    if (img) {
        const imgEl = document.createElement('img');
        imgEl.src = img;
        imgEl.style.cssText = `position:absolute;left:${imgX*S}px;top:${imgY*S}px;width:${imgW*S}px;height:${imgH*S}px;object-fit:contain;`;
        env.appendChild(imgEl);
    } else {
        const ph = document.createElement('div');
        ph.style.cssText = `position:absolute;left:${imgX*S}px;top:${imgY*S}px;width:${imgW*S}px;height:${imgH*S}px;background:rgba(0,0,0,0.07);border-radius:3px;display:flex;align-items:center;justify-content:center;`;
        const phTxt = document.createElement('span');
        phTxt.style.cssText = `font-size:${3*S}px;color:#aaa;`;
        phTxt.textContent = 'IMAGE';
        ph.appendChild(phTxt);
        env.appendChild(ph);
    }

    // Offering text (right area, vertically centred)
    const offeringLines = getOfferingLines(row);
    if (offeringLines.length) {
        const lineH = 5 * S;
        const totalH = offeringLines.length * lineH;
        const centerY = (imgY * S) + (imgH * S / 2) - (totalH / 2);
        const textX = (imgX + imgW + 3) * S;
        const textW = (98 - imgX - imgW - 3 - 4) * S;

        offeringLines.forEach((line, i) => {
            const t = document.createElement('div');
            t.style.cssText = `position:absolute;left:${textX}px;top:${centerY + i * lineH}px;width:${textW}px;font-style:italic;font-size:${4*S}px;color:#1a1a1a;text-align:center;`;
            t.textContent = line;
            env.appendChild(t);
        });
    }

    // Set number (bottom left)
    if (setNum !== null && setNum > 0) {
        const num = document.createElement('div');
        num.style.cssText = `position:absolute;left:${5*S}px;bottom:${5*S}px;font-weight:700;font-size:${4.5*S}px;color:#1a1a1a;`;
        num.textContent = setNum;
        env.appendChild(num);
    }

    // Date (bottom right)
    const date = buildDate(row);
    if (date) {
        const dt = document.createElement('div');
        dt.style.cssText = `position:absolute;right:${5*S}px;bottom:${5*S}px;font-size:${3.5*S}px;color:#333;text-align:right;`;
        dt.textContent = date;
        env.appendChild(dt);
    }

    return env;
}

function getOfferingLines(row) {
    if (row.isSpecial) {
        return [row.vts[5], row.vts[6]].filter(Boolean); // VT6, VT7
    }
    return row.vts.filter(Boolean);
}

function buildDate(row) {
    if (!row.day && !row.month && !row.year) return '';
    return [row.day, row.month, row.year].filter(Boolean).join(' ');
}

// ── PDF Generation ────────────────────────────────────────────────────────────
async function generatePDF() {
    if (!parsedRows.length) return;
    const btn = document.getElementById('generate-btn');
    const status = document.getElementById('generate-status');
    btn.disabled = true;
    btn.textContent = 'Generating…';
    status.style.display = 'block';
    status.textContent   = `Building ${parsedRows.length} pages…`;

    // Yield to browser to update UI
    await new Promise(r => setTimeout(r, 50));

    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'mm', format: [98, 156], orientation: 'portrait' });
        const church = (parsedRows.find(r => !r.isSpecial) || parsedRows[0])?.church || 'church-envelopes';

        parsedRows.forEach((row, idx) => {
            if (idx > 0) doc.addPage();
            drawEnvelope(doc, row, 0, row.setLeft);
            drawEnvelope(doc, row, 78, row.setRight);
            // Dashed cut line at midpoint
            doc.setDrawColor(160, 160, 160);
            doc.setLineDashPattern([1.5, 1.5], 0);
            doc.setLineWidth(0.2);
            doc.line(2, 78, 96, 78);
            doc.setLineDashPattern([], 0);
        });

        const filename = sanitiseName(church) + '-envelopes.pdf';
        doc.save(filename);
        status.textContent = `Done! ${parsedRows.length} pages saved.`;
        status.style.color = '#166534';
    } catch(e) {
        status.textContent = 'PDF generation failed: ' + e.message;
        status.style.color = '#991b1b';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Generate & Download PDF';
    }
}

function drawEnvelope(doc, row, yBase, setNum) {
    const isSpecial = row.isSpecial;
    const imgDataUrl = isSpecial ? specialImgDataUrl : weeklyImgDataUrl;
    const rgb = hexToRgb(bgColour);

    // Background
    doc.setFillColor(rgb.r, rgb.g, rgb.b);
    doc.rect(0, yBase, 98, 78, 'F');

    let y = yBase + 9;

    // Church name — bold, uppercase, wrapped
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(20, 20, 20);
    const churchName = (row.church || '').toUpperCase();
    const churchLines = doc.splitTextToSize(churchName, 86);
    doc.text(churchLines, 6, y);
    y += churchLines.length * 5.2;

    // Town
    if (row.town) {
        doc.setFontSize(8);
        doc.text(row.town.toUpperCase(), 6, y);
        y += 5;
    }

    // Diocese lines
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.setTextColor(40, 40, 40);
    [row.diocese1, row.diocese2, row.diocese3].forEach(line => {
        if (line) { doc.text(line, 6, y); y += 4; }
    });

    // Image (left side, centred vertically in image zone)
    const imgX = 4, imgY = yBase + 32, imgW = 26, imgH = 33;
    if (imgDataUrl) {
        try {
            const fmt = imgDataUrl.startsWith('data:image/png') ? 'PNG' : 'JPEG';
            doc.addImage(imgDataUrl, fmt, imgX, imgY, imgW, imgH, undefined, 'FAST');
        } catch(e) {
            drawImgPlaceholder(doc, imgX, imgY, imgW, imgH);
        }
    } else {
        drawImgPlaceholder(doc, imgX, imgY, imgW, imgH);
    }

    // Offering text — italic, centred in right area
    const offeringLines = getOfferingLines(row);
    if (offeringLines.length) {
        doc.setFont('helvetica', 'italic');
        doc.setFontSize(8.5);
        doc.setTextColor(20, 20, 20);
        const textX   = imgX + imgW + 3;
        const textW   = 98 - textX - 5;
        const lineH   = 5;
        const totalH  = offeringLines.length * lineH;
        const centerY = imgY + (imgH / 2) - (totalH / 2) + 4;
        const cx      = textX + textW / 2;
        offeringLines.forEach((line, i) => {
            const wrapped = doc.splitTextToSize(line, textW);
            wrapped.forEach((wl, wi) => {
                doc.text(wl, cx, centerY + i * lineH + wi * lineH, { align: 'center' });
            });
        });
    }

    // Set number (bottom left) — only if > 0
    if (setNum !== null && setNum > 0) {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(20, 20, 20);
        doc.text(String(setNum), 5, yBase + 73);
    }

    // Date (bottom right)
    const date = buildDate(row);
    if (date) {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(50, 50, 50);
        doc.text(date, 93, yBase + 73, { align: 'right' });
    }
}

function drawImgPlaceholder(doc, x, y, w, h) {
    doc.setFillColor(220, 220, 220);
    doc.rect(x, y, w, h, 'F');
}

// ── Utilities ─────────────────────────────────────────────────────────────────
function hexToRgb(hex) {
    const r = parseInt(hex.slice(1,3),16);
    const g = parseInt(hex.slice(3,5),16);
    const b = parseInt(hex.slice(5,7),16);
    return { r, g, b };
}
function sanitiseName(str) {
    return str.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'') || 'envelopes';
}

// Auto-select blue on load
document.addEventListener('DOMContentLoaded', () => {
    const firstBtn = document.querySelector('.colour-btn');
    if (firstBtn) { firstBtn.style.borderColor = '#0f172a'; }
});
</script>
</x-layout>
