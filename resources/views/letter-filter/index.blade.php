<x-layout title="Letter Filter — Lockie Portal">
<main style="max-width:860px;margin:0 auto;padding:2rem 1.5rem;">

    <div style="margin-bottom:1.75rem;">
        <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">Letter Filter</h1>
        <p style="font-size:0.875rem;color:#64748b;margin:0.25rem 0 0;">Upload a mailing PDF and paste the account codes of customers who have already ordered. The tool will produce a filtered PDF with those pages removed.</p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

        {{-- PDF Upload --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h2 style="font-size:0.875rem;font-weight:600;color:#1e293b;margin:0 0 0.875rem;">1. Upload Mailing PDF</h2>
            <label id="pdf-drop"
                   style="display:flex;flex-direction:column;align-items:center;justify-content:center;border:2px dashed #cbd5e1;border-radius:0.625rem;padding:2rem 1rem;cursor:pointer;transition:border-color 0.15s;gap:8px;"
                   onmouseover="this.style.borderColor='#92aac8'" onmouseout="if(!window._pdfChosen)this.style.borderColor='#cbd5e1'">
                <svg style="width:28px;height:28px;color:#94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span id="pdf-label" style="font-size:0.875rem;color:#64748b;">Click to choose PDF or drag &amp; drop</span>
                <input type="file" id="pdf-input" accept=".pdf" style="display:none;" onchange="pdfChosen(this)">
            </label>
        </div>

        {{-- Account Codes --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h2 style="font-size:0.875rem;font-weight:600;color:#1e293b;margin:0 0 0.25rem;">2. Paste Account Codes to Exclude</h2>
            <p style="font-size:0.75rem;color:#94a3b8;margin:0 0 0.75rem;">One per line, or comma/space separated. These are customers who have already ordered.</p>
            <textarea id="codes-input" rows="8" placeholder="C0545&#10;E0707&#10;M0529&#10;..."
                style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.8125rem;color:#1e293b;resize:vertical;box-sizing:border-box;font-family:monospace;"></textarea>
            <div style="font-size:0.75rem;color:#94a3b8;margin-top:4px;text-align:right;">
                <span id="code-count">0</span> codes entered
            </div>
        </div>

    </div>

    <div style="display:flex;justify-content:center;margin-bottom:2rem;">
        <button id="process-btn" onclick="processFilter()"
                style="background:#1e293b;color:#fff;border:none;border-radius:0.625rem;padding:0.75rem 2.5rem;font-size:0.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:10px;">
            <span id="btn-text">Filter Letters</span>
            <svg id="btn-spinner" style="display:none;width:18px;height:18px;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 2a10 10 0 0 1 10 10"/></svg>
        </button>
    </div>

    {{-- Results --}}
    <div id="results" style="display:none;">

        {{-- Stats --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:1.5rem;">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Total Pages</p>
                <p id="stat-total" style="font-size:1.75rem;font-weight:700;color:#1e293b;line-height:1;">—</p>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Excluded</p>
                <p id="stat-excluded" style="font-size:1.75rem;font-weight:700;color:#dc2626;line-height:1;">—</p>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Remaining</p>
                <p id="stat-included" style="font-size:1.75rem;font-weight:700;color:#16a34a;line-height:1;">—</p>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Exceptions</p>
                <p id="stat-exceptions" style="font-size:1.75rem;font-weight:700;color:#d97706;line-height:1;">—</p>
            </div>
        </div>

        {{-- Downloads --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h2 style="font-size:0.875rem;font-weight:600;color:#1e293b;margin:0 0 1rem;">Downloads</h2>
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
                <a id="dl-pdf" href="#" style="display:inline-flex;align-items:center;gap:8px;background:#1e293b;color:#fff;text-decoration:none;padding:9px 18px;border-radius:8px;font-size:0.875rem;font-weight:600;">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    Filtered PDF
                </a>
                <a id="dl-excluded" href="#" style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:#1e293b;text-decoration:none;padding:9px 18px;border-radius:8px;font-size:0.875rem;font-weight:600;border:1px solid #e2e8f0;">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    Excluded CSV
                </a>
                <a id="dl-included" href="#" style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:#1e293b;text-decoration:none;padding:9px 18px;border-radius:8px;font-size:0.875rem;font-weight:600;border:1px solid #e2e8f0;">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    Included CSV
                </a>
                <a id="dl-exceptions" href="#" style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:#d97706;text-decoration:none;padding:9px 18px;border-radius:8px;font-size:0.875rem;font-weight:600;border:1px solid #fed7aa;">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    Exception Report
                </a>
            </div>
        </div>

    </div>

    {{-- Error --}}
    <div id="error-box" style="display:none;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:0.625rem;padding:0.875rem 1.125rem;font-size:0.875rem;"></div>

</main>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
window._pdfChosen = false;

function pdfChosen(input) {
    if (input.files.length) {
        document.getElementById('pdf-label').textContent = input.files[0].name;
        document.getElementById('pdf-drop').style.borderColor = '#92aac8';
        window._pdfChosen = true;
    }
}

document.getElementById('codes-input').addEventListener('input', function() {
    const codes = this.value.trim().split(/[\s,;]+/).filter(c => c.length > 0);
    document.getElementById('code-count').textContent = codes.length;
});

// Drag and drop
const drop = document.getElementById('pdf-drop');
drop.addEventListener('dragover', e => { e.preventDefault(); drop.style.borderColor = '#92aac8'; });
drop.addEventListener('dragleave', () => { if (!window._pdfChosen) drop.style.borderColor = '#cbd5e1'; });
drop.addEventListener('drop', e => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file && file.type === 'application/pdf') {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('pdf-input').files = dt.files;
        document.getElementById('pdf-label').textContent = file.name;
        window._pdfChosen = true;
    }
});

async function processFilter() {
    const pdfInput   = document.getElementById('pdf-input');
    const codesInput = document.getElementById('codes-input');

    document.getElementById('error-box').style.display = 'none';
    document.getElementById('results').style.display = 'none';

    if (!pdfInput.files.length) {
        showError('Please select a PDF file.');
        return;
    }
    if (!codesInput.value.trim()) {
        showError('Please paste at least one account code to exclude.');
        return;
    }

    // UI: loading state
    document.getElementById('btn-text').textContent = 'Processing…';
    document.getElementById('btn-spinner').style.display = 'block';
    document.getElementById('process-btn').disabled = true;

    const form = new FormData();
    form.append('pdf',   pdfInput.files[0]);
    form.append('codes', codesInput.value);
    form.append('_token', csrf);

    try {
        const res  = await fetch('{{ route("letter-filter.process") }}', { method: 'POST', body: form });
        const json = await res.json();

        if (!res.ok || json.error) {
            showError(json.error || json.message || 'An error occurred. Please try again.');
            return;
        }

        const key = json.key;

        // Fetch meta
        const metaRes = await fetch(`{{ url("/letter-filter/download") }}/${key}/meta.json`);
        const meta    = await metaRes.json();

        document.getElementById('stat-total').textContent      = meta.total;
        document.getElementById('stat-excluded').textContent   = meta.excluded;
        document.getElementById('stat-included').textContent   = meta.included;
        document.getElementById('stat-exceptions').textContent = meta.no_code + meta.duplicates;

        const base = `{{ url("/letter-filter/download") }}/${key}`;
        document.getElementById('dl-pdf').href        = base + '/filtered.pdf';
        document.getElementById('dl-excluded').href   = base + '/excluded.csv';
        document.getElementById('dl-included').href   = base + '/included.csv';
        document.getElementById('dl-exceptions').href = base + '/exceptions.csv';

        document.getElementById('results').style.display = 'block';
        document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });

    } catch (e) {
        showError('An unexpected error occurred. Please try again.');
    } finally {
        document.getElementById('btn-text').textContent = 'Filter Letters';
        document.getElementById('btn-spinner').style.display = 'none';
        document.getElementById('process-btn').disabled = false;
    }
}

function showError(msg) {
    const box = document.getElementById('error-box');
    box.textContent = msg;
    box.style.display = 'block';
}
</script>
</x-layout>
