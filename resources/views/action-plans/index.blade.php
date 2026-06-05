<x-layout title="Action Plans — Lockie Portal">
<main style="max-width:1100px;margin:0 auto;padding:2rem 1.5rem;">

    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.75rem;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Action Plans</h1>
            <p style="color:#64748b;font-size:0.875rem;margin:0;">Group task lists with member-based access.</p>
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
            @can('admin')
            <a href="{{ $showArchived ? route('action-plans.index') : route('action-plans.index', ['archived' => 1]) }}"
                style="display:inline-flex;align-items:center;padding:0.45rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:0.8125rem;cursor:pointer;text-decoration:none;">
                {{ $showArchived ? '← Active Plans' : 'Archived' }}
            </a>
            @endcan
            @if(!$showArchived)
            @can('admin')
            <button onclick="document.getElementById('create-plan-modal').style.display='flex'"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Plan
            </button>
            @endcan
            @endif
        </div>
    </div>

    @if(session('success'))
    <div style="margin-bottom:1rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:0.875rem;border-radius:8px;padding:0.75rem 1rem;">{{ session('success') }}</div>
    @endif

    @if($plans->isEmpty())
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:3rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
        {{ $showArchived ? 'No archived plans.' : 'No action plans yet.' }}
        @if(!$showArchived) @can('admin') Click "New Plan" to create one.@endcan @endif
    </div>
    @else
    <div id="plans-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;">
        @foreach($plans as $plan)
        <div data-id="{{ $plan->id }}"
            style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;display:flex;flex-direction:column;{{ $plan->is_archived ? 'opacity:0.7;' : '' }}position:relative;">

            {{-- Clickable body --}}
            <div onclick="window.location.href='{{ route('action-plans.show', $plan) }}'" style="display:flex;flex-direction:column;gap:0.6rem;padding:1.25rem 1.5rem;flex:1;cursor:pointer;">
                <div style="display:flex;align-items:center;gap:0.5rem;padding-right:2rem;">
                    @can('admin')
                    @if(!$showArchived)
                    <span class="drag-handle" onclick="event.stopPropagation()" style="cursor:grab;color:#d1d5db;flex-shrink:0;line-height:0;" title="Drag to reorder">
                        <svg width="10" height="14" viewBox="0 0 10 14" fill="currentColor"><circle cx="2" cy="2" r="1.25"/><circle cx="8" cy="2" r="1.25"/><circle cx="2" cy="7" r="1.25"/><circle cx="8" cy="7" r="1.25"/><circle cx="2" cy="12" r="1.25"/><circle cx="8" cy="12" r="1.25"/></svg>
                    </span>
                    @endif
                    @endcan
                    <span style="font-size:0.9375rem;font-weight:700;color:#1e293b;line-height:1.3;">{{ $plan->name }}</span>
                </div>

                @if($plan->start_date || $plan->end_date)
                <p style="font-size:0.75rem;color:#94a3b8;margin:0;">
                    {{ $plan->start_date?->format('d M Y') ?? '—' }} &rarr; {{ $plan->end_date?->format('d M Y') ?? '—' }}
                </p>
                @endif

                @if($plan->description)
                <p style="color:#64748b;font-size:0.8125rem;margin:0;line-height:1.4;">{{ $plan->description }}</p>
                @endif

                @if($plan->members->isNotEmpty())
                <div style="display:flex;flex-wrap:wrap;gap:0.3rem;margin-top:0.1rem;">
                    @foreach($plan->members as $m)
                    <span style="padding:2px 9px;border-radius:9999px;background:#f1f5f9;color:#475569;font-size:0.72rem;font-weight:500;">{{ $m->user->name }}</span>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div onclick="event.stopPropagation()" style="padding:0.75rem 1.5rem;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:0.8rem;color:#94a3b8;">{{ $plan->items()->count() }} {{ str('task')->plural($plan->items()->count()) }}</span>
                @can('admin')
                @if($plan->is_archived)
                <form action="{{ route('action-plans.unarchive', $plan) }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" style="padding:3px 10px;border-radius:6px;border:1px solid #bbf7d0;background:#f0fdf4;color:#166534;font-size:0.72rem;cursor:pointer;">Restore</button>
                </form>
                @else
                <div style="display:flex;gap:0.375rem;align-items:center;">
                    <button type="button"
                        onclick="alert('JS works - plan ' + {{ $plan->id }}); event.stopPropagation(); openEditPlan({{ $plan->id }}, '{{ addslashes($plan->name) }}', '{{ addslashes($plan->description ?? '') }}', '{{ $plan->start_date?->format('Y-m-d') }}', '{{ $plan->end_date?->format('Y-m-d') }}')"
                        style="padding:3px 10px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:0.75rem;cursor:pointer;">Edit</button>
                    <button type="button"
                        onclick="event.stopPropagation(); openManageMembers({{ $plan->id }})"
                        style="padding:3px 10px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:0.75rem;cursor:pointer;">Members</button>
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
<div id="create-plan-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:480px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 1.25rem;">New Action Plan</h2>
        <form action="{{ route('action-plans.store') }}" method="POST">
            @csrf
            <div style="margin-bottom:0.875rem;">
                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Plan Name *</label>
                <input type="text" name="name" required maxlength="150"
                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:0.875rem;">
                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Description</label>
                <textarea name="description" rows="2"
                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1.25rem;">
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Start Date</label>
                    <input type="date" name="start_date" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">End Date</label>
                    <input type="date" name="end_date" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button type="submit" style="padding:0.45rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">Create Plan</button>
                <button type="button" onclick="document.getElementById('create-plan-modal').style.display='none'"
                    style="padding:0.45rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit plan modal --}}
