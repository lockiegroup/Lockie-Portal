<x-layout title="{{ $plan->name }} — Lockie Portal">
<main style="max-width:1400px;margin:0 auto;padding:2rem 1.5rem;">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div>
            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                <a href="{{ route('action-plans.index') }}" style="font-size:0.8125rem;color:#94a3b8;text-decoration:none;">&larr; All Plans</a>
            </div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0.25rem 0 0.25rem;">{{ $plan->name }}</h1>
            @if($plan->description)
            <p style="color:#64748b;font-size:0.875rem;margin:0;">{{ $plan->description }}</p>
            @endif
            <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-top:0.5rem;">
                @foreach($plan->members as $m)
                <span style="padding:2px 8px;border-radius:9999px;background:#f1f5f9;color:#475569;font-size:0.72rem;font-weight:500;">{{ $m->user->name }}</span>
                @endforeach
            </div>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:flex-start;">
            <button onclick="document.getElementById('copy-modal').style.display='flex'"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.45rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copy Tasks
            </button>
            <button onclick="document.getElementById('add-task-section').style.display = document.getElementById('add-task-section').style.display === 'none' ? 'block' : 'none'"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.45rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Task
            </button>
        </div>
    </div>

    @if(session('success'))
    <div style="margin-bottom:1rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:0.875rem;border-radius:8px;padding:0.75rem 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div style="margin-bottom:1rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:0.875rem;border-radius:8px;padding:0.75rem 1rem;">{{ $errors->first() }}</div>
    @endif

    {{-- Add Task Form --}}
    <div id="add-task-section" style="display:none;background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:1.25rem 1.5rem;margin-bottom:1.5rem;">
        <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 1rem;">Add New Task</p>
        <form action="{{ route('action-plans.items.store', $plan) }}" method="POST">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 2fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Brand</label>
                    <select name="brand"
                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                        <option value="">— None —</option>
                        @foreach(\App\Models\ActionPlanItem::BRANDS as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Task *</label>
                    <input type="text" name="title" required
                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Assigned To</label>
                    <select name="assigned_user_id"
                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                        <option value="">— Unassigned —</option>
                        @foreach($allUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 2fr auto;gap:0.75rem;align-items:end;">
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Week Commencing</label>
                    <input type="date" name="week_commencing"
                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Status</label>
                    <select name="status"
                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                        @foreach(\App\Models\ActionPlanItem::STATUSES as $val => $label)
                        <option value="{{ $val }}" {{ $val === 'not_started' ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Notes</label>
                    <input type="text" name="notes"
                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                </div>
                <div>
                    <button type="submit" style="padding:0.45rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;white-space:nowrap;">Add Task</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Task table --}}
    @if($byYear->isEmpty())
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:3rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
        No tasks yet. Click "Add Task" to get started.
    </div>
    @else
    @php
    $statusStyles = \App\Models\ActionPlanItem::STATUS_STYLES;
    $statusLabels = \App\Models\ActionPlanItem::STATUSES;
    @endphp

    @foreach($byYear as $year => $yearItems)
    @php $yearLabel = $year === 'no-date' ? 'No Date' : $year; @endphp

    {{-- Year section --}}
    <div style="margin-bottom:2rem;">
        <button type="button" onclick="toggleYear('year-{{ $year }}')"
            style="display:flex;align-items:center;gap:0.5rem;width:100%;background:none;border:none;cursor:pointer;padding:0;margin-bottom:0.875rem;text-align:left;">
            <span id="year-chevron-{{ $year }}" style="display:inline-block;transition:transform 0.2s;font-size:0.875rem;color:#64748b;">&#9660;</span>
            <span style="font-size:1rem;font-weight:700;color:#1e293b;">{{ $yearLabel }}</span>
            <span style="font-size:0.8rem;color:#94a3b8;font-weight:400;">({{ $yearItems->count() }} tasks)</span>
        </button>

        <div id="year-{{ $year }}">
        @php $byMonth = $yearItems->groupBy(fn($i) => $i->week_commencing ? $i->week_commencing->format('Y-m') : 'no-date'); @endphp

        @foreach($byMonth as $monthKey => $items)
        @php
            $monthLabel = $monthKey === 'no-date' ? 'No Date' : \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->format('F Y');
        @endphp
        <div style="margin-bottom:1.5rem;">
            <h2 style="font-size:0.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 0.5rem;padding:0 0 0.375rem;border-bottom:2px solid #e2e8f0;">
                {{ $monthLabel }} <span style="font-weight:400;color:#cbd5e1;">({{ $items->count() }})</span>
            </h2>
            <div style="background:#fff;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden;">
                <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                            <th style="padding:7px 12px;text-align:left;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;width:90px;">Brand</th>
                            <th style="padding:7px 12px;text-align:left;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">Task</th>
                            <th style="padding:7px 12px;text-align:left;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;width:130px;">Assigned To</th>
                            <th style="padding:7px 12px;text-align:left;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;width:120px;">WC Date</th>
                            <th style="padding:7px 12px;text-align:left;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;width:115px;">Status</th>
                            <th style="padding:7px 12px;text-align:left;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">Notes</th>
                            <th style="padding:7px 12px;width:90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                    @php $style = $statusStyles[$item->status] ?? $statusStyles['not_started']; @endphp
                    <tr id="row-{{ $item->id }}" style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="padding:9px 12px;color:#475569;font-size:0.8rem;">{{ $item->brand ?: '—' }}</td>
                        <td style="padding:9px 12px;color:#1e293b;font-weight:500;">{{ $item->title }}</td>
                        <td style="padding:9px 12px;color:#475569;font-size:0.8rem;">{{ $item->assignedUser?->name ?: '—' }}</td>
                        <td style="padding:9px 12px;color:#64748b;font-size:0.8rem;">{{ $item->week_commencing ? $item->week_commencing->format('d M Y') : '—' }}</td>
                        <td style="padding:9px 12px;">
                            <span style="display:inline-block;padding:2px 8px;border-radius:6px;font-size:0.72rem;font-weight:600;background:{{ $style['bg'] }};color:{{ $style['color'] }};">
                                {{ $statusLabels[$item->status] }}
                            </span>
                        </td>
                        <td style="padding:9px 12px;color:#64748b;font-size:0.8rem;max-width:220px;">{{ $item->notes ?: '—' }}</td>
                        <td style="padding:9px 12px;text-align:right;white-space:nowrap;">
                            <button onclick="toggleEdit('edit-{{ $item->id }}')"
                                style="padding:3px 8px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.72rem;cursor:pointer;margin-right:4px;">Edit</button>
                            <form action="{{ route('action-plans.items.destroy', [$plan, $item]) }}" method="POST" style="display:inline-block;margin:0;"
                                onsubmit="return confirm('Delete this task?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="padding:3px 8px;border-radius:6px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.72rem;cursor:pointer;">Del</button>
                            </form>
                        </td>
                    </tr>
                    {{-- Inline edit row --}}
                    <tr id="edit-{{ $item->id }}" style="display:none;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                        <td colspan="7" style="padding:1rem 1.25rem;">
                            <form action="{{ route('action-plans.items.update', [$plan, $item]) }}" method="POST">
                                @csrf @method('PUT')
                                <div style="display:grid;grid-template-columns:1fr 2fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                                    <div>
                                        <label style="display:block;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Brand</label>
                                        <select name="brand"
                                            style="width:100%;border:1px solid #e2e8f0;border-radius:7px;padding:6px 9px;font-size:0.8rem;color:#334155;background:#fff;box-sizing:border-box;">
                                            <option value="">— None —</option>
                                            @foreach(\App\Models\ActionPlanItem::BRANDS as $b)
                                            <option value="{{ $b }}" {{ $item->brand === $b ? 'selected' : '' }}>{{ $b }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Task *</label>
                                        <input type="text" name="title" value="{{ $item->title }}" required
                                            style="width:100%;border:1px solid #e2e8f0;border-radius:7px;padding:6px 9px;font-size:0.8rem;color:#334155;box-sizing:border-box;">
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Assigned To</label>
                                        <select name="assigned_user_id"
                                            style="width:100%;border:1px solid #e2e8f0;border-radius:7px;padding:6px 9px;font-size:0.8rem;color:#334155;background:#fff;box-sizing:border-box;">
                                            <option value="">— Unassigned —</option>
                                            @foreach($allUsers as $u)
                                            <option value="{{ $u->id }}" {{ $item->assigned_user_id === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr 2fr auto;gap:0.75rem;align-items:end;">
                                    <div>
                                        <label style="display:block;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Week Commencing</label>
                                        <input type="date" name="week_commencing" value="{{ $item->week_commencing?->format('Y-m-d') }}"
                                            style="width:100%;border:1px solid #e2e8f0;border-radius:7px;padding:6px 9px;font-size:0.8rem;color:#334155;box-sizing:border-box;">
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Status</label>
                                        <select name="status"
                                            style="width:100%;border:1px solid #e2e8f0;border-radius:7px;padding:6px 9px;font-size:0.8rem;color:#334155;background:#fff;box-sizing:border-box;">
                                            @foreach(\App\Models\ActionPlanItem::STATUSES as $val => $label)
                                            <option value="{{ $val }}" {{ $item->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Notes</label>
                                        <input type="text" name="notes" value="{{ $item->notes }}"
                                            style="width:100%;border:1px solid #e2e8f0;border-radius:7px;padding:6px 9px;font-size:0.8rem;color:#334155;box-sizing:border-box;">
                                    </div>
                                    <div style="display:flex;gap:0.5rem;">
                                        <button type="submit" style="padding:0.35rem 0.75rem;border-radius:7px;background:#0f172a;color:#fff;font-size:0.75rem;font-weight:600;border:none;cursor:pointer;white-space:nowrap;">Save</button>
                                        <button type="button" onclick="toggleEdit('edit-{{ $item->id }}')"
                                            style="padding:0.35rem 0.75rem;border-radius:7px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.75rem;cursor:pointer;white-space:nowrap;">Cancel</button>
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
        </div>{{-- end year-{{ $year }} --}}
    </div>
    @endforeach
    @endif

</main>

{{-- Copy Tasks Modal --}}
<div id="copy-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:flex-start;justify-content:center;background:rgba(0,0,0,0.45);padding:2rem 1rem;overflow-y:auto;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:700px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;">
            <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Copy Tasks Forward</h2>
            <button onclick="document.getElementById('copy-modal').style.display='none'" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.25rem;line-height:1;">&times;</button>
        </div>
        <div style="padding:1.25rem 1.5rem;">
            <form action="{{ route('action-plans.items.copy', $plan) }}" method="POST">
                @csrf
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Copy for how many months ahead?</label>
                    <select name="months"
                        style="border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;">
                        <option value="1">1 month</option>
                        <option value="2">2 months</option>
                        <option value="3" selected>3 months</option>
                        <option value="6">6 months</option>
                        <option value="12">12 months</option>
                    </select>
                </div>
                <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">Select tasks to copy</p>
                <div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;max-height:350px;overflow-y:auto;margin-bottom:1.25rem;">
                    <div style="padding:6px 12px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" id="select-all-copy" onchange="toggleAllCopy(this)" style="cursor:pointer;">
                        <label for="select-all-copy" style="font-size:0.75rem;font-weight:600;color:#475569;cursor:pointer;">Select all</label>
                    </div>
                    @foreach($plan->items as $item)
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:8px 12px;border-bottom:1px solid #f8fafc;cursor:pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                        <input type="checkbox" name="item_ids[]" value="{{ $item->id }}" style="margin-top:2px;cursor:pointer;">
                        <span style="flex:1;min-width:0;">
                            <span style="font-size:0.8125rem;font-weight:500;color:#1e293b;">{{ $item->title }}</span>
                            <span style="font-size:0.72rem;color:#94a3b8;display:block;">
                                @if($item->brand){{ $item->brand }} · @endif
                                {{ $item->week_commencing ? $item->week_commencing->format('d M Y') : 'No date' }}
                            </span>
                        </span>
                    </label>
                    @endforeach
                </div>
                <div style="display:flex;gap:0.5rem;">
                    <button type="submit" style="padding:0.45rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">Copy Selected Tasks</button>
                    <button type="button" onclick="document.getElementById('copy-modal').style.display='none'"
                        style="padding:0.45rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;cursor:pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleEdit(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
}
function toggleYear(id) {
    var el = document.getElementById(id);
    var chevron = document.getElementById('year-chevron-' + id.replace('year-', ''));
    if (!el) return;
    var hidden = el.style.display === 'none';
    el.style.display = hidden ? '' : 'none';
    if (chevron) chevron.style.transform = hidden ? '' : 'rotate(-90deg)';
}
function toggleAllCopy(cb) {
    document.querySelectorAll('#copy-modal input[name="item_ids[]"]').forEach(function(c) { c.checked = cb.checked; });
}
document.addEventListener('click', function(e) {
    var modal = document.getElementById('copy-modal');
    if (modal && e.target === modal) modal.style.display = 'none';
});
</script>

</x-layout>
