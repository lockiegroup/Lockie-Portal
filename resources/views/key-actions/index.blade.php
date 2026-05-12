<x-layout title="Key Actions — Lockie Portal">

<main style="max-width:1000px;margin:0 auto;padding:2rem 1.5rem;">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">Key Actions</h1>
            <p style="font-size:0.875rem;color:#64748b;margin:0.25rem 0 0;">Action boards for team meetings.</p>
        </div>
        @if(auth()->user()->isMaster())
        <button onclick="document.getElementById('new-group-modal').style.display='flex'"
                style="background:#1e293b;color:#fff;border:none;border-radius:0.5rem;padding:0.5rem 1.25rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
            + New Group
        </button>
        @endif
    </div>

    @if($groups->isEmpty())
    <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:3rem;text-align:center;color:#94a3b8;">
        No action groups yet.
    </div>
    @else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
        @foreach($groups as $group)
        <a href="{{ route('key-actions.show', $group) }}"
           style="display:block;background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1.25rem;text-decoration:none;transition:box-shadow 0.15s;"
           onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'" onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,0.07)'">
            <p style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.5rem;">{{ $group->name }}</p>
            <p style="font-size:0.8125rem;color:#64748b;margin:0 0 0.75rem;">
                {{ $group->tasks_count }} task{{ $group->tasks_count !== 1 ? 's' : '' }}
            </p>
            <div style="display:flex;flex-wrap:wrap;gap:4px;">
                @foreach($group->members->take(6) as $member)
                <span style="background:#f1f5f9;color:#475569;border-radius:9999px;padding:2px 8px;font-size:0.7rem;font-weight:600;">
                    {{ $member->name }}
                </span>
                @endforeach
                @if($group->members->count() > 6)
                <span style="background:#f1f5f9;color:#94a3b8;border-radius:9999px;padding:2px 8px;font-size:0.7rem;">+{{ $group->members->count() - 6 }}</span>
                @endif
            </div>
        </a>
        @endforeach
    </div>
    @endif

</main>

{{-- New Group Modal --}}
@if(auth()->user()->isMaster())
<div id="new-group-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:0.75rem;padding:1.5rem;width:100%;max-width:420px;margin:1rem;">
        <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">New Action Group</h2>
        <form method="POST" action="{{ route('key-actions.store') }}">
            @csrf
            <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Group Name</label>
            <input name="name" required autofocus placeholder="e.g. Management Key Actions"
                   style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;box-sizing:border-box;margin-bottom:1rem;">
            <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('new-group-modal').style.display='none'"
                        style="background:#f1f5f9;color:#475569;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit"
                        style="background:#1e293b;color:#fff;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>
@endif

</x-layout>
