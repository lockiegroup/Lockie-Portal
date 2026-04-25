<x-layout title="Stock Watchlist — Lockie Portal">

<style>
.sw-wrap { overflow-x: auto; }
.sw-table { border-collapse: collapse; font-size: 0.8rem; min-width: 100%; white-space: nowrap; }
.sw-table th {
    background: #0f172a; color: #e2e8f0;
    padding: 7px 10px; text-align: right; font-weight: 600;
    position: sticky; top: 0; z-index: 2;
    border-right: 1px solid #1e293b;
}
.sw-table th:first-child, .sw-table th:nth-child(2) { text-align: left; }
.sw-table td {
    padding: 5px 10px; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9;
    color: #334155; text-align: right; vertical-align: middle;
}
.sw-table td:first-child, .sw-table td:nth-child(2) { text-align: left; }
.sw-cat-row td {
    background: #f8fafc; color: #0f172a; font-weight: 700;
    font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em;
    padding: 8px 10px; border-top: 2px solid #e2e8f0;
    text-align: left !important;
}
.sw-table tr:hover td { background: #f8fafc; }
.sw-cat-row:hover td { background: #f1f5f9 !important; }
.sw-num { font-variant-numeric: tabular-nums; }
.sw-input {
    width: 70px; text-align: right; border: 1px solid transparent;
    border-radius: 4px; padding: 2px 5px; font-size: 0.8rem; color: #0f172a;
    background: transparent; font-variant-numeric: tabular-nums;
}
.sw-input:hover { border-color: #cbd5e1; background: white; }
.sw-input:focus { border-color: #6366f1; outline: none; background: white; }
.sw-input-wide { width: 120px; }
.sw-badge {
    display: inline-block; padding: 1px 7px; border-radius: 10px;
    font-size: 0.7rem; font-weight: 600;
}
.sw-badge-ok   { background: #dcfce7; color: #166534; }
.sw-badge-low  { background: #fef9c3; color: #854d0e; }
.sw-badge-out  { background: #fee2e2; color: #991b1b; }
.sw-badge-disc { background: #f1f5f9; color: #94a3b8; }
.sw-disc-row td { opacity: 0.55; }
.sw-disc-row:hover td { opacity: 0.75; }
.sw-add-row td { background: #fafafa; }
.btn-del {
    background: none; border: none; cursor: pointer;
    color: #cbd5e1; padding: 2px 4px; border-radius: 4px;
    line-height:0; transition: color 0.15s;
}
.btn-del:hover { color: #ef4444; }
.btn-ghost {
    background: none; border: 1px solid #e2e8f0; color: #64748b;
    padding: 5px 12px; border-radius: 6px; font-size: 0.8rem;
    cursor: pointer; transition: all 0.15s;
}
.btn-ghost:hover { border-color: #94a3b8; color: #334155; }
.info-input {
    border: 1px solid transparent; border-radius: 4px;
    padding: 2px 5px; font-size: 0.8rem; color: #64748b;
    background: transparent; width: 140px;
}
.info-input:hover { border-color: #cbd5e1; background: white; }
.info-input:focus { border-color: #6366f1; outline: none; background: white; color: #334155; }
</style>

<main style="padding:2rem 1.5rem;max-width:100%;">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 4px;">Stock Watchlist</h1>
            <p style="font-size:0.875rem;color:#64748b;margin:0;">
                JW Products stock ordering tracker — sales history, on-hand levels, and order quantities.
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;flex-wrap:wrap;">
            <span id="sync-status" style="font-size:0.8rem;color:#94a3b8;">
                @if($syncedAt)
                    Last synced {{ \Carbon\Carbon::parse($syncedAt)->diffForHumans() }}
                @else
                    Not yet synced
                @endif
            </span>
            <button class="btn-ghost" onclick="openCatModal()">Manage Categories</button>
            <button id="sync-btn" onclick="runSync()"
                style="display:flex;align-items:center;gap:7px;padding:8px 16px;background:#0f172a;color:white;border:none;border-radius:8px;font-size:0.875rem;font-weight:600;cursor:pointer;transition:background 0.15s;"
                onmouseover="this.style.background='#1e293b'" onmouseout="this.style.background='#0f172a'">
                <svg id="sync-icon" style="width:15px;height:15px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
                Sync from Unleashed
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <div class="sw-wrap">
            <table class="sw-table">
                <thead>
                    <tr>
                        <th style="text-align:left;min-width:110px;">Code</th>
                        <th style="text-align:left;min-width:180px;">Description</th>
                        <th style="text-align:left;min-width:140px;">Notes</th>
                        <th>Lead<br><small style="font-weight:400;opacity:0.7;">(mo)</small></th>
                        @foreach($years as $yr)
                            <th>{{ $yr }}<br><small style="font-weight:400;opacity:0.7;">units</small></th>
                        @endforeach
                        <th>Avg/Mo</th>
                        <th>On Hand</th>
                        <th>Alloc'd</th>
                        <th>On Order</th>
                        <th>PO Date</th>
                        <th>Required</th>
                        <th>To Order</th>
                        <th>Price (£)</th>
                        <th>Total (£)</th>
                        <th>Status</th>
                        <th>Disc</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($categories as $cat)
                    {{-- Category header row --}}
                    <tr class="sw-cat-row" data-cat-id="{{ $cat->id }}">
                        <td colspan="{{ 15 + count($years) }}">
                            {{ $cat->name }}
                            <span style="font-weight:400;font-size:0.7rem;color:#94a3b8;margin-left:8px;">
                                ({{ $cat->items->count() }} {{ Str::plural('product', $cat->items->count()) }})
                            </span>
                        </td>
                    </tr>

                    {{-- Product rows --}}
                    @foreach($cat->items as $item)
                    @php
                        $stock     = $item->stock;
                        $onHand    = $stock ? (float)$stock->qty_on_hand : null;
                        $allocated = $stock ? (float)$stock->qty_allocated : 0;
                        $onOrder   = $stock ? (float)$stock->qty_on_order : 0;
                        $poDate    = $stock?->po_expected_date;
                        $avgMo     = $item->avg_monthly;
                        $reqQty    = $item->required_qty;
                        $toOrder   = (float)($item->to_order_qty ?? 0);
                        $price     = (float)($item->unit_price ?? 0);
                        $total     = $toOrder * $price;

                        // Status badge
                        if ($item->discontinued) {
                            $badgeClass = 'sw-badge-disc'; $badgeText = 'Discontinued';
                        } elseif ($onHand === null) {
                            $badgeClass = 'sw-badge-low'; $badgeText = 'No Data';
                        } elseif (($onHand - $allocated) <= 0) {
                            $badgeClass = 'sw-badge-out'; $badgeText = 'Out of Stock';
                        } elseif ($reqQty > 0) {
                            $badgeClass = 'sw-badge-low'; $badgeText = 'Order Needed';
                        } else {
                            $badgeClass = 'sw-badge-ok'; $badgeText = 'OK';
                        }
                    @endphp
                    <tr class="{{ $item->discontinued ? 'sw-disc-row' : '' }}" data-item-id="{{ $item->id }}">
                        <td style="font-weight:600;color:#0f172a;">{{ $item->product_code }}</td>
                        <td style="color:#475569;max-width:200px;overflow:hidden;text-overflow:ellipsis;" title="{{ $item->product_name }}">
                            {{ $item->product_name ?? '—' }}
                        </td>
                        <td>
                            <input type="text" class="info-input"
                                value="{{ $item->info }}"
                                placeholder="Add notes…"
                                onblur="saveField({{ $item->id }}, 'info', this.value)"
                                onkeydown="if(event.key==='Enter')this.blur()">
                        </td>
                        <td>
                            <input type="number" class="sw-input" style="width:50px;"
                                value="{{ $item->lead_time_months ?? 3 }}" min="1" max="120"
                                onblur="saveField({{ $item->id }}, 'lead_time_months', this.value)"
                                onkeydown="if(event.key==='Enter')this.blur()">
                        </td>
                        @foreach($years as $yr)
                        <td class="sw-num">
                            @php $yrQty = $item->yearly[$yr] ?? 0; @endphp
                            {{ $yrQty > 0 ? number_format($yrQty, 0) : '—' }}
                        </td>
                        @endforeach
                        <td class="sw-num">{{ $avgMo > 0 ? number_format($avgMo, 1) : '—' }}</td>
                        <td class="sw-num {{ ($onHand !== null && ($onHand - $allocated) <= 0) ? 'text-red-600' : '' }}">
                            {{ $onHand !== null ? number_format($onHand, 0) : '—' }}
                        </td>
                        <td class="sw-num" style="color:#94a3b8;">
                            {{ $allocated > 0 ? number_format($allocated, 0) : '—' }}
                        </td>
                        <td class="sw-num" style="color:#0891b2;">
                            {{ $onOrder > 0 ? number_format($onOrder, 0) : '—' }}
                        </td>
                        <td style="color:#64748b;font-size:0.75rem;">
                            {{ $poDate ? \Carbon\Carbon::parse($poDate)->format('d/m/y') : '—' }}
                        </td>
                        <td class="sw-num" style="{{ $reqQty > 0 ? 'color:#b45309;font-weight:700;' : 'color:#94a3b8;' }}">
                            {{ $reqQty > 0 ? number_format($reqQty, 0) : '—' }}
                        </td>
                        <td>
                            <input type="number" class="sw-input" min="0" step="1"
                                value="{{ $toOrder > 0 ? (int)$toOrder : '' }}"
                                placeholder="{{ $reqQty > 0 ? $reqQty : '0' }}"
                                onblur="saveField({{ $item->id }}, 'to_order_qty', this.value)"
                                onkeydown="if(event.key==='Enter')this.blur()">
                        </td>
                        <td>
                            <input type="number" class="sw-input" min="0" step="0.01"
                                value="{{ $price > 0 ? number_format($price, 2, '.', '') : '' }}"
                                placeholder="0.00"
                                onblur="saveField({{ $item->id }}, 'unit_price', this.value)"
                                onkeydown="if(event.key==='Enter')this.blur()">
                        </td>
                        <td class="sw-num" style="font-weight:600;">
                            {{ $total > 0 ? '£'.number_format($total, 2) : '—' }}
                        </td>
                        <td><span class="sw-badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
                        <td style="text-align:center;">
                            <input type="checkbox" {{ $item->discontinued ? 'checked' : '' }}
                                onchange="saveField({{ $item->id }}, 'discontinued', this.checked ? 1 : 0)"
                                style="width:14px;height:14px;cursor:pointer;accent-color:#6366f1;">
                        </td>
                        <td style="text-align:center;">
                            <button class="btn-del" onclick="deleteItem({{ $item->id }}, this)" title="Remove product">
                                <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @endforeach

                    {{-- Add product row --}}
                    <tr class="sw-add-row">
                        <td colspan="{{ 15 + count($years) }}" style="padding:6px 10px;">
                            <form style="display:inline-flex;gap:8px;align-items:center;"
                                onsubmit="addItem(event, {{ $cat->id }}, this)">
                                <input type="text" name="product_code" placeholder="Product code…"
                                    style="border:1px solid #e2e8f0;border-radius:6px;padding:4px 10px;font-size:0.8rem;color:#334155;width:160px;text-transform:uppercase;"
                                    oninput="this.value=this.value.toUpperCase()" required>
                                <input type="number" name="lead_time_months" placeholder="Lead (mo)"
                                    style="border:1px solid #e2e8f0;border-radius:6px;padding:4px 8px;font-size:0.8rem;color:#334155;width:90px;"
                                    min="1" max="120" value="3">
                                <button type="submit"
                                    style="padding:4px 14px;background:#0f172a;color:white;border:none;border-radius:6px;font-size:0.8rem;font-weight:600;cursor:pointer;">
                                    + Add
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 15 + count($years) }}" style="padding:2rem;text-align:center;color:#94a3b8;">
                        No categories yet. Click <strong>Manage Categories</strong> to add one.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</main>

{{-- Manage Categories Modal --}}
<div id="cat-modal" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;">
    <div style="background:white;border-radius:14px;width:440px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,0.2);padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <h2 style="font-size:1.1rem;font-weight:700;color:#1e293b;margin:0;">Manage Categories</h2>
            <button onclick="closeCatModal()" style="background:none;border:none;font-size:1.3rem;color:#94a3b8;cursor:pointer;line-height:1;padding:2px 6px;">&times;</button>
        </div>

        {{-- Existing categories --}}
        <div id="cat-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px;max-height:280px;overflow-y:auto;">
            @foreach($categories as $cat)
            <div id="cat-row-{{ $cat->id }}" style="display:flex;align-items:center;gap:8px;">
                <input type="text" value="{{ $cat->name }}" data-cat-id="{{ $cat->id }}"
                    style="flex:1;border:1px solid #e2e8f0;border-radius:7px;padding:7px 10px;font-size:0.875rem;color:#334155;"
                    onblur="renameCategory(this)" onkeydown="if(event.key==='Enter')this.blur()">
                <button onclick="deleteCategory({{ $cat->id }}, this)" class="btn-del"
                    style="color:#e2e8f0;padding:4px 6px;">
                    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
                    </svg>
                </button>
            </div>
            @endforeach
            @if($categories->isEmpty())
            <p id="no-cats-msg" style="font-size:0.875rem;color:#94a3b8;margin:0;">No categories yet.</p>
            @endif
        </div>

        {{-- Add category --}}
        <form onsubmit="addCategory(event, this)" style="display:flex;gap:8px;">
            <input type="text" name="name" placeholder="New category name…"
                style="flex:1;border:1px solid #e2e8f0;border-radius:7px;padding:7px 10px;font-size:0.875rem;color:#334155;">
            <button type="submit"
                style="padding:7px 18px;background:#0f172a;color:white;border:none;border-radius:7px;font-size:0.875rem;font-weight:600;cursor:pointer;">
                Add
            </button>
        </form>
    </div>
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

// ── Sync ──────────────────────────────────────────────────────────────────────
function runSync() {
    const btn  = document.getElementById('sync-btn');
    const icon = document.getElementById('sync-icon');
    btn.disabled = true;
    icon.style.animation = 'spin 1s linear infinite';
    document.getElementById('sync-status').textContent = 'Syncing…';

    fetch('{{ route("stock-watchlist.sync") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            document.getElementById('sync-status').textContent = `Synced ${d.products} products`;
            setTimeout(() => location.reload(), 600);
        } else {
            alert('Sync failed: ' + (d.error || 'Unknown error'));
            icon.style.animation = '';
            btn.disabled = false;
        }
    })
    .catch(() => {
        alert('Sync request failed. Check network.');
        icon.style.animation = '';
        btn.disabled = false;
    });
}

// ── Categories ────────────────────────────────────────────────────────────────
function openCatModal()  { document.getElementById('cat-modal').style.display = 'flex'; }
function closeCatModal() { document.getElementById('cat-modal').style.display = 'none'; }
document.getElementById('cat-modal').addEventListener('click', function(e) {
    if (e.target === this) closeCatModal();
});

function addCategory(e, form) {
    e.preventDefault();
    const name = form.name.value.trim();
    if (!name) return;
    fetch('{{ route("stock-watchlist.categories.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ name }),
    })
    .then(r => r.json())
    .then(cat => {
        form.name.value = '';
        document.getElementById('no-cats-msg')?.remove();
        const row = document.createElement('div');
        row.id = `cat-row-${cat.id}`;
        row.style.cssText = 'display:flex;align-items:center;gap:8px;';
        row.innerHTML = `
            <input type="text" value="${escHtml(cat.name)}" data-cat-id="${cat.id}"
                style="flex:1;border:1px solid #e2e8f0;border-radius:7px;padding:7px 10px;font-size:0.875rem;color:#334155;"
                onblur="renameCategory(this)" onkeydown="if(event.key==='Enter')this.blur()">
            <button onclick="deleteCategory(${cat.id}, this)" class="btn-del" style="color:#e2e8f0;padding:4px 6px;">
                <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
                </svg>
            </button>`;
        document.getElementById('cat-list').appendChild(row);
        location.reload(); // reload to show new category section in table
    })
    .catch(() => alert('Failed to add category'));
}

function renameCategory(input) {
    const id   = input.dataset.catId;
    const name = input.value.trim();
    if (!name) { input.value = input.defaultValue; return; }
    fetch(`/stock-watchlist/categories/${id}`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ name }),
    })
    .then(r => { if (r.ok) input.defaultValue = name; else alert('Failed to rename'); });
}

function deleteCategory(id, btn) {
    const itemCount = document.querySelectorAll(`[data-item-id]`).length;
    const catRow    = document.querySelector(`[data-cat-id="${id}"]`);
    if (!confirm(`Delete this category? All its products will be removed from the watchlist.`)) return;
    fetch(`/stock-watchlist/categories/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); else alert('Delete failed'); });
}

// ── Items ─────────────────────────────────────────────────────────────────────
function addItem(e, catId, form) {
    e.preventDefault();
    const code     = form.product_code.value.trim().toUpperCase();
    const leadTime = form.lead_time_months.value;
    if (!code) return;

    fetch(`/stock-watchlist/categories/${catId}/items`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ product_code: code, lead_time_months: leadTime }),
    })
    .then(r => r.json())
    .then(d => {
        if (d.error) { alert(d.error); return; }
        form.product_code.value = '';
        location.reload();
    })
    .catch(() => alert('Failed to add product'));
}

function saveField(itemId, field, value) {
    fetch(`/stock-watchlist/items/${itemId}`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ [field]: value }),
    })
    .then(r => { if (!r.ok) console.warn(`Failed to save ${field} on item ${itemId}`); });
}

function deleteItem(itemId, btn) {
    if (!confirm('Remove this product from the watchlist?')) return;
    fetch(`/stock-watchlist/items/${itemId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            const row = btn.closest('tr');
            row.style.opacity = '0';
            row.style.transition = 'opacity 0.2s';
            setTimeout(() => { row.remove(); }, 200);
        } else {
            alert('Failed to delete');
        }
    });
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

</x-layout>
