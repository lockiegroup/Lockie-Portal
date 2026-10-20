<x-layout title="Action Plans — Lockie Portal">
<main class="max-w-[1100px] mx-auto px-6 py-8">

    <div class="flex items-start justify-between gap-4 mb-7 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 mb-1">Action Plans</h1>
            <p class="text-sm text-slate-500">Group task lists with member-based access.</p>
        </div>
        <div class="flex gap-2 items-center flex-wrap">
            @can('admin')
            <a href="{{ $showArchived ? route('action-plans.index') : route('action-plans.index', ['archived' => 1]) }}"
                class="inline-flex items-center px-3.5 py-[0.45rem] rounded-lg border border-slate-200 bg-white text-slate-500 text-[0.8125rem] no-underline hover:bg-slate-50 transition-colors">
                {{ $showArchived ? '← Active Plans' : 'Archived' }}
            </a>
            @endcan
            @if(!$showArchived)
            @can('admin')
            <button onclick="document.getElementById('create-plan-modal').style.display='flex'"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Plan
            </button>
            @endcan
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif

    @if($plans->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 px-12 py-12 text-center text-slate-400 text-sm">
        {{ $showArchived ? 'No archived plans.' : 'No action plans yet.' }}
        @if(!$showArchived) @can('admin') Click "New Plan" to create one.@endcan @endif
    </div>
    @else
    <div id="plans-grid" class="grid gap-4" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr));">
        @foreach($plans as $plan)
        <div data-id="{{ $plan->id }}"
            class="bg-white rounded-xl border border-slate-200 flex flex-col relative {{ $plan->is_archived ? 'opacity-70' : '' }}">

            {{-- Clickable body --}}
            <div onclick="window.location.href='{{ route('action-plans.show', $plan) }}'" class="flex flex-col gap-2.5 px-6 py-5 flex-1 cursor-pointer">
                <div class="flex items-center gap-2 pr-8">
                    @can('admin')
                    @if(!$showArchived)
                    <span class="drag-handle text-slate-300 flex-shrink-0 leading-none cursor-grab" onclick="event.stopPropagation()" title="Drag to reorder">
                        <svg width="10" height="14" viewBox="0 0 10 14" fill="currentColor"><circle cx="2" cy="2" r="1.25"/><circle cx="8" cy="2" r="1.25"/><circle cx="2" cy="7" r="1.25"/><circle cx="8" cy="7" r="1.25"/><circle cx="2" cy="12" r="1.25"/><circle cx="8" cy="12" r="1.25"/></svg>
                    </span>
                    @endif
                    @endcan
                    <span class="text-[0.9375rem] font-bold text-slate-800 leading-snug">{{ $plan->name }}</span>
                </div>

                @if($plan->start_date || $plan->end_date)
                <p class="text-xs text-slate-400 m-0">
                    {{ $plan->start_date?->format('d M Y') ?? '—' }} &rarr; {{ $plan->end_date?->format('d M Y') ?? '—' }}
                </p>
                @endif

                @if($plan->description)
                <p class="text-slate-500 text-[0.8125rem] m-0 leading-snug">{{ $plan->description }}</p>
                @endif

                @if($plan->members->isNotEmpty())
                <div class="flex flex-wrap gap-1 mt-0.5">
                    @foreach($plan->members as $m)
                    <span class="px-2 py-px rounded-full bg-slate-100 text-slate-500 text-[0.72rem] font-medium">{{ $m->user->name }}</span>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div onclick="event.stopPropagation()" class="px-6 py-3 border-t border-slate-50 flex items-center justify-between">
                <span class="text-[0.8rem] text-slate-400">{{ $plan->items()->count() }} {{ str('task')->plural($plan->items()->count()) }}</span>
                @can('admin')
                @if($plan->is_archived)
                <form action="{{ route('action-plans.unarchive', $plan) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="px-2.5 py-px rounded-md border border-green-200 bg-green-50 text-green-800 text-[0.72rem] cursor-pointer">Restore</button>
                </form>
                @else
                <div class="flex gap-1.5 items-center">
                    <button type="button"
                        onclick="event.stopPropagation(); openEditPlan({{ $plan->id }}, '{{ addslashes($plan->name) }}', '{{ addslashes($plan->description ?? '') }}', '{{ $plan->start_date?->format('Y-m-d') }}', '{{ $plan->end_date?->format('Y-m-d') }}')"
                        class="px-2.5 py-px rounded-md border border-slate-200 bg-white text-slate-500 text-[0.75rem] cursor-pointer hover:bg-slate-50 transition-colors">Edit</button>
                    <button type="button"
                        onclick="event.stopPropagation(); openManageMembers({{ $plan->id }})"
                        class="px-2.5 py-px rounded-md border border-slate-200 bg-white text-slate-500 text-[0.75rem] cursor-pointer hover:bg-slate-50 transition-colors">Members</button>
                </div>
                @endif
                @endcan
            </div>
        </div>
        @endforeach
    </div>
    @endif

