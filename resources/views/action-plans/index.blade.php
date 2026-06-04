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
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;">
        @foreach($plans as $plan)
        <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:0.75rem;{{ $plan->is_archived ? 'opacity:0.7;' : '' }}">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;">
                <a href="{{ route('action-plans.show', $plan) }}"
                    style="font-size:1rem;font-weight:700;color:#1e293b;text-decoration:none;">{{ $plan->name }}</a>
                @can('admin')
                <div style="display:flex;gap:4px;flex-shrink:0;">
                    @if(!$plan->is_archived)
                    <button onclick="openEditPlan({{ $plan->id }}, '{{ addslashes($plan->name) }}', '{{ addslashes($plan->description ?? '') }}')"
                        style="padding:3px 8px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:0.72rem;cursor:pointer;">Edit</button>
                    <form action="{{ route('action-plans.duplicate', $plan) }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" style="padding:3px 8px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#6366f1;font-size:0.72rem;cursor:pointer;" title="Duplicate this plan">Copy</button>
                    </form>
                    @else
                    <form action="{{ route('action-plans.unarchive', $plan) }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" style="padding:3px 8px;border-radius:6px;border:1px solid #bbf7d0;background:#f0fdf4;color:#166534;font-size:0.72rem;cursor:pointer;">Restore</button>
                    </form>
                    @endif
                </div>
                @endcan
            </div>
            @if($plan->description)
            <p style="color:#64748b;font-size:0.8125rem;margin:0;">{{ $plan->description }}</p>
            @endif
            <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                @foreach($plan->members as $m)
                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;background:#f1f5f9;color:#475569;font-size:0.72rem;font-weight:500;">
                    {{ $m->user->name }}
                    @if(!$plan->is_archived)
                    @can('admin')
                    <form action="{{ route('action-plans.members.remove', [$plan, $m->user]) }}" method="POST" style="margin:0;line-height:0;">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;line-height:0;" title="Remove">&times;</button>
                    </form>
                    @endcan
                    @endif
                </span>
                @endforeach
                @if(!$plan->is_archived)
                @can('admin')
                <button onclick="openAddMember({{ $plan->id }})"
                    style="padding:2px 8px;border-radius:9999px;border:1px dashed #cbd5e1;background:transparent;color:#94a3b8;font-size:0.72rem;cursor:pointer;">+ Add</button>
                @endcan
                @endif
            </div>
            <a href="{{ route('action-plans.show', $plan) }}"
                style="display:inline-flex;align-items:center;gap:4px;font-size:0.8rem;color:#6366f1;text-decoration:none;font-weight:600;margin-top:auto;">
                Open plan &rarr;
            </a>
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
            <div style="margin-bottom:1.25rem;">
                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Description</label>
                <textarea name="description" rows="2"
                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;resize:vertical;box-sizing:border-box;"></textarea>
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
            <div style="display:flex;gap:0.5rem;margin-bottom:0.5rem;">
                <button type="submit" style="padding:0.45rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">Save</button>
                <button type="button" onclick="document.getElementById('edit-plan-modal').style.display='none'"
                    style="padding:0.45rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;cursor:pointer;">Cancel</button>
            </div>
        </form>
        <div style="display:flex;gap:0.5rem;padding-top:0.5rem;border-top:1px solid #f1f5f9;">
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

{{-- Add member modal --}}
<div id="add-member-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:400px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 1.25rem;">Add Member</h2>
        <form id="add-member-form" method="POST">
            @csrf
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">User</label>
                <select name="user_id" required
                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                    <option value="">Select user…</option>
                    @foreach($allUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button type="submit" style="padding:0.45rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">Add Member</button>
                <button type="button" onclick="document.getElementById('add-member-modal').style.display='none'"
                    style="padding:0.45rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
var planRouteBase = '{{ url("action-plans") }}';
function openEditPlan(id, name, desc) {
    document.getElementById('edit-plan-form').action    = planRouteBase + '/' + id;
    document.getElementById('archive-plan-form').action = planRouteBase + '/' + id + '/archive';
    document.getElementById('delete-plan-form').action  = planRouteBase + '/' + id;
    document.getElementById('edit-plan-name').value = name;
    document.getElementById('edit-plan-desc').value = desc;
    document.getElementById('edit-plan-modal').style.display = 'flex';
}
function openAddMember(id) {
    document.getElementById('add-member-form').action = planRouteBase + '/' + id + '/members';
    document.getElementById('add-member-modal').style.display = 'flex';
}
document.addEventListener('click', function(e) {
    ['create-plan-modal','edit-plan-modal','add-member-modal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && e.target === el) el.style.display = 'none';
    });
});
</script>
@endif
@endcan

</x-layout>
