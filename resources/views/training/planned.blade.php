<x-layout title="Planned Training — Lockie Portal">
<main style="max-width:960px;margin:0 auto;padding:2rem 1.5rem;">

    {{-- Page header --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Planned Training</h1>
            <p style="color:#64748b;font-size:0.875rem;margin:0;">Upcoming and recently completed training sessions.</p>
        </div>
        <a href="{{ route('training.index') }}"
           style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;font-weight:600;text-decoration:none;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Matrix
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div style="margin-bottom:1rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:0.875rem;border-radius:8px;padding:0.75rem 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div style="margin-bottom:1rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:0.875rem;border-radius:8px;padding:0.75rem 1rem;">{{ $errors->first() }}</div>
    @endif

    {{-- Add Planned Session form --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:1.25rem 1.5rem;margin-bottom:1.5rem;">
        <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 1rem;">Add Planned Session</p>
        <form action="{{ route('training.planned.store') }}" method="POST">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:0.75rem;align-items:end;flex-wrap:wrap;">
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Operator *</label>
                    <select name="operator_id" required
                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                        <option value="">Select operator…</option>
                        @foreach($operators as $op)
                        <option value="{{ $op->id }}" {{ old('operator_id') == $op->id ? 'selected' : '' }}>{{ $op->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Machine *</label>
                    <select name="machine_id" required
                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                        <option value="">Select machine…</option>
                        @foreach($machines as $mac)
                        <option value="{{ $mac->id }}" {{ old('machine_id') == $mac->id ? 'selected' : '' }}>
                            @if($mac->category){{ $mac->category }} — @endif{{ $mac->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Planned Date *</label>
                    <input type="date" name="planned_date" required value="{{ old('planned_date') }}"
                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                </div>
                <div>
                    <button type="submit"
                        style="padding:0.45rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;white-space:nowrap;">
                        Add Session
                    </button>
                </div>
            </div>
            <div style="margin-top:0.75rem;">
                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Notes</label>
                <textarea name="notes" rows="2" placeholder="Optional notes…"
                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;resize:vertical;box-sizing:border-box;">{{ old('notes') }}</textarea>
            </div>
        </form>
    </div>

    {{-- Upcoming sessions --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;margin-bottom:1.5rem;">
        <div style="padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0;">Upcoming Sessions</h2>
            <span style="display:inline-flex;align-items:center;padding:0.25rem 0.75rem;border-radius:9999px;background:#eef2ff;color:#4338ca;font-size:0.75rem;font-weight:600;">
                {{ $upcoming->count() }} scheduled
            </span>
        </div>

        @if($upcoming->isEmpty())
        <div style="padding:3rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
            No upcoming training sessions planned.
        </div>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:0.625rem 1rem;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e2e8f0;">Date</th>
                    <th style="padding:0.625rem 1rem;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e2e8f0;">Operator</th>
                    <th style="padding:0.625rem 1rem;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e2e8f0;">Machine</th>
                    <th style="padding:0.625rem 1rem;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e2e8f0;">Notes</th>
                    <th style="padding:0.625rem 1rem;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e2e8f0;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($upcoming as $session)
                @php
                    $isPast = $session->planned_date->isPast();
                    $isToday = $session->planned_date->isToday();
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <td style="padding:0.75rem 1rem;white-space:nowrap;">
                        <span style="font-weight:600;color:{{ $isPast ? '#dc2626' : ($isToday ? '#d97706' : '#1e293b') }};">
                            {{ $session->planned_date->format('d M Y') }}
                        </span>
                        @if($isToday)<span style="margin-left:0.375rem;font-size:0.7rem;font-weight:700;color:#d97706;background:#fef3c7;padding:1px 6px;border-radius:4px;">TODAY</span>@endif
                        @if($isPast && !$isToday)<span style="margin-left:0.375rem;font-size:0.7rem;font-weight:700;color:#dc2626;background:#fee2e2;padding:1px 6px;border-radius:4px;">OVERDUE</span>@endif
                    </td>
                    <td style="padding:0.75rem 1rem;color:#334155;font-weight:500;">{{ $session->operator->name ?? '—' }}</td>
                    <td style="padding:0.75rem 1rem;color:#334155;">
                        {{ $session->machine->name ?? '—' }}
                        @if($session->machine?->category)
                        <span style="display:block;font-size:0.72rem;color:#94a3b8;">{{ $session->machine->category }}</span>
                        @endif
                    </td>
                    <td style="padding:0.75rem 1rem;color:#64748b;font-size:0.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $session->notes }}">
                        {{ $session->notes ?: '—' }}
                    </td>
                    <td style="padding:0.75rem 1rem;text-align:right;white-space:nowrap;">
                        <button onclick="markComplete({{ $session->id }}, '{{ route('training.planned.complete', $session->id) }}', this)"
                            style="padding:0.275rem 0.625rem;border-radius:7px;background:#0f172a;color:#fff;border:none;cursor:pointer;font-size:0.75rem;font-weight:600;margin-right:0.375rem;">
                            Mark Complete
                        </button>
                        <form action="{{ route('training.planned.destroy', $session->id) }}" method="POST" style="display:inline-block;margin:0;"
                            onsubmit="return confirm('Delete this planned session?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                style="padding:0.275rem 0.625rem;border-radius:7px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.75rem;cursor:pointer;">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Recently completed (collapsed) --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
        <button onclick="toggleCompleted()" id="completed-toggle"
            style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;background:none;border:none;cursor:pointer;font-family:inherit;">
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0;">Recently Completed</h2>
                <span style="display:inline-flex;align-items:center;padding:0.2rem 0.625rem;border-radius:9999px;background:#dcfce7;color:#166534;font-size:0.72rem;font-weight:700;">
                    last 30 days
                </span>
            </div>
            <svg id="completed-chevron" style="width:14px;height:14px;color:#94a3b8;transition:transform 0.2s;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>

        <div id="completed-panel" style="display:none;border-top:1px solid #f1f5f9;">
            @if($recentlyCompleted->isEmpty())
            <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
                No completed sessions in the last 30 days.
            </div>
            @else
            <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.625rem 1rem;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">Date</th>
                        <th style="padding:0.625rem 1rem;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">Operator</th>
                        <th style="padding:0.625rem 1rem;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">Machine</th>
                        <th style="padding:0.625rem 1rem;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentlyCompleted as $session)
                    <tr style="border-top:1px solid #f1f5f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="padding:0.75rem 1rem;font-weight:600;color:#166534;">{{ $session->planned_date->format('d M Y') }}</td>
                        <td style="padding:0.75rem 1rem;color:#334155;font-weight:500;">{{ $session->operator->name ?? '—' }}</td>
                        <td style="padding:0.75rem 1rem;color:#334155;">{{ $session->machine->name ?? '—' }}</td>
                        <td style="padding:0.75rem 1rem;color:#64748b;font-size:0.8rem;">{{ $session->notes ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

</main>

<script>
function toggleCompleted() {
    var panel   = document.getElementById('completed-panel');
    var chevron = document.getElementById('completed-chevron');
    if (!panel) return;
    var open = panel.style.display !== 'none';
    panel.style.display   = open ? 'none' : 'block';
    if (chevron) chevron.style.transform = open ? '' : 'rotate(180deg)';
}

function markComplete(id, url, btn) {
    btn.disabled = true;
    btn.textContent = 'Saving…';
    fetch(url, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.ok) {
            var row = btn.closest('tr');
            if (row) {
                row.style.opacity = '0.4';
                setTimeout(function() { row.remove(); }, 400);
            }
        }
    }).catch(function() {
        btn.disabled = false;
        btn.textContent = 'Mark Complete';
    });
}
</script>
</x-layout>