</main>

@can('admin')
@if(!$showArchived)
{{-- Create plan modal --}}
<div id="create-plan-modal" style="display:none;" class="fixed inset-0 z-[100] items-center justify-center bg-black/45 p-4">
    <div class="bg-white rounded-2xl w-full max-w-[480px] p-6 shadow-2xl">
        <h2 class="text-base font-bold text-slate-800 mb-5">New Action Plan</h2>
        <form action="{{ route('action-plans.store') }}" method="POST">
            @csrf
            <div class="mb-3.5">
                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Plan Name *</label>
                <input type="text" name="name" required maxlength="150"
                    class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
            </div>
            <div class="mb-3.5">
                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Description</label>
                <textarea name="description" rows="2"
                    class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 resize-y outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-5">
                <div>
                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Start Date</label>
                    <input type="date" name="start_date" class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">End Date</label>
                    <input type="date" name="end_date" class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-[0.45rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer">Create Plan</button>
                <button type="button" onclick="document.getElementById('create-plan-modal').style.display='none'"
                    class="px-4 py-[0.45rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.8125rem] cursor-pointer hover:bg-slate-50 transition-colors">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit plan modal --}}
<div id="edit-plan-modal" style="display:none;" class="fixed inset-0 z-[100] items-center justify-center bg-black/45 p-4">
    <div class="bg-white rounded-2xl w-full max-w-[480px] p-6 shadow-2xl">
        <h2 class="text-base font-bold text-slate-800 mb-5">Edit Plan</h2>
        <form id="edit-plan-form" method="POST">
            @csrf @method('PUT')
            <div class="mb-3.5">
                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Plan Name *</label>
                <input type="text" id="edit-plan-name" name="name" required maxlength="150"
                    class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
            </div>
            <div class="mb-3.5">
                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Description</label>
                <textarea id="edit-plan-desc" name="description" rows="2"
                    class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 resize-y outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3.5">
                <div>
                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Start Date</label>
                    <input type="date" id="edit-plan-start" name="start_date" class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">End Date</label>
                    <input type="date" id="edit-plan-end" name="end_date" class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex gap-2 mb-2">
                <button type="submit" class="px-4 py-[0.45rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer">Save</button>
                <button type="button" onclick="document.getElementById('edit-plan-modal').style.display='none'"
                    class="px-4 py-[0.45rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.8125rem] cursor-pointer hover:bg-slate-50 transition-colors">Cancel</button>
            </div>
        </form>
        <div class="flex gap-2 pt-2 border-t border-slate-50 flex-wrap">
            <form id="duplicate-plan-form" method="POST" class="m-0">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.75rem] cursor-pointer hover:bg-slate-50 transition-colors">Duplicate</button>
            </form>
            <form id="archive-plan-form" method="POST" class="m-0">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg border border-orange-200 bg-white text-orange-700 text-[0.75rem] cursor-pointer hover:bg-orange-50 transition-colors">Archive Plan</button>
            </form>
            <form id="delete-plan-form" method="POST" class="m-0" onsubmit="return confirm('Delete this plan and all its tasks?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-300 bg-white text-red-600 text-[0.75rem] cursor-pointer hover:bg-red-50 transition-colors">Delete Plan</button>
            </form>
        </div>
    </div>
