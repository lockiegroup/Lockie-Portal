<x-layout title="Racking — Lockie Portal">
<style>
.slot-card {
    flex:1;min-width:0;border-radius:9px;padding:10px 12px;cursor:pointer;transition:box-shadow .15s,transform .15s;border:1.5px solid #e2e8f0;background:#fff;text-align:left;font-family:inherit;
}
.slot-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.1); transform:translateY(-1px); }
.slot-empty {
    flex:1;min-width:0;border-radius:9px;padding:10px 12px;cursor:pointer;transition:box-shadow .15s;border:1.5px dashed #cbd5e1;background:#f8fafc;text-align:left;font-family:inherit;
}
.slot-empty:hover { border-color:#94a3b8;background:#f1f5f9; }
.div-lc { background:#dcfce7;color:#166534; }
.div-jw { background:#dbeafe;color:#1e40af; }
.div-hh { background:#f3e8ff;color:#6b21a8; }
.div-xx { background:#f1f5f9;color:#475569; }
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
function divClass($d) {
    $d = strtolower($d ?? '');
    if (str_contains($d, 'lockie'))  return 'div-lc';
    if (str_contains($d, 'jw'))      return 'div-jw';
    if (str_contains($d, 'hammond')) return 'div-hh';
    return 'div-xx';
}
@endphp

{{-- Bay Grid --}}
<div style="display:flex;flex-direction:column;gap:.5rem;">

@php $lastLetter = ''; @endphp
@foreach($grid as $bay => $baySlots)
@php
    $letter = substr($bay, 0, 1);
    if ($letter !== $lastLetter && $lastLetter !== '') {
        echo '<div style="height:.25rem;"></div>';
    }
    $lastLetter = $letter;
@endphp

<div style="display:flex;align-items:stretch;gap:.5rem;">
    {{-- Bay label --}}
    <div style="width:42px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#1e293b;color:#fff;border-radius:8px;font-weight:700;font-size:.9375rem;letter-spacing:.03em;">
        {{ $bay }}
    </div>

    {{-- Slots --}}
    @for($s = 1; $s <= $slots; $s++)
    @php $item = $baySlots[$s] ?? null; @endphp

    @if($item)
    <button type="button"
        class="slot-card {{ $item->is_unusable ? 'slot-unusable' : '' }}"
        style="{{ $item->is_unusable ? 'opacity:.6;border-color:#fca5a5;' : '' }}{{ $item->for_outside_storage ? 'border-color:#fde68a;' : '' }}"
        onclick="openSlot({{ $item->id }},{{ json_encode(['bay'=>$item->bay,'slot_number'=>$item->slot_number,'division'=>$item->division,'description'=>$item->description,'pallet_ref'=>$item->pallet_ref,'quantity'=>$item->quantity,'date_stored'=>$item->date_stored?->format('Y-m-d'),'is_unusable'=>$item->is_unusable,'for_outside_storage'=>$item->for_outside_storage,'notes'=>$item->notes]) }})">

        @if($item->division)
        <div class="{{ divClass($item->division) }}" style="border-radius:4px;padding:1px 7px;font-size:.65rem;font-weight:700;display:inline-block;margin-bottom:5px;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $item->division }}</div>
        @endif

        <div style="font-size:.75rem;font-weight:600;color:#1e293b;line-height:1.35;margin-bottom:3px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $item->description }}</div>

        @if($item->quantity)
        <div style="font-size:.7rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $item->quantity }}</div>
        @endif

        @if($item->pallet_ref)
        <div style="font-size:.65rem;color:#94a3b8;margin-top:2px;">{{ $item->pallet_ref }}</div>
        @endif

        @if($item->date_stored)
        <div style="font-size:.65rem;color:#94a3b8;margin-top:2px;">{{ $item->date_stored->format('d/m/Y') }}</div>
        @endif

        @if($item->is_unusable)
        <div style="font-size:.65rem;color:#dc2626;font-weight:700;margin-top:3px;">UNUSABLE</div>
        @elseif($item->for_outside_storage)
        <div style="font-size:.65rem;color:#ca8a04;font-weight:700;margin-top:3px;">FOR OUTSIDE</div>
        @endif
    </button>

    @else
    <button type="button"
        class="slot-empty"
        onclick="openSlot(null,{bay:'{{ $bay }}',slot_number:{{ $s }}})">
        <div style="font-size:.7rem;color:#cbd5e1;font-weight:500;">Slot {{ $s }}</div>
        <div style="font-size:.8rem;color:#94a3b8;margin-top:4px;">+ Add</div>
    </button>
    @endif

    @endfor
</div>
@endforeach

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
        </form>

        {{-- Hidden clear form --}}
        <form id="clear-form" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="_method" value="DELETE">
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
