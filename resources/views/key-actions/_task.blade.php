@php
$taskMembers = $task->relationLoaded('members') ? $task->members : collect();
@endphp
<div id="task-{{ $task->id }}"
     data-task-id="{{ $task->id }}"
     class="task-card{{ $task->label !== 'none' ? ' label-'.$task->label : '' }}{{ $task->completed ? ' opacity-60' : '' }}"
     onclick="openPanel({{ $task->id }})"
     style="{{ $task->completed ? 'opacity:0.55;' : '' }}">
    <div style="display:flex;align-items:flex-start;gap:0.5rem;">
        <button onclick="quickComplete({{ $task->id }}, event)"
                title="{{ $task->completed ? 'Reopen' : 'Mark complete' }}"
                style="flex-shrink:0;margin-top:1px;width:16px;height:16px;border-radius:50%;border:2px solid {{ $task->completed ? '#22c55e' : '#cbd5e1' }};background:{{ $task->completed ? '#22c55e' : 'transparent' }};cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">
            @if($task->completed)
            <svg style="width:9px;height:9px;color:#fff;" viewBox="0 0 12 12" fill="none" stroke="white" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg>
            @endif
        </button>
        <p class="task-title" style="margin:0;flex:1;{{ $task->completed ? 'text-decoration:line-through;' : '' }}">{{ $task->title }}</p>
    </div>
    @if($taskMembers->count() > 0 || $task->comments->count() > 0)
    <div class="task-meta" style="margin-top:0.4rem;padding-left:1.25rem;display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
        @if($task->comments->count() > 0)
        <span class="badge due-ok comment-badge" data-count="{{ $task->comments->count() }}">💬 {{ $task->comments->count() }}</span>
        @endif
        @foreach($taskMembers as $member)
        @php
            $parts = preg_split('/\s+/', trim($member->name));
            $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
        @endphp
        <span title="{{ $member->name }}"
              style="background:#dbeafe;color:#1d4ed8;border-radius:9999px;padding:1px 6px;font-size:0.65rem;font-weight:700;line-height:1.4;">{{ $initials }}</span>
        @endforeach
    </div>
    @endif
</div>