</div>

{{-- Manage members modal --}}
<div id="manage-members-modal" style="display:none;" class="fixed inset-0 z-[100] items-center justify-center bg-black/45 p-4">
    <div class="bg-white rounded-2xl w-full max-w-[420px] p-6 shadow-2xl">
        <h2 class="text-base font-bold text-slate-800 mb-5">Manage Members</h2>
        <div id="members-list" class="mb-4 flex flex-col gap-1.5"></div>
        <form id="add-member-form" method="POST">
            @csrf
            <div class="flex gap-2 items-center">
                <select name="user_id" required
                    class="flex-1 border border-slate-200 rounded-lg px-2.5 py-2 text-[0.8125rem] text-slate-700 bg-white outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    <option value="">Add a member…</option>
                    @foreach($allUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3.5 py-[0.45rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer whitespace-nowrap">Add</button>
            </div>
        </form>
        <div class="mt-4 text-right">
            <button type="button" onclick="document.getElementById('manage-members-modal').style.display='none'"
                class="px-4 py-[0.45rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.8125rem] cursor-pointer hover:bg-slate-50 transition-colors">Done</button>
        </div>
    </div>
</div>

<script>
var planRouteBase = '{{ url("action-plans") }}';

// Stored plan members data for the manage members modal
var plansData = {
    @foreach($plans as $plan)
    {{ $plan->id }}: {
        members: [
            @foreach($plan->members as $m)
            { id: {{ $m->user->id }}, name: @json($m->user->name) },
            @endforeach
        ]
    },
    @endforeach
};

function openEditPlan(id, name, desc, start, end) {
    document.getElementById('edit-plan-form').action      = planRouteBase + '/' + id;
    document.getElementById('duplicate-plan-form').action = planRouteBase + '/' + id + '/duplicate';
    document.getElementById('archive-plan-form').action   = planRouteBase + '/' + id + '/archive';
    document.getElementById('delete-plan-form').action    = planRouteBase + '/' + id;
    document.getElementById('edit-plan-name').value  = name;
    document.getElementById('edit-plan-desc').value  = desc;
    document.getElementById('edit-plan-start').value = start || '';
    document.getElementById('edit-plan-end').value   = end   || '';
    document.getElementById('edit-plan-modal').style.display = 'flex';
}

function openManageMembers(planId) {
    var data = plansData[planId] || { members: [] };
    var list = document.getElementById('members-list');
    list.innerHTML = '';
    data.members.forEach(function(m) {
        var row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:#f8fafc;border-radius:7px;';
        row.innerHTML = '<span style="font-size:0.8125rem;color:#334155;">' + m.name + '</span>'
            + '<form action="' + planRouteBase + '/' + planId + '/members/' + m.id + '" method="POST" style="margin:0;">'
            + '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
            + '<input type="hidden" name="_method" value="DELETE">'
            + '<button type="submit" style="padding:2px 8px;border-radius:5px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.72rem;cursor:pointer;">Remove</button>'
            + '</form>';
        list.appendChild(row);
    });
    if (!data.members.length) {
        list.innerHTML = '<p style="font-size:0.8125rem;color:#94a3b8;margin:0;">No members yet.</p>';
    }
    document.getElementById('add-member-form').action = planRouteBase + '/' + planId + '/members';
    document.getElementById('manage-members-modal').style.display = 'flex';
}

document.addEventListener('click', function(e) {
    ['create-plan-modal','edit-plan-modal','manage-members-modal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && e.target === el) el.style.display = 'none';
    });
});
</script>
@endif
@endcan

@can('admin')
@if(!$showArchived)
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
var grid = document.getElementById('plans-grid');
if (grid) {
    Sortable.create(grid, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function() {
            var ids = Array.from(grid.children).map(function(el) { return el.getAttribute('data-id'); });
            fetch('{{ route('action-plans.reorder') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ ids: ids })
            });
        }
    });
}
</script>
@endif
@endcan

</x-layout>
