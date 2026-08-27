<x-layout title="Racking — Lockie Portal">
<main style="max-width:1300px;margin:0 auto;padding:2rem 1.5rem;">

@php
    $divisionColor = fn($d) => match(true) {
        str_contains(strtolower($d ?? ''), 'lockie')   => ['#dcfce7','#166534'],
        str_contains(strtolower($d ?? ''), 'jw')       => ['#dbeafe','#1e40af'],
        str_contains(strtolower($d ?? ''), 'hammond')  => ['#f3e8ff','#6b21a8'],
        default                                         => ['#f1f5f9','#475569'],
    };
@endphp

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Pallet Racking</h1>
        <p style="color:#64748b;font-size:0.875rem;margin:0;">Main warehouse racking — bays A1 to H3</p>
    </div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <a href="{{ route('racking.outside') }}"
           style="padding:0.5rem 1rem;background:#fff;border:1px solid #e2e8f0;border-radius:8px;font-size:0.8125rem;color:#374151;text-decoration:none;font-weight:600;">
            Outside Storage <span style="background:#e2e8f0;border-radius:10px;padding:1px 7px;font-size:0.75rem;margin-left:4px;">{{ $outsideStorageCount }}</span>
        </a>
        <a href="{{ route('racking.movements') }}"
           style="padding:0.5rem 1rem;background:#fff;border:1px solid #e2e8f0;border-radius:8px;font-size:0.8125rem;color:#374151;text-decoration:none;font-weight:600;">
            Stock Movements
        </a>
        <button onclick="document.getElementById('import-modal').style.display='flex'"
            style="padding:0.5rem 1rem;background:#fff;border:1px solid #e2e8f0;border-radius:8px;font-size:0.8125rem;color:#374151;font-weight:600;cursor:pointer;">
            Import XLSX
        </button>
        <button onclick="document.getElementById('add-modal').style.display='flex'"
            style="padding:0.5rem 1rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:0.8125rem;font-weight:700;cursor:pointer;">
            + Add Item
        </button>
    </div>
</div>

{{-- Flash --}}
@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:0.75rem 1rem;margin-bottom:1rem;color:#166534;font-size:0.875rem;">{{ session('success') }}</div>
@endif

