<x-layout title="Racking — Lockie Portal">
<style>
/* ── Rack grid ── */
.rack-row {
    display:grid;
    grid-template-columns: 64px 28px repeat(8, minmax(0,1fr));
    gap:2px;
    margin-bottom:2px;
}
.rack-bay-label {
    background:#1e293b;color:#fff;border-radius:6px;
    font-weight:700;font-size:.8rem;letter-spacing:.02em;
    display:flex;align-items:center;justify-content:center;
    padding:.4rem .2rem;text-align:center;
}
.rack-level-label {
    background:#334155;color:#e2e8f0;border-radius:5px;
    font-weight:700;font-size:.6rem;letter-spacing:.05em;
    display:flex;align-items:center;justify-content:center;
    padding:.2rem 0;text-align:center;
    writing-mode:vertical-rl;transform:rotate(180deg);
}
.rack-p-label {
    background:#f1f5f9;color:#94a3b8;border-radius:5px;
    font-weight:700;font-size:.65rem;
    display:flex;align-items:center;justify-content:center;
}
.rack-floor-label {
    background:#0f172a;color:#94a3b8;border-radius:6px;
    font-weight:800;font-size:.6rem;letter-spacing:.08em;
    display:flex;align-items:center;justify-content:center;
    padding:.4rem .2rem;text-align:center;
}
.rack-floor-bay {
    background:#0f172a;color:#94a3b8;border-radius:6px;
    font-weight:700;font-size:.75rem;
    display:flex;align-items:center;justify-content:center;
    padding:.4rem;
}

