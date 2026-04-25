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
.sw-req-click { cursor: pointer; text-decoration: underline dotted #b45309; }
.sw-req-click:hover { background: #fef3c7; border-radius: 3px; }
.sw-flash { background: #dcfce7 !important; transition: background 0.6s; }
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
.sw-disc-row td { background: #fecaca; color: #7f1d1d; }
.sw-disc-row:hover td { background: #fca5a5; }
.sw-add-row td { background: #fafafa; }
.btn-del {
    background: none; border: none; cursor: pointer;
    color: #cbd5e1; padding: 2px 4px; border-radius: 4px;
    line-height:0; transition: color 0.15s;
}
.btn-del:hover { color: #ef4444; }
.sw-drag-handle {
    color: #cbd5e1; cursor: grab; padding: 0 6px; text-align: center;
    transition: color 0.15s;
}
.sw-drag-handle:active { cursor: grabbing; }
.sw-drag-handle:hover { color: #94a3b8; }
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
            @if($salesFrom)
            <p style="font-size:0.78rem;color:#94a3b8;margin:4px 0 0;">
                Sales data: {{ $salesFrom }} – {{ $salesTo }}
            </p>
            @endif
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
            <div style="display:flex;align-items:center;gap:4px;">
                <button class="btn-ghost" onclick="document.getElementById('sales-import-input').click()">
                    Import Sales CSV
                </button>
                <input type="text" id="sales-find" placeholder="Find…" title="Find in product code"
                    style="width:70px;border:1px solid #e2e8f0;border-radius:6px;padding:4px 7px;font-size:0.78rem;color:#334155;text-transform:uppercase;"
                    oninput="this.value=this.value.toUpperCase()">
                <span style="font-size:0.75rem;color:#94a3b8;">→</span>
                <input type="text" id="sales-replace" placeholder="Replace…" title="Replace with"
                    style="width:70px;border:1px solid #e2e8f0;border-radius:6px;padding:4px 7px;font-size:0.78rem;color:#334155;text-transform:uppercase;"
                    oninput="this.value=this.value.toUpperCase()">
            </div>
            <input type="file" id="sales-import-input" accept=".csv,.tsv,.txt" style="display:none"
                onchange="importSalesFile(this)">
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
                        <th style="width:24px;"></th>
                        <th style="text-align:left;min-width:110px;">Code</th>
                        <th style="text-align:left;min-width:140px;">Notes</th>
                        @foreach($years as $yr)
                            <th>{{ $yr }}<br><small style="font-weight:400;opacity:0.7;">units</small></th>
                        @endforeach
                        <th>Avg/Mo</th>
                        <th>On Hand</th>
                        <th>Alloc'd</th>
                        <th>On Order</th>
                        <th style="cursor:pointer;user-select:none;" onclick="fillAllRequired()" title="Fill all To Order from Required">Required<br><small style="font-weight:400;opacity:0.7;">click→order</small></th>
                        <th>To Order</th>
                        <th>Price</th>
                        <th id="total-header">Total</th>
                        <th>Status</th>
                        <th>Disc</th>
                    </tr>
                </thead>
                @forelse($categories as $cat)
                    {{-- Category header --}}
                    <tbody id="cat-{{ $cat->id }}">
                        <tr class="sw-cat-row" data-cat-id="{{ $cat->id }}">
                            <td colspan="{{ 13 + count($years) }}">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                                    <div>
                                        {{ $cat->name }}
                                        <span style="font-weight:400;font-size:0.7rem;color:#94a3b8;margin-left:8px;">
                                            ({{ $cat->items->count() }} {{ $cat->items->count() === 1 ? 'product' : 'products' }})
                                        </span>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:14px;">
                                        <label style="display:flex;align-items:center;gap:5px;font-weight:400;font-size:0.72rem;color:#64748b;text-transform:none;letter-spacing:0;cursor:default;">
                                            Lead time:
                                            <input type="number" min="1" max="3650"
                                                value="{{ $cat->lead_time_days ?? 30 }}"
                                                style="width:48px;border:1px solid #cbd5e1;border-radius:4px;padding:1px 4px;font-size:0.75rem;font-weight:600;color:#0f172a;text-transform:none;text-align:center;"
                                                onblur="saveCatField({{ $cat->id }}, 'lead_time_days', this.value)"
                                                onkeydown="if(event.key==='Enter')this.blur()">
                                            days
                                        </label>
                                        <label style="display:flex;align-items:center;gap:5px;font-weight:400;font-size:0.72rem;color:#64748b;text-transform:none;letter-spacing:0;cursor:default;">
                                            Currency:
                                            <select onchange="saveCatCurrency({{ $cat->id }}, this.value)"
                                                style="border:1px solid #cbd5e1;border-radius:4px;padding:1px 4px;font-size:0.75rem;font-weight:600;color:#0f172a;background:white;">
                                                @foreach(['£','$','€','AU$','NZ$'] as $cur)
                                                <option value="{{ $cur }}" {{ ($cat->currency ?? '£') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <a href="{{ route('stock-watchlist.items.download', $cat) }}"
                                            style="font-weight:500;font-size:0.72rem;color:#3b82f6;text-decoration:none;text-transform:none;letter-spacing:0;"
                                            title="Download product list as CSV">↓ Export CSV</a>
                                        <button onclick="document.getElementById('cat-import-{{ $cat->id }}').click()"
                                            style="background:none;border:none;font-weight:500;font-size:0.72rem;color:#3b82f6;cursor:pointer;padding:0;text-transform:none;letter-spacing:0;"
                                            title="Import product codes from CSV">↑ Import CSV</button>
                                        <input type="file" id="cat-import-{{ $cat->id }}" accept=".csv,.txt" style="display:none"
                                            onchange="importCatItems({{ $cat->id }}, this)">
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                    {{-- Sortable item rows --}}
                    <tbody class="sw-sortable" id="sortable-cat-{{ $cat->id }}" data-cat-id="{{ $cat->id }}">
                    @foreach($cat->items as $item)
                    @php
                        $stock     = $item->stock;
                        $onHand    = $stock ? (float)$stock->qty_on_hand : null;
                        $allocated = $stock ? (float)$stock->qty_allocated : 0;
                        $onOrder   = $stock ? (float)$stock->qty_on_order : 0;
                        $avgMo     = $item->avg_monthly;
                        $reqQty    = $item->required_qty;
                        $toOrder   = (float)($item->to_order_qty ?? 0);
                        $price     = (float)($item->unit_price ?? 0);
                        $total     = $toOrder * $price;

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
                    <tr class="{{ $item->discontinued ? 'sw-disc-row' : '' }}" data-id="{{ $item->id }}">
                        <td class="sw-drag-handle">
                            <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/>
                                <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                                <circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/>
                            </svg>
                        </td>
                        <td style="font-weight:600;color:#0f172a;">{{ $item->product_code }}</td>
                        <td>
                            <input type="text" class="info-input"
                                value="{{ $item->info }}"
                                placeholder="Add notes…"
                                onblur="saveField({{ $item->id }}, 'info', this.value)"
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
                        <td class="sw-num {{ $reqQty > 0 ? 'sw-req-click' : '' }}"
                            style="{{ $reqQty > 0 ? 'color:#b45309;font-weight:700;' : 'color:#94a3b8;' }}"
                            data-item-id="{{ $item->id }}" data-req-qty="{{ $reqQty }}"
                            @if($reqQty > 0) onclick="copyRequired(this)" title="Click to copy to To Order" @endif>
                            {{ $reqQty > 0 ? number_format($reqQty, 0) : '—' }}
                        </td>
                        <td>
                            <input type="number" class="sw-input" min="0" step="1"
                                data-field="to_order"
                                value="{{ $toOrder > 0 ? (int)$toOrder : '' }}"
                                placeholder="{{ $reqQty > 0 ? $reqQty : '0' }}"
                                onblur="saveField({{ $item->id }}, 'to_order_qty', this.value); recalcTotal(this.closest('tr'))"
                                onkeydown="if(event.key==='Enter')this.blur()">
                        </td>
                        <td>
                            <input type="number" class="sw-input" min="0" step="0.01"
                                data-field="unit_price"
                                value="{{ $price > 0 ? number_format($price, 2, '.', '') : '' }}"
                                placeholder="0.00"
                                onblur="saveField({{ $item->id }}, 'unit_price', this.value); recalcTotal(this.closest('tr'))"
                                onkeydown="if(event.key==='Enter')this.blur()">
                        </td>
                        <td class="sw-num sw-total" data-amount="{{ $total > 0 ? number_format($total, 2, '.', '') : '' }}" data-currency="{{ $cat->currency ?? '£' }}" style="font-weight:600;"></td>
                        <td><span class="sw-badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
                        <td style="text-align:center;">
                            <input type="checkbox" {{ $item->discontinued ? 'checked' : '' }}
                                onchange="toggleDiscontinued(this, {{ $item->id }})"
                                style="width:14px;height:14px;cursor:pointer;accent-color:#6366f1;">
                        </td>
                    </tr>
                    @endforeach
                    </tbody>

                    {{-- Subtotal row --}}
                    @php
                        $subYearly   = [];
                        foreach ($years as $yr) {
                            $subYearly[$yr] = $cat->items->sum(fn($i) => $i->yearly[$yr] ?? 0);
                        }
                        $subAvg     = $cat->items->sum(fn($i) => $i->avg_monthly);
                        $subOnHand  = $cat->items->sum(fn($i) => $i->stock ? (float)$i->stock->qty_on_hand : 0);
                        $subAllocd  = $cat->items->sum(fn($i) => $i->stock ? (float)$i->stock->qty_allocated : 0);
                        $subOnOrder = $cat->items->sum(fn($i) => $i->stock ? (float)$i->stock->qty_on_order : 0);
                        $subReq     = $cat->items->sum(fn($i) => $i->required_qty);
                        $subToOrder = $cat->items->sum(fn($i) => (float)($i->to_order_qty ?? 0));
                        $subTotal   = $cat->items->sum(fn($i) => (float)($i->to_order_qty ?? 0) * (float)($i->unit_price ?? 0));
                    @endphp
                    <tbody>
                        <tr id="subtotal-cat-{{ $cat->id }}" style="font-weight:700;background:#f1f5f9;border-top:2px solid #e2e8f0;">
                            <td></td>
                            <td style="font-size:0.7rem;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Subtotal</td>
                            <td></td>
                            @foreach($years as $yr)
                            <td class="sw-num">{{ $subYearly[$yr] > 0 ? number_format($subYearly[$yr], 0) : '—' }}</td>
                            @endforeach
                            <td class="sw-num">{{ $subAvg > 0 ? number_format($subAvg, 1) : '—' }}</td>
                            <td class="sw-num">{{ $subOnHand > 0 ? number_format($subOnHand, 0) : '—' }}</td>
                            <td class="sw-num">{{ $subAllocd > 0 ? number_format($subAllocd, 0) : '—' }}</td>
                            <td class="sw-num">{{ $subOnOrder > 0 ? number_format($subOnOrder, 0) : '—' }}</td>
                            <td class="sw-num">{{ $subReq > 0 ? number_format($subReq, 0) : '—' }}</td>
                            <td class="sw-num sw-sub-toorder">{{ $subToOrder > 0 ? number_format($subToOrder, 0) : '—' }}</td>
                            <td></td>
                            <td class="sw-num sw-sub-total" data-currency="{{ $cat->currency ?? '£' }}" data-amount="{{ $subTotal > 0 ? number_format($subTotal, 2, '.', '') : '' }}"></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>

                    {{-- Add product row --}}
                    <tbody>
                        <tr class="sw-add-row">
                            <td colspan="{{ 13 + count($years) }}" style="padding:6px 10px;">
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
                    </tbody>
                @empty
                    <tbody>
                        <tr><td colspan="{{ 13 + count($years) }}" style="padding:2rem;text-align:center;color:#94a3b8;">
                            No categories yet. Click <strong>Manage Categories</strong> to add one.
                        </td></tr>
                    </tbody>
                @endforelse
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

function saveCatField(id, field, value) {
    fetch(`/stock-watchlist/categories/${id}`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ [field]: value }),
    });
}

function importCatItems(catId, input) {
    const file = input.files[0];
    if (!file) return;
    input.value = '';

    const form = new FormData();
    form.append('file', file);
    form.append('_token', csrfToken);

    fetch(`/stock-watchlist/categories/${catId}/items/import`, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: form,
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            alert(`Import complete: ${d.added} added, ${d.updated} updated. Page will reload.`);
            location.reload();
        } else {
            alert('Import failed: ' + (d.error || 'Unknown error'));
        }
    })
    .catch(() => alert('Import request failed.'));
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

// ── Required → To Order ───────────────────────────────────────────────────────
function copyRequired(cell) {
    const itemId = cell.dataset.itemId;
    const reqQty = parseInt(cell.dataset.reqQty, 10);
    if (!itemId || !reqQty) return;
    const row   = cell.closest('tr');
    const input = row.querySelector('input[data-field="to_order"]');
    if (!input) return;
    input.value = reqQty;
    saveField(itemId, 'to_order_qty', reqQty);
    recalcTotal(row);
    input.classList.add('sw-flash');
    setTimeout(() => input.classList.remove('sw-flash'), 800);
}

function fillAllRequired() {
    const cells = [...document.querySelectorAll('.sw-req-click')].filter(c => !c.closest('tr').classList.contains('sw-disc-row'));
    if (!cells.length) return;
    if (!confirm(`Fill To Order for all ${cells.length} product(s) that need ordering? This will overwrite any existing To Order values.`)) return;
    cells.forEach(cell => copyRequired(cell));
}

// ── Recalc total cell ─────────────────────────────────────────────────────────
function recalcTotal(row) {
    const toOrder = parseFloat(row.querySelector('[data-field="to_order"]')?.value) || 0;
    const price   = parseFloat(row.querySelector('[data-field="unit_price"]')?.value) || 0;
    const total   = toOrder * price;
    const cell    = row.querySelector('.sw-total');
    if (!cell) return;
    cell.dataset.amount = total > 0 ? total.toFixed(2) : '';
    renderTotal(cell);
    const catId = row.closest('.sw-sortable')?.dataset.catId;
    if (catId) updateCatSubtotal(catId);
}

// ── Category subtotal row ─────────────────────────────────────────────────────
function updateCatSubtotal(catId) {
    const tbody  = document.getElementById(`sortable-cat-${catId}`);
    const subRow = document.getElementById(`subtotal-cat-${catId}`);
    if (!tbody || !subRow) return;

    let toOrderSum = 0, totalSum = 0;
    tbody.querySelectorAll('tr[data-id]').forEach(row => {
        toOrderSum += parseFloat(row.querySelector('[data-field="to_order"]')?.value) || 0;
        totalSum   += parseFloat(row.querySelector('.sw-total')?.dataset.amount) || 0;
    });

    const subToOrder = subRow.querySelector('.sw-sub-toorder');
    const subTotal   = subRow.querySelector('.sw-sub-total');
    if (subToOrder) subToOrder.textContent = toOrderSum > 0 ? toOrderSum.toLocaleString('en-GB', {maximumFractionDigits:0}) : '—';
    if (subTotal) {
        subTotal.dataset.amount = totalSum > 0 ? totalSum.toFixed(2) : '';
        renderTotal(subTotal);
    }
}

// Initialise all subtotal totals on load
document.querySelectorAll('.sw-sortable').forEach(tbody => updateCatSubtotal(tbody.dataset.catId));

// ── Discontinued toggle ───────────────────────────────────────────────────────
function toggleDiscontinued(checkbox, itemId) {
    const isDisc = checkbox.checked;
    const row    = checkbox.closest('tr');
    row.classList.toggle('sw-disc-row', isDisc);
    const badge = row.querySelector('.sw-badge');
    if (badge) {
        if (isDisc) {
            badge.dataset.savedClass = badge.className;
            badge.dataset.savedText  = badge.textContent.trim();
            badge.className   = 'sw-badge sw-badge-disc';
            badge.textContent = 'Discontinued';
        } else if (badge.dataset.savedClass) {
            badge.className   = badge.dataset.savedClass;
            badge.textContent = badge.dataset.savedText;
        }
    }
    saveField(itemId, 'discontinued', isDisc ? 1 : 0);
}

// ── Currency (per category) ───────────────────────────────────────────────────
function renderTotal(td) {
    const amt = td.dataset.amount;
    const sym = td.dataset.currency || '£';
    td.textContent = amt ? sym + Number(amt).toLocaleString('en-GB', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
}
function saveCatCurrency(catId, sym) {
    saveCatField(catId, 'currency', sym);
    document.querySelectorAll(`#sortable-cat-${catId} .sw-total`).forEach(td => {
        td.dataset.currency = sym;
        renderTotal(td);
    });
    const subTotal = document.querySelector(`#subtotal-cat-${catId} .sw-sub-total`);
    if (subTotal) { subTotal.dataset.currency = sym; renderTotal(subTotal); }
}
document.querySelectorAll('.sw-total').forEach(renderTotal);

// ── Sales CSV Import ──────────────────────────────────────────────────────────
function importSalesFile(input) {
    const file = input.files[0];
    if (!file) return;
    input.value = '';

    const status = document.getElementById('sync-status');
    status.textContent = 'Importing…';

    const form = new FormData();
    form.append('file', file);
    form.append('_token', csrfToken);
    form.append('find',    document.getElementById('sales-find')?.value.trim() || '');
    form.append('replace', document.getElementById('sales-replace')?.value.trim() || '');

    fetch('{{ route("stock-watchlist.import-sales") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: form,
    })
    .then(r => r.json())
    .then(d => {
        console.log('Sales import result:', d);
        if (d.ok) {
            status.textContent = `Imported ${d.months} month-rows for ${d.products} product(s) (${d.rows_processed} rows matched, ${d.rows_skipped} skipped)`;
            setTimeout(() => location.reload(), 1500);
        } else {
            alert('Import failed: ' + (d.error || 'Unknown error'));
            status.textContent = '';
        }
    })
    .catch(() => { alert('Import request failed.'); status.textContent = ''; });
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Reorder ───────────────────────────────────────────────────────────────────
function saveItemOrder(tbody) {
    const ids = [...tbody.querySelectorAll('tr[data-id]')].map(r => r.dataset.id);
    fetch('{{ route("stock-watchlist.items.reorder") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ ids }),
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.querySelectorAll('.sw-sortable').forEach(tbody => {
    Sortable.create(tbody, {
        handle: '.sw-drag-handle',
        animation: 150,
        onEnd() { saveItemOrder(tbody); },
    });
});
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

</x-layout>