{{-- Summary Cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem 1.5rem;">
        <div style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Total Items</div>
        <div style="font-size:2rem;font-weight:700;color:#1e293b;">{{ $items->flatten()->count() }}</div>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem 1.5rem;">
        <div style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Available Spaces</div>
        <div style="display:flex;align-items:center;gap:.75rem;">
            <span style="font-size:2rem;font-weight:700;color:#16a34a;" id="avail-display">{{ $availableSpaces }}</span>
            <button onclick="document.getElementById('avail-edit').style.display='flex';document.getElementById('avail-display').parentElement.style.display='none'"
                style="font-size:0.75rem;color:#64748b;background:none;border:1px solid #e2e8f0;border-radius:5px;padding:2px 8px;cursor:pointer;">Edit</button>
        </div>
        <form id="avail-edit" action="{{ route('racking.settings') }}" method="POST" style="display:none;align-items:center;gap:.5rem;margin-top:.25rem;">
            @csrf
            <input type="number" name="available_spaces" value="{{ $availableSpaces }}" min="0"
                style="width:70px;border:1px solid #cbd5e1;border-radius:6px;padding:4px 8px;font-size:0.875rem;">
            <button type="submit" style="background:#0f172a;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:0.8125rem;cursor:pointer;">Save</button>
        </form>
    </div>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:1.25rem 1.5rem;">
        <div style="font-size:0.75rem;font-weight:600;color:#991b1b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Unusable Spaces</div>
        <div style="font-size:2rem;font-weight:700;color:#dc2626;">{{ $unusableCount }}</div>
    </div>
    <div style="background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:1.25rem 1.5rem;">
        <div style="font-size:0.75rem;font-weight:600;color:#92400e;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">For Outside Storage</div>
        <div style="font-size:2rem;font-weight:700;color:#d97706;">{{ $forOutsideCount }}</div>
    </div>
    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:1.25rem 1.5rem;">
        <div style="font-size:0.75rem;font-weight:600;color:#075985;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Outside Storage</div>
        <div style="font-size:2rem;font-weight:700;color:#0284c7;">{{ $outsideStorageCount }}</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-bottom:1.25rem;align-items:center;">
    <select name="bay" onchange="this.form.submit()"
        style="border:1px solid #e2e8f0;border-radius:7px;padding:0.45rem 0.75rem;font-size:0.8125rem;color:#374151;background:#fff;">
        <option value="">All Bays</option>
        @foreach($bays as $b)
            <option value="{{ $b }}" {{ request('bay')===$b ? 'selected' : '' }}>Bay {{ $b }}</option>
        @endforeach
    </select>
    <select name="division" onchange="this.form.submit()"
        style="border:1px solid #e2e8f0;border-radius:7px;padding:0.45rem 0.75rem;font-size:0.8125rem;color:#374151;background:#fff;">
        <option value="">All Divisions</option>
        @foreach($divisions as $d)
            <option value="{{ $d }}" {{ request('division')===$d ? 'selected' : '' }}>{{ $d }}</option>
        @endforeach
    </select>
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search description…"
        style="border:1px solid #e2e8f0;border-radius:7px;padding:0.45rem 0.75rem;font-size:0.8125rem;width:220px;color:#374151;">
    <button type="submit" style="padding:0.45rem 1rem;background:#1e293b;color:#fff;border:none;border-radius:7px;font-size:0.8125rem;cursor:pointer;">Search</button>
    @if(request()->hasAny(['bay','division','q']))
    <a href="{{ route('racking.index') }}" style="font-size:0.8125rem;color:#64748b;text-decoration:none;">Clear</a>
    @endif
</form>

{{-- Racking Table --}}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
        <thead>
            <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <th style="padding:0.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:65px;">Bay</th>
                <th style="padding:0.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:140px;">Division</th>
                <th style="padding:0.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Description</th>
                <th style="padding:0.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:120px;">Pallet Ref</th>
                <th style="padding:0.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:120px;">Quantity</th>
                <th style="padding:0.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:110px;">Date Stored</th>
                <th style="padding:0.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:90px;">Flags</th>
                <th style="padding:0.7rem;width:80px;"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $bay => $bayItems)
            <tr style="background:#f1f5f9;">
                <td colspan="8" style="padding:0.4rem 1rem;font-weight:700;font-size:0.8125rem;color:#1e293b;letter-spacing:.04em;">
                    Bay {{ $bay }}
                </td>
            </tr>
            @foreach($bayItems as $item)
            @php [$bg, $txt] = $divisionColor($item->division); @endphp
            <tr style="border-bottom:1px solid #f1f5f9;{{ $item->is_unusable ? 'opacity:.55;' : '' }}" class="rack-row">
                <td style="padding:0.65rem 1rem;color:#64748b;">{{ $item->bay }}</td>
                <td style="padding:0.65rem 1rem;">
                    @if($item->division)
                    <span style="background:{{ $bg }};color:{{ $txt }};border-radius:5px;padding:2px 8px;font-size:0.75rem;font-weight:600;white-space:nowrap;">{{ $item->division }}</span>
                    @endif
                </td>
                <td style="padding:0.65rem 1rem;color:#1e293b;font-weight:{{ $item->is_unusable ? '400' : '500' }};">
                    {{ $item->description }}
                    @if($item->notes)<div style="font-size:0.75rem;color:#94a3b8;margin-top:2px;">{{ $item->notes }}</div>@endif
                </td>
                <td style="padding:0.65rem 1rem;color:#64748b;font-size:0.75rem;">{{ $item->pallet_ref }}</td>
                <td style="padding:0.65rem 1rem;color:#374151;">{{ $item->quantity }}</td>
                <td style="padding:0.65rem 1rem;color:#64748b;font-size:0.75rem;">{{ $item->date_stored?->format('d/m/Y') }}</td>
                <td style="padding:0.65rem 1rem;">
                    @if($item->is_unusable)<span style="font-size:0.7rem;background:#fee2e2;color:#991b1b;border-radius:4px;padding:1px 6px;">Unusable</span>@endif
                    @if($item->for_outside_storage)<span style="font-size:0.7rem;background:#fef9c3;color:#854d0e;border-radius:4px;padding:1px 6px;margin-left:2px;">Outside</span>@endif
                </td>
                <td style="padding:0.5rem 0.75rem;text-align:right;white-space:nowrap;">
                    <button onclick="openEdit({{ $item->id }},{{ json_encode($item) }})"
                        style="font-size:0.75rem;color:#475569;background:none;border:1px solid #e2e8f0;border-radius:5px;padding:3px 8px;cursor:pointer;margin-right:3px;">Edit</button>
                    <form action="{{ route('racking.destroy', $item) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove this item?')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="bay" value="{{ request('bay') }}">
                        <input type="hidden" name="division" value="{{ request('division') }}">
                        <input type="hidden" name="q" value="{{ request('q') }}">
                        <button type="submit" style="font-size:0.75rem;color:#dc2626;background:none;border:1px solid #fecaca;border-radius:5px;padding:3px 8px;cursor:pointer;">Del</button>
                    </form>
                </td>
            </tr>
            @endforeach
        @empty
            <tr><td colspan="8" style="padding:3rem;text-align:center;color:#94a3b8;">No items found. Import your spreadsheet or add items manually.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Add Item Modal --}}
