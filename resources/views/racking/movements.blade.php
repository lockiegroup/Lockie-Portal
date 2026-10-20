<x-layout title="Movement Log — Racking — Lockie Portal">
<main class="max-w-[1200px] mx-auto px-4 sm:px-6 py-8">

{{-- Header --}}
<div class="flex items-center gap-4 flex-wrap mb-6">
    <a href="{{ route('racking.index') }}"
       class="text-[0.8125rem] text-slate-500 no-underline inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 rounded-md bg-white hover:bg-slate-50 transition-colors">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Main Racking
    </a>
    <div>
        <h1 class="text-[1.4rem] font-bold text-slate-800 mb-0.5">Movement Log</h1>
        <p class="text-slate-500 text-sm">Automatically recorded whenever a slot or outside storage item is added, moved, or removed.</p>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3 mb-4 text-green-800 text-sm">{{ session('success') }}</div>
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
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full border-collapse text-[0.8125rem]">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-left font-semibold text-slate-700 whitespace-nowrap">Date &amp; Time</th>
                <th class="px-3 py-3 text-left font-semibold text-slate-700 w-28">Action</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Description</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700 w-28">Quantity</th>
                <th class="px-3 py-3 text-left font-semibold text-slate-700 w-24">From</th>
                <th class="px-3 py-3 text-left font-semibold text-slate-700 w-24">To</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700 w-32">By</th>
                <th class="px-3 py-3 w-14"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($movements as $m)
        @php $badge = actionBadge($m->action_type); @endphp
        <tr class="border-b border-slate-100">
            <td class="px-4 py-2.5 text-slate-500 whitespace-nowrap">
                {{ $m->created_at ? $m->created_at->format('d/m/y H:i') : $m->moved_at->format('d/m/y') }}
            </td>
            <td class="px-3 py-2.5">
                <span class="rounded px-2 py-0.5 text-[0.7rem] font-bold whitespace-nowrap" style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};">{{ $badge['label'] }}</span>
            </td>
            <td class="px-4 py-2.5 font-medium text-slate-800">{{ $m->description }}</td>
            <td class="px-4 py-2.5 text-slate-700">{{ $m->quantity }}</td>
            <td class="px-3 py-2.5">
                @if($m->from_location)
                <span class="bg-slate-100 text-slate-700 rounded px-1.5 py-0.5 font-semibold text-[0.75rem]">{{ $m->from_location }}</span>
                @else<span class="text-slate-300 text-[0.75rem]">—</span>@endif
            </td>
            <td class="px-3 py-2.5">
                @if($m->to_location)
                <span class="bg-blue-100 text-blue-800 rounded px-1.5 py-0.5 font-semibold text-[0.75rem]">{{ $m->to_location }}</span>
                @else<span class="text-slate-300 text-[0.75rem]">—</span>@endif
            </td>
            <td class="px-4 py-2.5 text-slate-500">{{ $m->moved_by ?? '—' }}</td>
            <td class="px-2.5 py-2 text-right">
                <form action="{{ route('racking.movements.destroy', $m) }}" method="POST" onsubmit="return confirm('Delete this log entry?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-[0.7rem] text-red-600 bg-transparent border border-red-200 rounded px-1.5 py-0.5 cursor-pointer hover:bg-red-50 transition-colors">Del</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="p-12 text-center text-slate-400">No movements recorded yet. Actions on the racking or outside storage pages will appear here automatically.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

</main>
</x-layout>