/* ── Slot cards ── */
.slot-card {
    width:100%;border-radius:6px;padding:6px 7px;cursor:pointer;
    transition:box-shadow .12s,transform .12s;
    border:1.5px solid #e2e8f0;background:#fff;
    text-align:left;font-family:inherit;min-height:52px;
    box-sizing:border-box;
}
.slot-card:hover { box-shadow:0 3px 12px rgba(0,0,0,.12); transform:translateY(-1px); }
.slot-card.unusable { opacity:.6;border-color:#fca5a5!important; }
.slot-card.for-outside { border-color:#fde68a!important; }
.slot-empty {
    width:100%;border-radius:6px;padding:6px 7px;cursor:pointer;
    transition:box-shadow .12s;border:1.5px dashed #e2e8f0;
    background:#f8fafc;text-align:center;font-family:inherit;min-height:52px;
    box-sizing:border-box;display:flex;align-items:center;justify-content:center;
    color:#cbd5e1;font-size:.7rem;font-weight:500;
}
.slot-empty:hover { border-color:#94a3b8;background:#f1f5f9;color:#94a3b8; }

/* ── Division colours ── */
.slot-lc { background:#dcfce7;border-color:#86efac!important; }
.slot-jw { background:#dbeafe;border-color:#93c5fd!important; }
.slot-hh { background:#f3e8ff;border-color:#d8b4fe!important; }
.div-badge-lc { background:#bbf7d0;color:#166534; }
.div-badge-jw { background:#bfdbfe;color:#1e40af; }
.div-badge-hh { background:#e9d5ff;color:#6b21a8; }
.div-badge-xx { background:#e2e8f0;color:#475569; }
</style>

<main style="max-width:1400px;margin:0 auto;padding:1.5rem;">

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;">
    <div>
        <h1 style="font-size:1.4rem;font-weight:700;color:#1e293b;margin:0 0 .2rem;">Pallet Racking</h1>
        <p style="color:#64748b;font-size:0.8125rem;margin:0;">Click any slot to edit or fill it. Dashed = empty.</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <a href="{{ route('racking.outside') }}" style="padding:.45rem .875rem;background:#fff;border:1px solid #e2e8f0;border-radius:7px;font-size:.8125rem;color:#374151;text-decoration:none;font-weight:600;">
            Outside Storage <span style="background:#e2e8f0;border-radius:8px;padding:1px 6px;font-size:.7rem;margin-left:3px;">{{ $outsideCount }}</span>
        </a>
        <a href="{{ route('racking.movements') }}" style="padding:.45rem .875rem;background:#fff;border:1px solid #e2e8f0;border-radius:7px;font-size:.8125rem;color:#374151;text-decoration:none;font-weight:600;">
            Movements
        </a>
        <button onclick="document.getElementById('import-modal').style.display='flex'"
            style="padding:.45rem .875rem;background:#fff;border:1px solid #e2e8f0;border-radius:7px;font-size:.8125rem;color:#374151;font-weight:600;cursor:pointer;">
            Import XLSX
        </button>
    </div>
</div>

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;color:#166534;font-size:.875rem;">{{ session('success') }}</div>
@endif

{{-- Stats --}}
<div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:1.5rem;">
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:.875rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
        <div style="font-size:1.75rem;font-weight:700;color:#1e293b;">{{ $filledCount }}</div>
        <div style="font-size:.75rem;color:#64748b;font-weight:600;line-height:1.3;">Filled<br>Spaces</div>
    </div>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:.875rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
        <div style="font-size:1.75rem;font-weight:700;color:#16a34a;">{{ $emptyCount }}</div>
        <div style="font-size:.75rem;color:#166534;font-weight:600;line-height:1.3;">Empty<br>Spaces</div>
    </div>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:.875rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
        <div style="font-size:1.75rem;font-weight:700;color:#dc2626;">{{ $unusableCount }}</div>
        <div style="font-size:.75rem;color:#991b1b;font-weight:600;line-height:1.3;">Unusable<br>Spaces</div>
    </div>
    <div style="background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:.875rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
        <div style="font-size:1.75rem;font-weight:700;color:#ca8a04;">{{ $forOutsideCount }}</div>
        <div style="font-size:.75rem;color:#854d0e;font-weight:600;line-height:1.3;">For Outside<br>Storage</div>
    </div>
    {{-- Division legend --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:.875rem 1.25rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
        <span style="font-size:.75rem;color:#64748b;font-weight:600;">Key:</span>
        <span class="div-lc" style="border-radius:5px;padding:3px 10px;font-size:.75rem;font-weight:600;">Lockie Church</span>
        <span class="div-jw" style="border-radius:5px;padding:3px 10px;font-size:.75rem;font-weight:600;">JW Products</span>
        <span class="div-hh" style="border-radius:5px;padding:3px 10px;font-size:.75rem;font-weight:600;">Hammond &amp; Harper</span>
    </div>
</div>

@php
$letters = ['A','B','C','D','E','F','G','H'];
$levels  = [3, 2, 1];

function slotCardClass($item) {
    if (!$item || !$item->division) return '';
    $d = strtolower($item->division);
    if (str_contains($d, 'lockie'))  return 'slot-lc';
    if (str_contains($d, 'jw'))      return 'slot-jw';
    if (str_contains($d, 'hammond')) return 'slot-hh';
    return '';
}
function divBadgeClass($d) {
    $d = strtolower($d ?? '');
    if (str_contains($d, 'lockie'))  return 'div-badge-lc';
    if (str_contains($d, 'jw'))      return 'div-badge-jw';
    if (str_contains($d, 'hammond')) return 'div-badge-hh';
    return 'div-badge-xx';
}
@endphp

{{-- Racking Grid: front-elevation view — Level 3 top, Level 1 bottom, A–H columns --}}
<div style="overflow-x:auto;padding-bottom:.5rem;">
<div style="min-width:780px;">

@foreach($levels as $lvl)

{{-- Level header row: bay column labels --}}
<div class="rack-row">
    <div class="rack-bay-label" style="font-size:.68rem;writing-mode:initial;transform:none;">Level {{ $lvl }}</div>
    <div></div>
    @foreach($letters as $letter)
    <div class="rack-bay-label">{{ $letter.$lvl }}</div>
    @endforeach
</div>

{{-- P1–P4 rows --}}
@for($p = 1; $p <= $slots; $p++)
<div class="rack-row">
    <div class="rack-level-label">Level {{ $lvl }}</div>
    <div class="rack-p-label">P{{ $p }}</div>

    @foreach($letters as $letter)
    @php
        $bay  = $letter . $lvl;
        $item = $grid[$bay][$p] ?? null;
        $iData = $item ? ['bay'=>$item->bay,'slot_number'=>$item->slot_number,'division'=>$item->division,'description'=>$item->description,'pallet_ref'=>$item->pallet_ref,'quantity'=>$item->quantity,'date_stored'=>$item->date_stored?->format('Y-m-d'),'is_unusable'=>$item->is_unusable,'for_outside_storage'=>$item->for_outside_storage,'notes'=>$item->notes] : null;
    @endphp

    @if($item)
    <button type="button"
        class="slot-card {{ slotCardClass($item) }}{{ $item->is_unusable ? ' unusable' : '' }}{{ $item->for_outside_storage ? ' for-outside' : '' }}"
        onclick="openSlot({{ $item->id }},{{ json_encode($iData) }})">

        @if($item->is_unusable)
            <div style="font-size:.6rem;color:#dc2626;font-weight:700;margin-bottom:2px;">UNUSABLE</div>
        @elseif($item->for_outside_storage)
            <div style="font-size:.6rem;color:#ca8a04;font-weight:700;margin-bottom:2px;">FOR OUTSIDE</div>
        @elseif($item->division)
            <div class="{{ divBadgeClass($item->division) }}" style="border-radius:3px;padding:1px 5px;font-size:.58rem;font-weight:700;display:inline-block;margin-bottom:3px;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $item->division }}</div>
        @endif

        <div style="font-size:.72rem;font-weight:600;color:#1e293b;line-height:1.3;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $item->description }}</div>

        @if($item->quantity)
        <div style="font-size:.63rem;color:#64748b;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $item->quantity }}</div>
        @endif

        @if($item->date_stored)
        <div style="font-size:.58rem;color:#94a3b8;margin-top:1px;">{{ $item->date_stored->format('d/m/y') }}</div>
        @endif
    </button>

    @else
    <button type="button" class="slot-empty"
        onclick="openSlot(null,{bay:'{{ $bay }}',slot_number:{{ $p }}})">
        + Add
    </button>
    @endif
    @endforeach

</div>
@endfor

@if($lvl > 1)
<div style="height:8px;"></div>
@endif

@endforeach

{{-- Floor label row --}}
<div class="rack-row" style="margin-top:4px;">
    <div class="rack-floor-label">FLOOR</div>
    <div style="background:#0f172a;border-radius:5px;"></div>
    @foreach($letters as $letter)
    <div class="rack-floor-bay">{{ $letter }}</div>
    @endforeach
</div>

</div>
</div>

{{-- Slot Edit/Add Modal --}}
<div id="slot-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:460px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);">

        <div style="padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <h2 id="modal-title" style="font-size:.9375rem;font-weight:700;color:#1e293b;margin:0;"></h2>
            <button onclick="closeModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#94a3b8;line-height:1;">✕</button>
        </div>

        <form id="slot-form" method="POST" style="padding:1.25rem;">
            @csrf
            <input type="hidden" id="slot-method" name="_method" value="PUT">

            @php
                $fStyle = 'width:100%;border:1px solid #e2e8f0;border-radius:7px;padding:.45rem .7rem;font-size:.875rem;box-sizing:border-box;margin-bottom:.875rem;color:#1e293b;';
                $lStyle = 'display:block;font-size:.75rem;font-weight:600;color:#374151;margin-bottom:.25rem;';
            @endphp

            <label style="{{ $lStyle }}">Division</label>
            <input type="text" name="division" id="s-division" list="div-list" style="{{ $fStyle }}" placeholder="Lockie Church, JW Products…">
            <datalist id="div-list">
                @foreach($divisions as $d)<option value="{{ $d }}">@endforeach
                <option value="Lockie Church">
                <option value="JW Products">
                <option value="Hammond &amp; Harper">
            </datalist>

            <label style="{{ $lStyle }}">Description</label>
            <input type="text" name="description" id="s-desc" style="{{ $fStyle }}" placeholder="e.g. Yellow Booklets 4 perf">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 .875rem;">
                <div>
                    <label style="{{ $lStyle }}">Quantity</label>
                    <input type="text" name="quantity" id="s-qty" style="{{ $fStyle }}" placeholder="150,000 / Picking Pallet">
                </div>
                <div>
                    <label style="{{ $lStyle }}">Pallet Ref</label>
                    <input type="text" name="pallet_ref" id="s-ref" style="{{ $fStyle }}" placeholder="Pallet 504">
                </div>
            </div>

            <label style="{{ $lStyle }}">Date Stored</label>
            <input type="date" name="date_stored" id="s-date" style="{{ $fStyle }}">

            <div style="display:flex;gap:1.25rem;margin-bottom:.875rem;">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8125rem;cursor:pointer;color:#374151;">
                    <input type="checkbox" name="is_unusable" id="s-unusable" value="1"> Unusable
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8125rem;cursor:pointer;color:#374151;">
                    <input type="checkbox" name="for_outside_storage" id="s-outside" value="1"> For Outside Storage
                </label>
            </div>

            <label style="{{ $lStyle }}">Notes</label>
            <textarea name="notes" id="s-notes" rows="2" style="{{ $fStyle }}resize:vertical;margin-bottom:1rem;"></textarea>

            <div style="display:flex;align-items:center;gap:.5rem;">
                <button type="submit"
                    style="flex:1;padding:.6rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:700;cursor:pointer;">
                    Save
                </button>
                <button type="button" id="clear-btn"
                    onclick="clearSlot()"
                    style="padding:.6rem 1rem;background:#fff;color:#dc2626;border:1px solid #fecaca;border-radius:8px;font-size:.8125rem;font-weight:600;cursor:pointer;">
                    Clear Slot
                </button>
                <button type="button" onclick="closeModal()"
                    style="padding:.6rem 1rem;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:8px;font-size:.8125rem;cursor:pointer;">
                    Cancel
                </button>
            </div>

            {{-- Move to Outside Storage --}}
            <div id="move-outside-wrap" style="display:none;margin-top:.75rem;padding-top:.75rem;border-top:1px solid #f1f5f9;">
                <button type="button" id="move-outside-btn"
                    onclick="moveToOutside()"
                    style="width:100%;padding:.55rem;background:#fefce8;color:#854d0e;border:1px solid #fde68a;border-radius:8px;font-size:.8125rem;font-weight:600;cursor:pointer;">
                    Send to Outside Storage
                </button>
            </div>
        </form>

        {{-- Hidden clear form --}}
        <form id="clear-form" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="_method" value="DELETE">
        </form>

        {{-- Hidden move-to-outside form --}}
        <form id="move-outside-form" method="POST" style="display:none;">
            @csrf
        </form>
    </div>
</div>

{{-- Import Modal --}}
<div id="import-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <h2 style="font-size:.9375rem;font-weight:700;color:#1e293b;margin:0;">Import Spreadsheet</h2>
            <button onclick="document.getElementById('import-modal').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#94a3b8;">✕</button>
        </div>
        <form action="{{ route('racking.import') }}" method="POST" enctype="multipart/form-data" style="padding:1.25rem;">
            @csrf
            <p style="font-size:.875rem;color:#64748b;margin:0 0 1rem;">Upload the Pallet_Storage_Racking.xlsx file. Rows from the Main Racking and Outside Storage sheets will be added (existing records are not deleted).</p>
            <input type="file" name="file" accept=".xlsx,.xls" required
                style="width:100%;border:1.5px dashed #cbd5e1;border-radius:8px;padding:.875rem;font-size:.875rem;box-sizing:border-box;cursor:pointer;margin-bottom:1rem;">
            <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                <button type="button" onclick="document.getElementById('import-modal').style.display='none'"
                    style="padding:.5rem 1rem;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:7px;font-size:.875rem;cursor:pointer;">Cancel</button>
                <button type="submit"
                    style="padding:.5rem 1.25rem;background:#0f172a;color:#fff;border:none;border-radius:7px;font-size:.875rem;font-weight:700;cursor:pointer;">Import</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentItemId = null;

function openSlot(id, data) {
    currentItemId = id;
    const isNew = id === null;

    document.getElementById('modal-title').textContent =
        isNew ? ('Bay ' + data.bay + ' · Slot ' + data.slot_number + ' — Add Item')
               : ('Bay ' + data.bay + ' · Slot ' + data.slot_number + ' — Edit');

    // Form action
    const form = document.getElementById('slot-form');
    if (isNew) {
        form.action = '{{ route('racking.store') }}';
        document.getElementById('slot-method').value = 'POST';
        // Inject bay + slot as hidden fields (only needed for store)
        setOrCreate(form, 'bay', data.bay);
        setOrCreate(form, 'slot_number', data.slot_number);
    } else {
        form.action = '/racking/' + id;
        document.getElementById('slot-method').value = 'PUT';
        removeField(form, 'bay');
        removeField(form, 'slot_number');
    }

    document.getElementById('s-division').value = data.division || '';
    document.getElementById('s-desc').value      = data.description || '';
    document.getElementById('s-qty').value       = data.quantity || '';
    document.getElementById('s-ref').value       = data.pallet_ref || '';
    document.getElementById('s-date').value      = data.date_stored || '';
    document.getElementById('s-unusable').checked = !!data.is_unusable;
    document.getElementById('s-outside').checked  = !!data.for_outside_storage;
    document.getElementById('s-notes').value      = data.notes || '';

    document.getElementById('clear-btn').style.display = isNew ? 'none' : '';
    document.getElementById('move-outside-wrap').style.display = isNew ? 'none' : '';
    document.getElementById('slot-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('s-division').focus(), 80);
}

function clearSlot() {
    if (!currentItemId) return;
    if (!confirm('Clear this slot?')) return;
    const f = document.getElementById('clear-form');
    f.action = '/racking/' + currentItemId;
    f.submit();
}

function moveToOutside() {
    if (!currentItemId) return;
    if (!confirm('Move this item to Outside Storage? The slot will be cleared and the move logged.')) return;
    const f = document.getElementById('move-outside-form');
    f.action = '/racking/' + currentItemId + '/move-outside';
    f.submit();
}

function closeModal() {
    document.getElementById('slot-modal').style.display = 'none';
}

function setOrCreate(form, name, value) {
    let el = form.querySelector('[name="' + name + '"][data-dyn]');
    if (!el) {
        el = document.createElement('input');
        el.type = 'hidden'; el.name = name; el.dataset.dyn = '1';
        form.appendChild(el);
    }
    el.value = value;
}

function removeField(form, name) {
    const el = form.querySelector('[name="' + name + '"][data-dyn]');
    if (el) el.remove();
}

// Close on backdrop click
document.getElementById('slot-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('import-modal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
</main>
</x-layout>
