<x-layout title="Stock Movements — Racking — Lockie Portal">
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
            <h1 style="font-size:1.4rem;font-weight:700;color:#1e293b;margin:0 0 .2rem;">Stock Movements</h1>
            <p style="color:#64748b;font-size:0.875rem;margin:0;">Log of pallet movements between bay locations</p>
        </div>
    </div>
    <button onclick="document.getElementById('add-modal').style.display='flex'"
        style="padding:.5rem 1rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:.8125rem;font-weight:700;cursor:pointer;">
        + Record Movement
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
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:110px;">Date</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Description</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:120px;">Colour</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:110px;">Quantity</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:80px;">From</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:80px;">To</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Notes</th>
                <th style="padding:.7rem;width:60px;"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($movements as $m)
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:.65rem 1rem;color:#64748b;">{{ $m->moved_at->format('d/m/Y') }}</td>
            <td style="padding:.65rem 1rem;font-weight:500;color:#1e293b;">{{ $m->description }}</td>
            <td style="padding:.65rem 1rem;color:#374151;">{{ $m->colour }}</td>
            <td style="padding:.65rem 1rem;color:#374151;">{{ $m->quantity }}</td>
            <td style="padding:.65rem 1rem;">
                @if($m->from_location)<span style="background:#f1f5f9;border-radius:4px;padding:1px 8px;font-weight:600;">{{ $m->from_location }}</span>@endif
            </td>
            <td style="padding:.65rem 1rem;">
                @if($m->to_location)<span style="background:#dbeafe;color:#1e40af;border-radius:4px;padding:1px 8px;font-weight:600;">{{ $m->to_location }}</span>@endif
            </td>
            <td style="padding:.65rem 1rem;color:#64748b;font-size:.75rem;">{{ $m->notes }}</td>
            <td style="padding:.5rem .75rem;text-align:right;">
                <form action="{{ route('racking.movements.destroy', $m) }}" method="POST" onsubmit="return confirm('Delete this movement record?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size:.75rem;color:#dc2626;background:none;border:1px solid #fecaca;border-radius:5px;padding:3px 8px;cursor:pointer;">Del</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="padding:3rem;text-align:center;color:#94a3b8;">No movements recorded yet.</td></tr>
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
    <div style="background:#fff;border-radius:14px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Record Stock Movement</h2>
            <button onclick="document.getElementById('add-modal').style.display='none'" style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <form action="{{ route('racking.movements.store') }}" method="POST" style="padding:1.5rem;">
            @csrf
            <label style="{{ $lStyle }}">Date *</label>
            <input type="date" name="moved_at" required value="{{ date('Y-m-d') }}" style="{{ $fStyle }}">
            <label style="{{ $lStyle }}">Description *</label>
            <input type="text" name="description" required style="{{ $fStyle }}" placeholder="e.g. Green Zips moved from A1 to C3">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 1rem;">
                <div><label style="{{ $lStyle }}">Colour</label><input type="text" name="colour" style="{{ $fStyle }}" placeholder="Green Zip"></div>
                <div><label style="{{ $lStyle }}">Quantity</label><input type="text" name="quantity" style="{{ $fStyle }}" placeholder="294,000"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 1rem;">
                <div>
                    <label style="{{ $lStyle }}">From Bay</label>
                    <select name="from_location" style="{{ $fStyle }}">
                        <option value="">— select —</option>
                        @foreach($bays as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                        <option value="Outside Storage">Outside Storage</option>
                    </select>
                </div>
                <div>
                    <label style="{{ $lStyle }}">To Bay</label>
                    <select name="to_location" style="{{ $fStyle }}">
                        <option value="">— select —</option>
                        @foreach($bays as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                        <option value="Outside Storage">Outside Storage</option>
                    </select>
                </div>
            </div>
            <label style="{{ $lStyle }}">Notes</label>
            <textarea name="notes" rows="2" style="{{ $fStyle }}resize:vertical;"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:.5rem;">
                <button type="button" onclick="document.getElementById('add-modal').style.display='none'"
                    style="padding:.6rem 1.25rem;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;cursor:pointer;">Cancel</button>
                <button type="submit"
                    style="padding:.6rem 1.5rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:700;cursor:pointer;">Record</button>
            </div>
        </form>
    </div>
</div>

</main>
</x-layout>
