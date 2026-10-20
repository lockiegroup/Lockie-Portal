<x-layout title="Outside Storage — Racking — Lockie Portal">
<main class="max-w-[1100px] mx-auto px-4 sm:px-6 py-8">

{{-- Header --}}
<div class="flex items-center justify-between flex-wrap gap-4 mb-6">
    <div class="flex items-center gap-4 flex-wrap">
        <a href="{{ route('racking.index') }}"
           class="text-[0.8125rem] text-slate-500 no-underline inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 rounded-md bg-white hover:bg-slate-50 transition-colors">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Main Racking
        </a>
        <div>
            <h1 class="text-[1.4rem] font-bold text-slate-800 mb-0.5">Outside Storage</h1>
            <p class="text-slate-500 text-sm">{{ $items->count() }} items currently in outside storage</p>
        </div>
    </div>
    <button onclick="document.getElementById('add-modal').style.display='flex'"
        class="px-4 py-2 bg-slate-900 text-white border-0 rounded-lg text-[0.8125rem] font-bold cursor-pointer hover:bg-slate-800 transition-colors">
        + Add Item
    </button>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3 mb-4 text-green-800 text-sm">{{ session('success') }}</div>
@endif

{{-- Table --}}
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full border-collapse text-[0.8125rem]">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Date Stored</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Colour / Type</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Quantity</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Ref</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Year</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Return Date</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Notes</th>
                <th class="px-3 py-3 w-40"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
        <tr class="border-b border-slate-100">
            <td class="px-4 py-2.5 text-slate-700">{{ $item->storage_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5 font-medium text-slate-800">{{ $item->colour }}</td>
            <td class="px-4 py-2.5 text-slate-700">{{ $item->quantity }}</td>
            <td class="px-4 py-2.5 text-slate-500">{{ $item->ref }}</td>
            <td class="px-4 py-2.5 text-slate-500">{{ $item->year }}</td>
            <td class="px-4 py-2.5">
                @if($item->return_date)
                    <span class="bg-green-100 text-green-800 rounded px-2 py-0.5 text-[0.75rem]">{{ $item->return_date->format('d/m/Y') }}</span>
                @else
                    <span class="text-slate-300 text-[0.75rem]">—</span>
                @endif
            </td>
            <td class="px-4 py-2.5 text-slate-500 text-[0.75rem]">{{ $item->notes }}</td>
            <td class="px-3 py-2 text-right whitespace-nowrap">
                <button onclick="openMoveToRack({{ $item->id }}, {{ json_encode($item->colour) }})"
                    class="text-[0.75rem] text-yellow-800 bg-yellow-50 border border-yellow-300 rounded px-2 py-0.5 cursor-pointer mr-1 font-semibold hover:bg-yellow-100 transition-colors">→ Racking</button>
                <button onclick="openEdit({{ $item->id }},{{ json_encode($item) }})"
                    class="text-[0.75rem] text-slate-600 bg-transparent border border-slate-200 rounded px-2 py-0.5 cursor-pointer mr-1 hover:bg-slate-50 transition-colors">Edit</button>
                <form action="{{ route('racking.outside.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Remove this item?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-[0.75rem] text-red-600 bg-transparent border border-red-200 rounded px-2 py-0.5 cursor-pointer hover:bg-red-50 transition-colors">Del</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="p-12 text-center text-slate-400">No items in outside storage.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- Add Modal --}}
