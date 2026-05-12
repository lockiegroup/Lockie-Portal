<div id="task-{{ $task->id }}"
     class="task-card{{ $task->label !== 'none' ? ' label-'.$task->label : '' }}{{ $task->completed ? ' opacity-60' : '' }}"
     onclick="openPanel({{ $task->id }})"
     style="{{ $task->completed ? 'opacity:0.55;' : '' }}">
    <p class="task-title" style="{{ $task->completed ? 'text-decoration:line-through;' : '' }}">{{ $task->title }}</p>
    <div class="task-meta">
        @if($task->due_date)
        <span class="badge {{ $task->isOverdue() ? 'due-bad' : 'due-ok' }}">📅 {{ $task->due_date->format('d M') }}</span>
        @endif
        @if($task->comments->count() > 0)
        <span class="badge due-ok comment-badge" data-count="{{ $task->comments->count() }}">💬 {{ $task->comments->count() }}</span>
        @endif
    </div>
</div>
