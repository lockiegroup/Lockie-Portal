<x-layout title="{{ $plan->name }} — Lockie Portal">
<main class="max-w-[1400px] mx-auto px-6 py-8">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div>
            <a href="{{ route('action-plans.index') }}" class="text-[0.8125rem] text-slate-400 no-underline hover:text-slate-600 transition-colors">&larr; All Plans</a>
            <h1 class="text-2xl font-bold text-slate-800 mt-1 mb-1">{{ $plan->name }}</h1>
            @if($plan->description)
            <p class="text-sm text-slate-500 m-0">{{ $plan->description }}</p>
            @endif
            @if($plan->start_date || $plan->end_date)
            <p class="text-[0.8rem] text-slate-400 mt-0.5 mb-0">
                {{ $plan->start_date?->format('d M Y') ?? '—' }} &rarr; {{ $plan->end_date?->format('d M Y') ?? '—' }}
            </p>
            @endif
            <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach($plan->members as $m)
                <span class="px-2 py-px rounded-full bg-slate-100 text-slate-500 text-[0.72rem] font-medium">{{ $m->user->name }}</span>
                @endforeach
            </div>
        </div>
        <div class="flex gap-2 flex-wrap items-start">
            @can('admin')
            <button onclick="document.getElementById('import-modal').style.display='flex'"
                class="inline-flex items-center gap-1.5 px-3.5 py-[0.45rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.8125rem] font-semibold cursor-pointer hover:bg-slate-50 transition-colors">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Import CSV
            </button>
            @endcan
            <button onclick="document.getElementById('add-task-section').style.display = document.getElementById('add-task-section').style.display === 'none' ? 'block' : 'none'"
                class="inline-flex items-center gap-1.5 px-3.5 py-[0.45rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Task
            </button>
        </div>
    </div>

    @php
    $dateMin = $plan->start_date?->format('Y-m-d') ?? '';
    $dateMax = $plan->end_date?->format('Y-m-d')   ?? '';
    @endphp

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ $errors->first() }}</div>
    @endif

    {{-- Add Task Form --}}
    <div id="add-task-section" style="display:none;" class="bg-white rounded-xl border border-slate-200 px-6 py-5 mb-6">
        <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide m-0 mb-4">Add New Task</p>
        <form action="{{ route('action-plans.items.store', $plan) }}" method="POST">
            @csrf
            <div class="grid gap-3 mb-3" style="grid-template-columns:1fr 2fr 1fr;">
                <div>
                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Brand</label>
                    <select name="brand"
                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 bg-white outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="">— None —</option>
                        @foreach(\App\Models\ActionPlanItem::BRANDS as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Task *</label>
                    <input type="text" name="title" required
                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Assigned To</label>
                    <div class="border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white max-h-[110px] overflow-y-auto">
                        @foreach($allUsers as $u)
                        <label class="flex items-center gap-1.5 py-0.5 cursor-pointer">
                            <input type="checkbox" name="assigned_user_ids[]" value="{{ $u->id }}" class="cursor-pointer">
                            <span class="text-[0.8rem] text-slate-700">{{ $u->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="grid gap-3 items-end" style="grid-template-columns:1fr 1fr 2fr auto;">
                <div>
                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Week Commencing</label>
                    <input type="date" name="week_commencing"
                        {{ $dateMin ? 'min="'.$dateMin.'"' : '' }} {{ $dateMax ? 'max="'.$dateMax.'"' : '' }}
                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Status</label>
                    <select name="status"
                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 bg-white outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        @foreach(\App\Models\ActionPlanItem::STATUSES as $val => $label)
                        <option value="{{ $val }}" {{ $val === 'not_started' ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Notes</label>
                    <input type="text" name="notes"
                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
                <div>
                    <button type="submit" class="px-4 py-[0.45rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer whitespace-nowrap">Add Task</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Task tables grouped by month --}}
    @if($grouped->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 px-12 py-12 text-center text-slate-400 text-sm">
        No tasks yet. Click "Add Task" to get started.
    </div>
    @else
    @php
    $statusStyles = \App\Models\ActionPlanItem::STATUS_STYLES;
    $statusLabels = \App\Models\ActionPlanItem::STATUSES;
    $usersById    = $allUsers->keyBy('id');
    @endphp

    @foreach($grouped as $monthKey => $items)
    @php
        $monthLabel = $monthKey === 'no-date' ? 'No Date' : \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->format('F Y');
    @endphp
    <div class="mb-7">
        <button type="button" onclick="toggleMonth('month-{{ $monthKey }}')"
            class="flex items-center gap-2 w-full bg-transparent border-0 border-b-2 border-slate-200 pb-1.5 mb-2 cursor-pointer text-left">
            <svg id="chev-{{ $monthKey }}" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5" class="flex-shrink-0 transition-transform duration-200"><polyline points="6 9 12 15 18 9"/></svg>
            <span class="text-[0.75rem] font-bold text-slate-400 uppercase tracking-wider">
                {{ $monthLabel }} <span class="font-normal text-slate-300">({{ $items->count() }})</span>
            </span>
        </button>
        <div id="month-{{ $monthKey }}" class="bg-white rounded-[10px] border border-slate-200 overflow-x-auto">
            <table class="w-full border-collapse text-[0.8125rem]" style="min-width:600px;">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-[7px] text-left text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide w-[90px]">Brand</th>
                        <th class="px-3 py-[7px] text-left text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide">Task</th>
                        <th class="px-3 py-[7px] text-left text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide w-[150px]">Assigned To</th>
                        <th class="px-3 py-[7px] text-left text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide w-[120px]">WC Date</th>
                        <th class="px-3 py-[7px] text-left text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide w-[115px]">Status</th>
                        <th class="px-3 py-[7px] text-left text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide">Notes</th>
                        <th class="px-3 py-[7px] w-[90px]"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                @php
                    $style        = $statusStyles[$item->status] ?? $statusStyles['not_started'];
                    $assignedIds  = $item->assigned_user_ids ?? [];
                    $assignedNames = collect($assignedIds)->map(fn($id) => $usersById[$id]->name ?? null)->filter()->implode(', ');
                @endphp
                <tr id="row-{{ $item->id }}" class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                    <td class="px-3 py-[9px] text-slate-500 text-[0.8rem]">{{ $item->brand ?: '—' }}</td>
                    <td class="px-3 py-[9px] text-slate-800 font-medium">{{ $item->title }}</td>
                    <td class="px-3 py-[9px] text-slate-500 text-[0.8rem]">{{ $assignedNames ?: '—' }}</td>
                    <td class="px-3 py-[9px] text-slate-500 text-[0.8rem]">{{ $item->week_commencing ? $item->week_commencing->format('d M Y') : '—' }}</td>
                    <td class="px-3 py-[9px]">
                        <span class="inline-block px-2 py-0.5 rounded-md text-[0.72rem] font-semibold" style="background:{{ $style['bg'] }};color:{{ $style['color'] }};">
                            {{ $statusLabels[$item->status] }}
                        </span>
                    </td>
                    <td class="px-3 py-[9px] text-slate-500 text-[0.8rem] max-w-[220px]">
                        @if($item->notes)
                        <div class="line-clamp-2 leading-snug" title="{{ $item->notes }}">{{ $item->notes }}</div>
                        @else —
                        @endif
                    </td>
                    <td class="px-3 py-[9px] text-right whitespace-nowrap">
                        <button onclick="toggleEdit('edit-{{ $item->id }}')"
                            class="px-2 py-px rounded-md border border-slate-200 bg-white text-slate-700 text-[0.72rem] cursor-pointer mr-1 hover:bg-slate-50 transition-colors">Edit</button>
                        <button onclick="openCopyItem({{ $item->id }})"
                            class="px-2 py-px rounded-md border border-slate-200 bg-white text-indigo-500 text-[0.72rem] cursor-pointer mr-1 hover:bg-slate-50 transition-colors">Copy</button>
                        <form action="{{ route('action-plans.items.destroy', [$plan, $item]) }}" method="POST" class="inline-block m-0"
                            onsubmit="return confirm('Delete this task?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-2 py-px rounded-md border border-red-300 bg-white text-red-600 text-[0.72rem] cursor-pointer hover:bg-red-50 transition-colors">Del</button>
                        </form>
                    </td>
                </tr>
                {{-- Inline edit row --}}
                <tr id="edit-{{ $item->id }}" style="display:none;" class="bg-slate-50 border-b border-slate-200">
                    <td colspan="7" class="px-5 py-4">
                        <form action="{{ route('action-plans.items.update', [$plan, $item]) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="grid gap-3 mb-3" style="grid-template-columns:1fr 2fr 1fr;">
                                <div>
                                    <label class="block text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Brand</label>
                                    <select name="brand"
                                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[6px] text-[0.8rem] text-slate-700 bg-white outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                                        <option value="">— None —</option>
                                        @foreach(\App\Models\ActionPlanItem::BRANDS as $b)
                                        <option value="{{ $b }}" {{ $item->brand === $b ? 'selected' : '' }}>{{ $b }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Task *</label>
                                    <input type="text" name="title" value="{{ $item->title }}" required
                                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[6px] text-[0.8rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Assigned To</label>
                                    <div class="border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white max-h-[110px] overflow-y-auto">
                                        @foreach($allUsers as $u)
                                        <label class="flex items-center gap-1.5 py-0.5 cursor-pointer">
                                            <input type="checkbox" name="assigned_user_ids[]" value="{{ $u->id }}"
                                                {{ in_array($u->id, $assignedIds) ? 'checked' : '' }} class="cursor-pointer">
                                            <span class="text-[0.78rem] text-slate-700">{{ $u->name }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="grid gap-3 items-end" style="grid-template-columns:1fr 1fr 2fr auto;">
                                <div>
                                    <label class="block text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Week Commencing</label>
                                    <input type="date" name="week_commencing" value="{{ $item->week_commencing?->format('Y-m-d') }}"
                                        {{ $dateMin ? 'min="'.$dateMin.'"' : '' }} {{ $dateMax ? 'max="'.$dateMax.'"' : '' }}
                                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[6px] text-[0.8rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Status</label>
                                    <select name="status"
                                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[6px] text-[0.8rem] text-slate-700 bg-white outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                                        @foreach(\App\Models\ActionPlanItem::STATUSES as $val => $label)
                                        <option value="{{ $val }}" {{ $item->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[0.68rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Notes</label>
                                    <input type="text" name="notes" value="{{ $item->notes }}"
                                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[6px] text-[0.8rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 text-white text-[0.75rem] font-semibold border-0 cursor-pointer whitespace-nowrap">Save</button>
                                    <button type="button" onclick="toggleEdit('edit-{{ $item->id }}')"
                                        class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.75rem] cursor-pointer whitespace-nowrap hover:bg-slate-50 transition-colors">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
    @endif

</main>

@can('admin')
{{-- Import CSV Modal --}}
<div id="import-modal" style="display:none;" class="fixed inset-0 z-[100] items-center justify-center bg-black/45 p-4">
    <div class="bg-white rounded-2xl w-full max-w-[500px] p-6 shadow-2xl">
        <h2 class="text-base font-bold text-slate-800 mb-1">Import CSV</h2>
        <p class="text-[0.8rem] text-slate-500 mb-5">Export your spreadsheet as CSV. Required column headers (row 1):</p>
        <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-[0.775rem] text-slate-500 font-mono mb-5 leading-relaxed">
            Brand, Task, Assigned To, WC Date, Status, Notes
        </div>
        <ul class="text-[0.775rem] text-slate-500 mb-5 pl-5 leading-loose">
            <li><strong>Brand</strong> — Lockie, JWP, H&amp;H, All (or blank)</li>
            <li><strong>Assigned To</strong> — full name(s) as shown in portal, comma-separated</li>
            <li><strong>WC Date</strong> — any date format, e.g. <em>03/11/2025</em> or <em>WC 3/11/2025</em></li>
            <li><strong>Status</strong> — Not Started, In Progress, Completed, Cancelled, Booked In</li>
        </ul>
        <form action="{{ route('action-plans.import', $plan) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <input type="file" name="csv_file" accept=".csv,.txt" required
                    class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 bg-white outline-none">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-[0.45rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer">Import</button>
                <button type="button" onclick="document.getElementById('import-modal').style.display='none'"
                    class="px-4 py-[0.45rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.8125rem] cursor-pointer hover:bg-slate-50 transition-colors">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- Copy Item Modal --}}
<div id="copy-item-modal" style="display:none;" class="fixed inset-0 z-[100] items-center justify-center bg-black/45 p-4">
    <div class="bg-white rounded-2xl w-full max-w-[360px] p-6 shadow-2xl">
        <h2 class="text-base font-bold text-slate-800 mb-5">Copy Task Forward</h2>
        <form id="copy-item-form" action="" method="POST">
            @csrf
            <input type="hidden" name="item_ids[]" id="copy-item-id" value="">
            <div class="mb-5">
                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1.5">How many months ahead?</label>
                <select name="months"
                    class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 bg-white outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    <option value="0" selected>Current month</option>
                    <option value="1">1 month</option>
                    <option value="2">2 months</option>
                    <option value="3">3 months</option>
                    <option value="6">6 months</option>
                    <option value="12">12 months</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-[0.45rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer">Copy</button>
                <button type="button" onclick="document.getElementById('copy-item-modal').style.display='none'"
                    class="px-4 py-[0.45rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.8125rem] cursor-pointer hover:bg-slate-50 transition-colors">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleMonth(id) {
    var el = document.getElementById(id);
    var key = id.replace('month-', '');
    var chev = document.getElementById('chev-' + key);
    if (!el) return;
    var collapsed = el.style.display === 'none';
    el.style.display = collapsed ? '' : 'none';
    if (chev) chev.style.transform = collapsed ? '' : 'rotate(-90deg)';
}

// Auto-collapse past months on load
(function() {
    var now = new Date();
    var currentKey = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    document.querySelectorAll('[id^="month-"]').forEach(function(el) {
        var key = el.id.replace('month-', '');
        if (key !== 'no-date' && key < currentKey) {
            el.style.display = 'none';
            var chev = document.getElementById('chev-' + key);
            if (chev) chev.style.transform = 'rotate(-90deg)';
        }
    });
})();

function toggleEdit(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
}
function openCopyItem(itemId) {
    document.getElementById('copy-item-id').value = itemId;
    document.getElementById('copy-item-form').action = '{{ route('action-plans.items.copy', $plan) }}';
    document.getElementById('copy-item-modal').style.display = 'flex';
}
document.addEventListener('click', function(e) {
    ['copy-item-modal','import-modal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && e.target === el) el.style.display = 'none';
    });
});
</script>

</x-layout>