<div id="add-modal" style="display:none;" class="fixed inset-0 bg-black/50 z-[1000] items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-base font-bold text-slate-800">Add Outside Storage Item</h2>
            <button onclick="document.getElementById('add-modal').style.display='none'" class="bg-transparent border-0 text-xl cursor-pointer text-slate-500 hover:text-slate-700">✕</button>
        </div>
        <form action="{{ route('racking.outside.store') }}" method="POST" class="p-6">
            @csrf
            <div class="grid grid-cols-2 gap-x-4">
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Date Stored</label>
                    <input type="date" name="storage_date" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Return Date</label>
                    <input type="date" name="return_date" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
            </div>
            <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Colour / Type</label>
            <input type="text" name="colour" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent" placeholder="Green Zip, Pink Booklets…">
            <div class="grid grid-cols-3 gap-x-4">
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Quantity</label>
                    <input type="text" name="quantity" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent" placeholder="294,000">
                </div>
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Ref</label>
                    <input type="text" name="ref" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent" placeholder="105">
                </div>
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Year</label>
                    <input type="number" name="year" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent" placeholder="{{ date('Y') }}" min="2000" max="2100">
                </div>
            </div>
            <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Notes</label>
            <textarea name="notes" rows="2" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none resize-y focus:ring-2 focus:ring-sky-500 focus:border-transparent"></textarea>
            <div class="flex justify-end gap-3 mt-2">
                <button type="button" onclick="document.getElementById('add-modal').style.display='none'"
                    class="px-5 py-2.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-sm cursor-pointer hover:bg-slate-200 transition-colors">Cancel</button>
                <button type="submit"
                    class="px-6 py-2.5 bg-slate-900 text-white border-0 rounded-lg text-sm font-bold cursor-pointer hover:bg-slate-800 transition-colors">Add</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" style="display:none;" class="fixed inset-0 bg-black/50 z-[1000] items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-base font-bold text-slate-800">Edit Outside Storage Item</h2>
            <button onclick="document.getElementById('edit-modal').style.display='none'" class="bg-transparent border-0 text-xl cursor-pointer text-slate-500 hover:text-slate-700">✕</button>
        </div>
        <form id="edit-form" method="POST" class="p-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-x-4">
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Date Stored</label>
                    <input type="date" name="storage_date" id="edit-storage-date" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Return Date</label>
                    <input type="date" name="return_date" id="edit-return-date" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
            </div>
            <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Colour / Type</label>
            <input type="text" name="colour" id="edit-colour" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
            <div class="grid grid-cols-3 gap-x-4">
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Quantity</label>
                    <input type="text" name="quantity" id="edit-quantity" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Ref</label>
                    <input type="text" name="ref" id="edit-ref" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Year</label>
                    <input type="number" name="year" id="edit-year" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent" min="2000" max="2100">
                </div>
            </div>
            <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Notes</label>
            <textarea name="notes" id="edit-notes" rows="2" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none resize-y focus:ring-2 focus:ring-sky-500 focus:border-transparent"></textarea>
            <div class="flex justify-end gap-3 mt-2">
                <button type="button" onclick="document.getElementById('edit-modal').style.display='none'"
                    class="px-5 py-2.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-sm cursor-pointer hover:bg-slate-200 transition-colors">Cancel</button>
                <button type="submit"
                    class="px-6 py-2.5 bg-slate-900 text-white border-0 rounded-lg text-sm font-bold cursor-pointer hover:bg-slate-800 transition-colors">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Move to Racking Modal --}}
<div id="move-rack-modal" style="display:none;" class="fixed inset-0 bg-black/50 z-[1000] items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl">
        <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center">
            <div>
                <h2 class="text-base font-bold text-slate-800">Move to Racking</h2>
                <p id="move-rack-desc" class="text-[0.8125rem] text-slate-500 mt-1"></p>
            </div>
            <button onclick="document.getElementById('move-rack-modal').style.display='none'" class="bg-transparent border-0 text-xl cursor-pointer text-slate-500 hover:text-slate-700">✕</button>
        </div>
        <form id="move-rack-form" method="POST" class="p-6">
            @csrf
            <p class="text-sm text-slate-600 mb-4">Choose the racking slot to move this item into. The slot must be empty.</p>
            <div class="grid grid-cols-2 gap-x-4">
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Bay</label>
                    <select name="bay" id="move-rack-bay" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        @foreach(['A','B','C','D','E','F','G','H'] as $letter)
                            @foreach([1,2,3] as $level)
                                <option value="{{ $letter.$level }}">{{ $letter.$level }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[0.8125rem] font-semibold text-slate-700 mb-1.5">Slot</label>
                    <select name="slot_number" id="move-rack-slot" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        @for($s=1;$s<=4;$s++)<option value="{{ $s }}">Slot {{ $s }}</option>@endfor
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-1">
                <button type="button" onclick="document.getElementById('move-rack-modal').style.display='none'"
                    class="px-5 py-2.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-sm cursor-pointer hover:bg-slate-200 transition-colors">Cancel</button>
                <button type="submit"
                    class="px-5 py-2.5 bg-slate-900 text-white border-0 rounded-lg text-sm font-bold cursor-pointer hover:bg-slate-800 transition-colors">Move to Racking</button>
            </div>
        </form>
    </div>
</div>

@if(session('error'))
<div class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-red-50 border border-red-200 rounded-lg px-5 py-3 text-red-800 text-sm z-[9999] shadow-lg">
    {{ session('error') }}
</div>
@endif

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

function openMoveToRack(id, description) {
    document.getElementById('move-rack-form').action = '/racking/outside-storage/' + id + '/move-to-rack';
    document.getElementById('move-rack-desc').textContent = description || '';
    document.getElementById('move-rack-modal').style.display = 'flex';
}

document.getElementById('move-rack-modal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
</main>
</x-layout>
