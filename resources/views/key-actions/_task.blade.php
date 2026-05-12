<div id="task-{{ $task->id }}"
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
    @if($task->comments->count() > 0)
    <div class="task-meta" style="margin-top:0.35rem;padding-left:1.25rem;">
        <span class="badge due-ok comment-badge" data-count="{{ $task->comments->count() }}">💬 {{ $task->comments->count() }}</span>
    </div>
    @endif
</div>