<div id="edit-plan-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:480px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 1.25rem;">Edit Plan</h2>
        <form id="edit-plan-form" method="POST">
            @csrf @method('PUT')
            <div style="margin-bottom:0.875rem;">
                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Plan Name *</label>
                <input type="text" id="edit-plan-name" name="name" required maxlength="150"
                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:0.875rem;">
                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Description</label>
                <textarea id="edit-plan-desc" name="description" rows="2"
                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.875rem;">
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Start Date</label>
                    <input type="date" id="edit-plan-start" name="start_date" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">End Date</label>
                    <input type="date" id="edit-plan-end" name="end_date" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;margin-bottom:0.5rem;">
                <button type="submit" style="padding:0.45rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">Save</button>
                <button type="button" onclick="document.getElementById('edit-plan-modal').style.display='none'"
                    style="padding:0.45rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;cursor:pointer;">Cancel</button>
            </div>
        </form>
        <div style="display:flex;gap:0.5rem;padding-top:0.5rem;border-top:1px solid #f1f5f9;flex-wrap:wrap;">
            <form id="duplicate-plan-form" method="POST" style="margin:0;">
                @csrf
                <button type="submit" style="padding:0.35rem 0.75rem;border-radius:7px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.75rem;cursor:pointer;">Duplicate</button>
            </form>
            <form id="archive-plan-form" method="POST" style="margin:0;">
                @csrf
                <button type="submit" style="padding:0.35rem 0.75rem;border-radius:7px;border:1px solid #fed7aa;background:#fff;color:#c2410c;font-size:0.75rem;cursor:pointer;">Archive Plan</button>
            </form>
            <form id="delete-plan-form" method="POST" style="margin:0;" onsubmit="return confirm('Delete this plan and all its tasks?')">
                @csrf @method('DELETE')
                <button type="submit" style="padding:0.35rem 0.75rem;border-radius:7px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.75rem;cursor:pointer;">Delete Plan</button>
            </form>
        </div>
    </div>
</div>

{{-- Manage members modal --}}
<div id="manage-members-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:420px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 1.25rem;">Manage Members</h2>
        <div id="members-list" style="margin-bottom:1rem;display:flex;flex-direction:column;gap:0.375rem;"></div>
        <form id="add-member-form" method="POST">
            @csrf
            <div style="display:flex;gap:0.5rem;align-items:center;">
                <select name="user_id" required
                    style="flex:1;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                    <option value="">Add a member…</option>
                    @foreach($allUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                <button type="submit" style="padding:0.45rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;white-space:nowrap;">Add</button>
            </div>
        </form>
        <div style="margin-top:1rem;text-align:right;">
            <button type="button" onclick="document.getElementById('manage-members-modal').style.display='none'"
                style="padding:0.45rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;cursor:pointer;">Done</button>
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
            { id: {{ $m->user->id }}, name: {{ json_encode($m->user->name) }} },
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
    try {
    var data = plansData[planId] || { members: [] };
    var list = document.getElementById('members-list');
    if (!list) { alert('ERROR: members-list not found'); return; }
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
    var addForm = document.getElementById('add-member-form');
    if (!addForm) { alert('ERROR: add-member-form not found'); return; }
    addForm.action = planRouteBase + '/' + planId + '/members';
    var modal = document.getElementById('manage-members-modal');
    if (!modal) { alert('ERROR: manage-members-modal not found'); return; }
    modal.style.display = 'flex';
    } catch(e) { alert('openManageMembers error: ' + e.message); }
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