<div id="add-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Add Racking Item</h2>
            <button onclick="document.getElementById('add-modal').style.display='none'" style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <form action="{{ route('racking.store') }}" method="POST" style="padding:1.5rem;">
            @csrf
            <input type="hidden" name="bay" id="add-bay-h">
            <input type="hidden" name="division" id="add-div-h">
            <input type="hidden" name="q" id="add-q-h">
            @php
                $fStyle = 'width:100%;border:1px solid #e2e8f0;border-radius:7px;padding:0.5rem 0.75rem;font-size:0.875rem;margin-bottom:1rem;box-sizing:border-box;';
                $lStyle = 'display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;';
            @endphp
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 1rem;">
                <div>
                    <label style="{{ $lStyle }}">Bay *</label>
                    <select name="bay" required style="{{ $fStyle }}">
                        @foreach($bays as $b)
                        <option value="{{ $b }}" {{ request('bay')===$b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="{{ $lStyle }}">Division</label>
                    <input type="text" name="division" list="division-list" style="{{ $fStyle }}" placeholder="Lockie Church…">
                    <datalist id="division-list">
                        @foreach($divisions as $d)<option value="{{ $d }}">@endforeach
                    </datalist>
                </div>
            </div>
            <label style="{{ $lStyle }}">Description</label>
            <input type="text" name="description" style="{{ $fStyle }}" placeholder="e.g. Yellow Booklets 4 perf">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 1rem;">
                <div>
                    <label style="{{ $lStyle }}">Pallet Ref</label>
                    <input type="text" name="pallet_ref" style="{{ $fStyle }}" placeholder="Pallet 504">
                </div>
                <div>
                    <label style="{{ $lStyle }}">Quantity</label>
                    <input type="text" name="quantity" style="{{ $fStyle }}" placeholder="150,000 / Picking Pallet">
                </div>
            </div>
            <label style="{{ $lStyle }}">Date Stored</label>
            <input type="date" name="date_stored" style="{{ $fStyle }}">
            <div style="display:flex;gap:1.5rem;margin-bottom:1rem;">
                <label style="display:flex;align-items:center;gap:.5rem;font-size:0.875rem;cursor:pointer;">
                    <input type="checkbox" name="is_unusable" value="1"> Unusable Space
                </label>
                <label style="display:flex;align-items:center;gap:.5rem;font-size:0.875rem;cursor:pointer;">
                    <input type="checkbox" name="for_outside_storage" value="1"> For Outside Storage
                </label>
            </div>
            <label style="{{ $lStyle }}">Notes</label>
            <textarea name="notes" rows="2" style="{{ $fStyle }}resize:vertical;"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:.5rem;">
                <button type="button" onclick="document.getElementById('add-modal').style.display='none'"
                    style="padding:.6rem 1.25rem;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;cursor:pointer;">Cancel</button>
                <button type="submit"
                    style="padding:.6rem 1.5rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:700;cursor:pointer;">Add Item</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Item Modal --}}
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Edit Racking Item</h2>
            <button onclick="document.getElementById('edit-modal').style.display='none'" style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <form id="edit-form" method="POST" style="padding:1.5rem;">
            @csrf @method('PUT')
            <input type="hidden" name="bay" value="{{ request('bay') }}">
            <input type="hidden" name="division" value="{{ request('division') }}">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 1rem;">
                <div>
                    <label style="{{ $lStyle }}">Bay *</label>
                    <select name="bay" id="edit-bay" required style="{{ $fStyle }}">
                        @foreach($bays as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label style="{{ $lStyle }}">Division</label>
                    <input type="text" name="division" id="edit-division" list="division-list" style="{{ $fStyle }}">
                </div>
            </div>
            <label style="{{ $lStyle }}">Description</label>
            <input type="text" name="description" id="edit-description" style="{{ $fStyle }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 1rem;">
                <div>
                    <label style="{{ $lStyle }}">Pallet Ref</label>
                    <input type="text" name="pallet_ref" id="edit-pallet-ref" style="{{ $fStyle }}">
                </div>
                <div>
                    <label style="{{ $lStyle }}">Quantity</label>
                    <input type="text" name="quantity" id="edit-quantity" style="{{ $fStyle }}">
                </div>
            </div>
            <label style="{{ $lStyle }}">Date Stored</label>
            <input type="date" name="date_stored" id="edit-date-stored" style="{{ $fStyle }}">
            <div style="display:flex;gap:1.5rem;margin-bottom:1rem;">
                <label style="display:flex;align-items:center;gap:.5rem;font-size:0.875rem;cursor:pointer;">
                    <input type="checkbox" name="is_unusable" id="edit-unusable" value="1"> Unusable Space
                </label>
                <label style="display:flex;align-items:center;gap:.5rem;font-size:0.875rem;cursor:pointer;">
                    <input type="checkbox" name="for_outside_storage" id="edit-outside" value="1"> For Outside Storage
                </label>
            </div>
            <label style="{{ $lStyle }}">Notes</label>
            <textarea name="notes" id="edit-notes" rows="2" style="{{ $fStyle }}resize:vertical;"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:.5rem;">
                <button type="button" onclick="document.getElementById('edit-modal').style.display='none'"
                    style="padding:.6rem 1.25rem;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;cursor:pointer;">Cancel</button>
                <button type="submit"
                    style="padding:.6rem 1.5rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:700;cursor:pointer;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Import Modal --}}
<div id="import-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Import Spreadsheet</h2>
            <button onclick="document.getElementById('import-modal').style.display='none'" style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <form action="{{ route('racking.import') }}" method="POST" enctype="multipart/form-data" style="padding:1.5rem;">
            @csrf
            <p style="font-size:0.875rem;color:#64748b;margin:0 0 1rem;">Upload the Pallet_Storage_Racking.xlsx file. This will <strong>add</strong> all rows from the Main Racking and Outside Storage sheets (existing data is not deleted).</p>
            <input type="file" name="file" accept=".xlsx,.xls" required
                style="width:100%;border:1.5px dashed #cbd5e1;border-radius:8px;padding:1rem;font-size:0.875rem;box-sizing:border-box;cursor:pointer;margin-bottom:1rem;">
            <div style="display:flex;justify-content:flex-end;gap:.75rem;">
                <button type="button" onclick="document.getElementById('import-modal').style.display='none'"
                    style="padding:.6rem 1.25rem;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;cursor:pointer;">Cancel</button>
                <button type="submit"
                    style="padding:.6rem 1.5rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:700;cursor:pointer;">Import</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, item) {
    document.getElementById('edit-form').action = '/racking/' + id;
    document.getElementById('edit-bay').value         = item.bay || '';
    document.getElementById('edit-division').value    = item.division || '';
    document.getElementById('edit-description').value = item.description || '';
    document.getElementById('edit-pallet-ref').value  = item.pallet_ref || '';
    document.getElementById('edit-quantity').value    = item.quantity || '';
    document.getElementById('edit-date-stored').value = item.date_stored ? item.date_stored.substring(0,10) : '';
    document.getElementById('edit-unusable').checked  = !!item.is_unusable;
    document.getElementById('edit-outside').checked   = !!item.for_outside_storage;
    document.getElementById('edit-notes').value       = item.notes || '';
    document.getElementById('edit-modal').style.display = 'flex';
}
// Pre-fill filter state into add-modal hidden fields
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('add-bay-h').value = '{{ request('bay') }}';
    document.getElementById('add-div-h').value = '{{ request('division') }}';
    document.getElementById('add-q-h').value   = '{{ request('q') }}';
});
</script>

</main>
</x-layout>
