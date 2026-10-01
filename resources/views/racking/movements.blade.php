<x-layout title="Movement Log — Racking — Lockie Portal">
<main style="max-width:1200px;margin:0 auto;padding:2rem 1.5rem;">

{{-- Header --}}
<div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <a href="{{ route('racking.index') }}"
       style="font-size:0.8125rem;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .75rem;border:1px solid #e2e8f0;border-radius:6px;background:#fff;">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Main Racking
    </a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:700;color:#1e293b;margin:0 0 .2rem;">Movement Log</h1>
        <p style="color:#64748b;font-size:0.875rem;margin:0;">Automatically recorded whenever a slot or outside storage item is added, moved, or removed.</p>
    </div>
</div>

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;color:#166534;font-size:.875rem;">{{ session('success') }}</div>
@endif

@php
function actionBadge($type) {
    return match($type) {
        'filled'         => ['bg'=>'#dbeafe','color'=>'#1e40af','label'=>'Slot Filled'],
        'cleared'        => ['bg'=>'#fef2f2','color'=>'#991b1b','label'=>'Slot Cleared'],
        'moved-outside'  => ['bg'=>'#fefce8','color'=>'#854d0e','label'=>'→ Outside'],
        'moved-to-rack'  => ['bg'=>'#f0fdf4','color'=>'#166534','label'=>'→ Racking'],
        'outside-added'  => ['bg'=>'#fefce8','color'=>'#854d0e','label'=>'OS Added'],
        'outside-removed'=> ['bg'=>'#fef2f2','color'=>'#991b1b','label'=>'OS Removed'],
        'moved'          => ['bg'=>'#eff6ff','color'=>'#1d4ed8','label'=>'Moved'],
        default          => ['bg'=>'#f1f5f9','color'=>'#475569','label'=>$type ?? 'Manual'],
    };
}
@endphp

{{-- Log table --}}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.8125rem;">
        <thead>
            <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;white-space:nowrap;">Date &amp; Time</th>
                <th style="padding:.7rem .75rem;text-align:left;font-weight:600;color:#374151;width:110px;">Action</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;">Description</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:110px;">Quantity</th>
                <th style="padding:.7rem .75rem;text-align:left;font-weight:600;color:#374151;width:90px;">From</th>
                <th style="padding:.7rem .75rem;text-align:left;font-weight:600;color:#374151;width:90px;">To</th>
                <th style="padding:.7rem 1rem;text-align:left;font-weight:600;color:#374151;width:130px;">By</th>
                <th style="padding:.7rem;width:52px;"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($movements as $m)
        @php $badge = actionBadge($m->action_type); @endphp
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:.6rem 1rem;color:#64748b;white-space:nowrap;">
                {{ $m->created_at ? $m->created_at->format('d/m/y H:i') : $m->moved_at->format('d/m/y') }}
            </td>
            <td style="padding:.6rem .75rem;">
                <span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};border-radius:5px;padding:2px 8px;font-size:.7rem;font-weight:700;white-space:nowrap;">{{ $badge['label'] }}</span>
            </td>
            <td style="padding:.6rem 1rem;font-weight:500;color:#1e293b;">{{ $m->description }}</td>
            <td style="padding:.6rem 1rem;color:#374151;">{{ $m->quantity }}</td>
            <td style="padding:.6rem .75rem;">
                @if($m->from_location)
                <span style="background:#f1f5f9;color:#374151;border-radius:4px;padding:2px 7px;font-weight:600;font-size:.75rem;">{{ $m->from_location }}</span>
                @else<span style="color:#cbd5e1;font-size:.75rem;">—</span>@endif
            </td>
            <td style="padding:.6rem .75rem;">
                @if($m->to_location)
                <span style="background:#dbeafe;color:#1e40af;border-radius:4px;padding:2px 7px;font-weight:600;font-size:.75rem;">{{ $m->to_location }}</span>
                @else<span style="color:#cbd5e1;font-size:.75rem;">—</span>@endif
            </td>
            <td style="padding:.6rem 1rem;color:#64748b;">{{ $m->moved_by ?? '—' }}</td>
            <td style="padding:.5rem .6rem;text-align:right;">
                <form action="{{ route('racking.movements.destroy', $m) }}" method="POST" onsubmit="return confirm('Delete this log entry?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size:.7rem;color:#dc2626;background:none;border:1px solid #fecaca;border-radius:4px;padding:2px 7px;cursor:pointer;">Del</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="padding:3rem;text-align:center;color:#94a3b8;">No movements recorded yet. Actions on the racking or outside storage pages will appear here automatically.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

</main>
</x-layout>
