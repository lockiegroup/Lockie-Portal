<x-layout title="Settings — Clocking">
<main style="max-width:900px;margin:0 auto;padding:2rem 1.5rem;">

    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">Clocking</h1>
        @include('clocking._tabs', ['active' => 'settings'])
    </div>

    {{-- Alert timing --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.75rem;">Alert timing</h2>
        <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;">Send an alert this many minutes after an employee's scheduled start if they haven't clocked in.</p>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <input type="number" id="grace-mins" value="{{ $graceMins }}" min="0" max="120"
                style="width:80px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;">
            <span style="font-size:0.875rem;color:#475569;">minutes</span>
            <button onclick="saveGrace()"
                style="padding:0.4rem 1rem;background:#6366f1;color:#fff;border:none;border-radius:7px;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                Save
            </button>
            <span id="grace-result" style="font-size:0.8125rem;"></span>
        </div>
    </div>

    {{-- Global recipients --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Global alert recipients</h2>
        <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;">These addresses receive alerts for <strong>all employees</strong>, regardless of category.</p>

        <div id="global-list">
            @foreach($globalRecipients as $r)
            <div id="recipient-{{ $r->id }}" style="display:flex;align-items:center;gap:0.75rem;padding:0.4rem 0;border-bottom:1px solid #f8fafc;">
                <span style="flex:1;font-size:0.875rem;color:#1e293b;">{{ $r->email }}</span>
                @if($r->label)<span style="font-size:0.75rem;color:#94a3b8;">{{ $r->label }}</span>@endif
                <button onclick="removeRecipient({{ $r->id }})"
                    style="padding:2px 8px;background:none;border:1px solid #fecaca;border-radius:5px;color:#ef4444;font-size:0.75rem;cursor:pointer;">Remove</button>
            </div>
            @endforeach
        </div>
        <div style="margin-top:0.75rem;">
            @include('clocking._recipient-form', ['categoryId' => 'null', 'formId' => 'global'])
        </div>
    </div>

    {{-- Categories --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <div>
                <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Categories</h2>
                <p style="font-size:0.8125rem;color:#64748b;margin:0;">Group employees so managers only receive alerts for their team.</p>
            </div>
            <button onclick="toggleAddCategory()"
                style="padding:0.4rem 0.875rem;background:#1e293b;color:#fff;border:none;border-radius:7px;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                + Add Category
            </button>
        </div>

        <div id="add-category-form" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:1rem;">
            <div style="display:flex;gap:0.5rem;align-items:flex-end;flex-wrap:wrap;">
                <div>
                    <label style="font-size:0.75rem;color:#64748b;display:block;margin-bottom:3px;">Name</label>
                    <input type="text" id="new-cat-name" maxlength="80" placeholder="e.g. Factory"
                        style="padding:0.4rem 0.75rem;border:1px solid #e2e8f0;border-radius:6px;font-size:0.875rem;width:180px;">
                </div>
                <div>
                    <label style="font-size:0.75rem;color:#64748b;display:block;margin-bottom:3px;">Colour</label>
                    <input type="color" id="new-cat-color" value="#6366f1"
                        style="width:42px;height:34px;border:1px solid #e2e8f0;border-radius:6px;padding:2px;cursor:pointer;">
                </div>
                <button onclick="addCategory()"
                    style="padding:0.4rem 1rem;background:#6366f1;color:#fff;border:none;border-radius:6px;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                    Add
                </button>
                <span id="add-cat-result" style="font-size:0.8125rem;"></span>
            </div>
        </div>

        <div id="cat-list">
        @forelse($categories as $cat)
        <div id="cat-{{ $cat->id }}" style="border:1px solid #e2e8f0;border-radius:10px;padding:1rem;margin-bottom:0.75rem;">
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                <div style="width:14px;height:14px;border-radius:50%;background:{{ $cat->color }};flex-shrink:0;"></div>
                <span id="cat-name-{{ $cat->id }}" style="font-size:0.9375rem;font-weight:700;color:#1e293b;flex:1;">{{ $cat->name }}</span>
                <button onclick="startRenameCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->color }}')"
                    style="padding:2px 8px;background:none;border:1px solid #e2e8f0;border-radius:5px;color:#475569;font-size:0.75rem;cursor:pointer;">Rename</button>
                <button onclick="deleteCategory({{ $cat->id }})"
                    style="padding:2px 8px;background:none;border:1px solid #fecaca;border-radius:5px;color:#ef4444;font-size:0.75rem;cursor:pointer;">Delete</button>
            </div>
            {{-- Rename inline form (hidden) --}}
            <div id="cat-rename-{{ $cat->id }}" style="display:none;margin-bottom:0.75rem;display:none;">
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <input type="text" id="cat-rename-name-{{ $cat->id }}" maxlength="80"
                        style="padding:0.35rem 0.6rem;border:1px solid #6366f1;border-radius:6px;font-size:0.875rem;width:160px;">
                    <input type="color" id="cat-rename-color-{{ $cat->id }}"
                        style="width:36px;height:30px;border:1px solid #e2e8f0;border-radius:5px;padding:1px;cursor:pointer;">
                    <button onclick="saveRenameCategory({{ $cat->id }})"
                        style="padding:0.35rem 0.75rem;background:#6366f1;color:#fff;border:none;border-radius:5px;font-size:0.75rem;cursor:pointer;">Save</button>
                    <button onclick="cancelRename({{ $cat->id }})"
                        style="padding:0.35rem 0.6rem;background:none;border:1px solid #e2e8f0;border-radius:5px;color:#64748b;font-size:0.75rem;cursor:pointer;">Cancel</button>
                </div>
            </div>
            {{-- Recipients --}}
            <div style="margin-left:1.5rem;">
                <p style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;margin:0 0 0.375rem;">Alert recipients</p>
                <div id="cat-recipients-{{ $cat->id }}">
                    @foreach($cat->alertRecipients as $r)
                    <div id="recipient-{{ $r->id }}" style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0;">
                        <span style="flex:1;font-size:0.875rem;color:#1e293b;">{{ $r->email }}</span>
                        @if($r->label)<span style="font-size:0.75rem;color:#94a3b8;">{{ $r->label }}</span>@endif
                        <button onclick="removeRecipient({{ $r->id }})"
                            style="padding:2px 7px;background:none;border:1px solid #fecaca;border-radius:5px;color:#ef4444;font-size:0.7rem;cursor:pointer;">×</button>
                    </div>
                    @endforeach
                </div>
                @include('clocking._recipient-form', ['categoryId' => $cat->id, 'formId' => 'cat-'.$cat->id])
            </div>
        </div>
        @empty
        <p style="color:#94a3b8;font-size:0.875rem;">No categories yet.</p>
        @endforelse
        </div>
    </div>

</main>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Grace period ──────────────────────────────────────────────────────────────
async function saveGrace() {
    const mins = document.getElementById('grace-mins').value;
    const res  = document.getElementById('grace-result');
    const r = await fetch('/clocking/settings', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ alert_grace_minutes: parseInt(mins) }),
    });
    res.innerHTML = r.ok ? '<span style="color:#22c55e">Saved</span>' : '<span style="color:#ef4444">Error</span>';
    setTimeout(() => res.textContent = '', 2000);
}

// ── Categories ────────────────────────────────────────────────────────────────
function toggleAddCategory() {
    const f = document.getElementById('add-category-form');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

async function addCategory() {
    const name  = document.getElementById('new-cat-name').value.trim();
    const color = document.getElementById('new-cat-color').value;
    const res   = document.getElementById('add-cat-result');
    if (!name) { res.innerHTML = '<span style="color:#ef4444">Name required.</span>'; return; }
    const r = await fetch('/clocking/settings/categories', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, color }),
    });
    if (r.ok) location.reload();
    else res.innerHTML = '<span style="color:#ef4444">Error.</span>';
}

function startRenameCategory(id, name, color) {
    document.getElementById('cat-rename-' + id).style.display = 'flex';
    document.getElementById('cat-rename-name-' + id).value  = name;
    document.getElementById('cat-rename-color-' + id).value = color;
}
function cancelRename(id) {
    document.getElementById('cat-rename-' + id).style.display = 'none';
}

async function saveRenameCategory(id) {
    const name  = document.getElementById('cat-rename-name-' + id).value.trim();
    const color = document.getElementById('cat-rename-color-' + id).value;
    if (!name) return;
    const r = await fetch(`/clocking/settings/categories/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, color }),
    });
    if (r.ok) location.reload();
}

async function deleteCategory(id) {
    if (!confirm('Delete this category? Employees in it will become uncategorised.')) return;
    const r = await fetch(`/clocking/settings/categories/${id}`, {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
    });
    if (r.ok) document.getElementById('cat-' + id)?.remove();
}

// ── Recipients ────────────────────────────────────────────────────────────────
async function addRecipient(categoryId, formId) {
    const email = document.getElementById('email-' + formId).value.trim();
    const label = document.getElementById('label-' + formId).value.trim();
    const res   = document.getElementById('res-' + formId);
    if (!email) { res.innerHTML = '<span style="color:#ef4444">Email required.</span>'; return; }
    const r = await fetch('/clocking/settings/recipients', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ category_id: categoryId === 'null' ? null : categoryId, email, label: label || null }),
    });
    if (r.ok) location.reload();
    else {
        let msg = 'Error.';
        try { const j = await r.json(); msg = j.message || Object.values(j.errors||{})[0]?.[0] || msg; } catch(_){}
        res.innerHTML = `<span style="color:#ef4444">${msg}</span>`;
    }
}

async function removeRecipient(id) {
    const r = await fetch(`/clocking/settings/recipients/${id}`, {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
    });
    if (r.ok) document.getElementById('recipient-' + id)?.remove();
}
</script>
</x-layout>
