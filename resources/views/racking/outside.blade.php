<x-layout title="Outside Storage — Racking — Lockie Portal">
<main style="max-width:1100px;margin:0 auto;padding:2rem 1.5rem;">

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <a href="{{ route('racking.index') }}"
           style="font-size:0.8125rem;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .75rem;border:1px solid #e2e8f0;border-radius:6px;background:#fff;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Main Racking
        </a>
        <div>
            <h1 style="font-size:1.4rem;font-weight:700;color:#1e293b;margin:0 0 .2rem;">Outside Storage</h1>
            <p style="color:#64748b;font-size:0.875rem;margin:0;">{{ $items->count() }} items currently in outside storage</p>
        </div>
    </div>
    <button onclick="document.getElementById('add-modal').style.display='flex'"
        style="padding:.5rem 1rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:.8125rem;font-weight:700;cursor:pointer;">
        + Add Item
    </button>
</div>

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;color:#166534;font-size:.875rem;">{{ session('success') }}</div>
@endif

{{-- Table --}}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.8125rem;">
        <thead>
            <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Date Stored</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Colour / Type</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Quantity</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Ref</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Year</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Return Date</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Notes</th>
                <th style="padding:.7rem;width:80px;"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:.65rem 1rem;color:#374151;">{{ $item->storage_date?->format('d/m/Y') }}</td>
            <td style="padding:.65rem 1rem;font-weight:500;color:#1e293b;">{{ $item->colour }}</td>
            <td style="padding:.65rem 1rem;color:#374151;">{{ $item->quantity }}</td>
            <td style="padding:.65rem 1rem;color:#64748b;">{{ $item->ref }}</td>
            <td style="padding:.65rem 1rem;color:#64748b;">{{ $item->year }}</td>
            <td style="padding:.65rem 1rem;">
                @if($item->return_date)
                    <span style="background:#dcfce7;color:#166534;border-radius:5px;padding:2px 8px;font-size:.75rem;">{{ $item->return_date->format('d/m/Y') }}</span>
                @else
                    <span style="color:#94a3b8;font-size:.75rem;">—</span>
                @endif
            </td>
            <td style="padding:.65rem 1rem;color:#64748b;font-size:.75rem;">{{ $item->notes }}</td>
            <td style="padding:.5rem .75rem;text-align:right;white-space:nowrap;">
                <button onclick="openEdit({{ $item->id }},{{ json_encode($item) }})"
                    style="font-size:.75rem;color:#475569;background:none;border:1px solid #e2e8f0;border-radius:5px;padding:3px 8px;cursor:pointer;margin-right:3px;">Edit</button>
                <form action="{{ route('racking.outside.destroy', $item) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove this item?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size:.75rem;color:#dc2626;background:none;border:1px solid #fecaca;border-radius:5px;padding:3px 8px;cursor:pointer;">Del</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="padding:3rem;text-align:center;color:#94a3b8;">No items in outside storage.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@php
    $fStyle = 'width:100%;border:1px solid #e2e8f0;border-radius:7px;padding:.5rem .75rem;font-size:.875rem;margin-bottom:1rem;box-sizing:border-box;';
    $lStyle = 'display:block;font-size:.8125rem;font-weight:600;color:#374151;margin-bottom:.35rem;';
@endphp

{{-- Add Modal --}}
<div id="add-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Add Outside Storage Item</h2>
            <button onclick="document.getElementById('add-modal').style.display='none'" style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <form action="{{ route('racking.outside.store') }}" method="POST" style="padding:1.5rem;">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 1rem;">
                <div><label style="{{ $lStyle }}">Date Stored</label><input type="date" name="storage_date" style="{{ $fStyle }}"></div>
                <div><label style="{{ $lStyle }}">Return Date</label><input type="date" name="return_date" style="{{ $fStyle }}"></div>
            </div>
            <label style="{{ $lStyle }}">Colour / Type</label>
            <input type="text" name="colour" style="{{ $fStyle }}" placeholder="Green Zip, Pink Booklets…">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0 1rem;">
                <div><label style="{{ $lStyle }}">Quantity</label><input type="text" name="quantity" style="{{ $fStyle }}" placeholder="294,000"></div>
                <div><label style="{{ $lStyle }}">Ref</label><input type="text" name="ref" style="{{ $fStyle }}" placeholder="105"></div>
                <div><label style="{{ $lStyle }}">Year</label><input type="number" name="year" style="{{ $fStyle }}" placeholder="{{ date('Y') }}" min="2000" max="2100"></div>
            </div>
            <label style="{{ $lStyle }}">Notes</label>
            <textarea name="notes" rows="2" style="{{ $fStyle }}resize:vertical;"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:.5rem;">
                <button type="button" onclick="document.getElementById('add-modal').style.display='none'"
                    style="padding:.6rem 1.25rem;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;cursor:pointer;">Cancel</button>
                <button type="submit"
                    style="padding:.6rem 1.5rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:700;cursor:pointer;">Add</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Edit Outside Storage Item</h2>
            <button onclick="document.getElementById('edit-modal').style.display='none'" style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <form id="edit-form" method="POST" style="padding:1.5rem;">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 1rem;">
                <div><label style="{{ $lStyle }}">Date Stored</label><input type="date" name="storage_date" id="edit-storage-date" style="{{ $fStyle }}"></div>
                <div><label style="{{ $lStyle }}">Return Date</label><input type="date" name="return_date" id="edit-return-date" style="{{ $fStyle }}"></div>
            </div>
            <label style="{{ $lStyle }}">Colour / Type</label>
            <input type="text" name="colour" id="edit-colour" style="{{ $fStyle }}">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0 1rem;">
                <div><label style="{{ $lStyle }}">Quantity</label><input type="text" name="quantity" id="edit-quantity" style="{{ $fStyle }}"></div>
                <div><label style="{{ $lStyle }}">Ref</label><input type="text" name="ref" id="edit-ref" style="{{ $fStyle }}"></div>
                <div><label style="{{ $lStyle }}">Year</label><input type="number" name="year" id="edit-year" style="{{ $fStyle }}" min="2000" max="2100"></div>
            </div>
            <label style="{{ $lStyle }}">Notes</label>
            <textarea name="notes" id="edit-notes" rows="2" style="{{ $fStyle }}resize:vertical;"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:.5rem;">
                <button type="button" onclick="document.getElementById('edit-modal').style.display='none'"
                    style="padding:.6rem 1.25rem;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;cursor:pointer;">Cancel</button>
                <button type="submit"
                    style="padding:.6rem 1.5rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:700;cursor:pointer;">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, item) {
    document.getElementById('edit-form').action = '/racking/outside-storage/' + id;
    document.getElementById('edit-storage-date').value = item.storage_date ? item.storage_date.substring(0,10) : '';
    document.getElementById('edit-return-date').value  = item.return_date  ? item.return_date.substring(0,10)  : '';
    document.getElementById('edit-colour').value   = item.colour   || '';
    document.getElementById('edit-quantity').value = item.quantity || '';
    document.getElementById('edit-ref').value      = item.ref      || '';
    document.getElementById('edit-year').value     = item.year     || '';
    document.getElementById('edit-notes').value    = item.notes    || '';
    document.getElementById('edit-modal').style.display = 'flex';
}
</script>
</main>
</x-layout>
