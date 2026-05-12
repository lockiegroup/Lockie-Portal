<x-layout title="{{ $group->name }} — Key Actions">
<style>
.board { display:flex; gap:1rem; overflow-x:auto; padding:0 1.5rem 2rem; align-items:flex-start; }
.col { flex:0 0 280px; background:#f8fafc; border-radius:0.75rem; padding:0.75rem; }
.col.col-ghost { opacity:0.35; background:#e0e7ff; }
.col.col-drag  { box-shadow:0 8px 24px rgba(0,0,0,0.18); cursor:grabbing; }
.col-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem; }
.col-title { font-size:0.8125rem; font-weight:700; color:#1e293b; }
.col-drag-handle { cursor:grab; color:#d1d5db; padding:2px 4px; font-size:1rem; line-height:1; user-select:none; flex-shrink:0; }
.col-drag-handle:hover { color:#94a3b8; }
.task-card { background:#fff; border-radius:0.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.07); padding:0.75rem; margin-bottom:0.5rem; cursor:grab; border-left:3px solid transparent; transition:box-shadow 0.15s; }
.task-card:hover { box-shadow:0 3px 8px rgba(0,0,0,0.12); }
.task-card.sortable-ghost { opacity:0.35; background:#e0e7ff; }
.task-card.sortable-drag  { cursor:grabbing; box-shadow:0 8px 24px rgba(0,0,0,0.18); }
.task-list { min-height:40px; }
.task-card.label-yellow { border-left-color:#f59e0b; }
.task-card.label-red    { border-left-color:#ef4444; }
.task-card.label-green  { border-left-color:#22c55e; }
.task-title { font-size:0.8125rem; font-weight:600; color:#1e293b; margin:0 0 0.35rem; }
.task-meta  { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.badge { font-size:0.65rem; font-weight:700; border-radius:9999px; padding:1px 7px; }
.due-ok  { background:#f1f5f9; color:#64748b; }
.due-bad { background:#fee2e2; color:#991b1b; }
.add-task-btn { width:100%; background:none; border:1px dashed #cbd5e1; border-radius:0.5rem; padding:0.5rem; font-size:0.8rem; color:#94a3b8; cursor:pointer; margin-top:0.25rem; }
.add-task-btn:hover { background:#f1f5f9; color:#475569; }
.done-toggle { font-size:0.75rem; color:#94a3b8; cursor:pointer; user-select:none; margin-top:0.5rem; padding:0.25rem 0; }
.panel-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:900; }
.panel { position:fixed; top:0; right:0; height:100%; width:420px; max-width:100%; background:#fff; box-shadow:-4px 0 24px rgba(0,0,0,0.12); z-index:901; overflow-y:auto; padding:1.5rem; box-sizing:border-box; }
@media(max-width:500px){ .panel { width:100%; } }
</style>

<div style="padding:1.25rem 1.5rem 0.75rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;max-width:100%;">
    <div style="display:flex;align-items:center;gap:0.75rem;">
        <a href="{{ route('key-actions.index') }}" style="font-size:0.8125rem;color:#64748b;text-decoration:none;">← Key Actions</a>
        <span style="color:#cbd5e1;">|</span>
        <h1 style="font-size:1.1rem;font-weight:700;color:#1e293b;margin:0;">{{ $group->name }}</h1>
    </div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        @if($isGroupAdmin)
        <button onclick="openManageMembers()"
                style="background:#f1f5f9;color:#475569;border:none;border-radius:0.5rem;padding:0.4rem 0.875rem;font-size:0.8125rem;font-weight:600;cursor:pointer;">
            Manage Group
        </button>
        @endif
        <button onclick="openAddTask(null, null)"
                style="background:#1e293b;color:#fff;border:none;border-radius:0.5rem;padding:0.4rem 0.875rem;font-size:0.8125rem;font-weight:600;cursor:pointer;">
            + Add Task
        </button>
    </div>
</div>

<div class="board" id="board">
    {{-- All columns (members + buckets) in saved order --}}
    @foreach($allColumns as $col)
    @if($col['type'] === 'user')
    <div class="col" data-col-type="user" data-col-id="{{ $col['user']->id }}">
        <div class="col-header">
            <span class="col-drag-handle" title="Drag to reorder">⠿</span>
            <span class="col-title">{{ $col['user']->name }}</span>
            <span style="background:#e2e8f0;color:#64748b;border-radius:9999px;padding:1px 8px;font-size:0.7rem;font-weight:700;">{{ $col['tasks']->count() }}</span>
        </div>
        <div class="task-list" id="list-{{ $col['user']->id }}">
            @foreach($col['tasks'] as $task)
                @include('key-actions._task', ['task' => $task])
            @endforeach
        </div>
        <button class="add-task-btn" onclick="openAddTask({{ $col['user']->id }}, null)">+ Add task</button>
        @if($col['done']->count())
        <div class="done-toggle" onclick="toggleDone('u{{ $col['user']->id }}')">▸ Completed ({{ $col['done']->count() }})</div>
        <div id="done-u{{ $col['user']->id }}" style="display:none;">
            @foreach($col['done'] as $task)
                @include('key-actions._task', ['task' => $task])
            @endforeach
        </div>
        @endif
    </div>
    @else
    <div class="col" data-col-type="bucket" data-col-id="{{ $col['bucket']->id }}">
        <div class="col-header">
            <span class="col-drag-handle" title="Drag to reorder">⠿</span>
            <span class="col-title">{{ $col['bucket']->name }}</span>
            <div style="display:flex;align-items:center;gap:0.4rem;">
                <span style="background:#e2e8f0;color:#64748b;border-radius:9999px;padding:1px 8px;font-size:0.7rem;font-weight:700;">{{ $col['tasks']->count() }}</span>
                @if($isGroupAdmin)
                <button onclick="renameBucket({{ $col['bucket']->id }}, '{{ addslashes($col['bucket']->name) }}')"
                        title="Rename" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;font-size:0.75rem;line-height:1;">✎</button>
                <button onclick="deleteBucket({{ $col['bucket']->id }})"
                        title="Delete" style="background:none;border:none;cursor:pointer;color:#fca5a5;padding:0;font-size:0.75rem;line-height:1;">✕</button>
                @endif
            </div>
        </div>
        <div class="task-list" id="list-b{{ $col['bucket']->id }}">
            @foreach($col['tasks'] as $task)
                @include('key-actions._task', ['task' => $task])
            @endforeach
        </div>
        <button class="add-task-btn" onclick="openAddTask(null, {{ $col['bucket']->id }})">+ Add task</button>
        @if($col['done']->count())
        <div class="done-toggle" onclick="toggleDone('b{{ $col['bucket']->id }}')">▸ Completed ({{ $col['done']->count() }})</div>
        <div id="done-b{{ $col['bucket']->id }}" style="display:none;">
            @foreach($col['done'] as $task)
                @include('key-actions._task', ['task' => $task])
            @endforeach
        </div>
        @endif
    </div>
    @endif
    @endforeach

    {{-- Add column button --}}
    @if($isGroupAdmin)
    <div class="col-add-btn" style="flex:0 0 200px;display:flex;align-items:flex-start;padding-top:0.5rem;">
        <button onclick="openAddBucket()"
                style="width:100%;background:rgba(255,255,255,0.6);border:1px dashed #cbd5e1;border-radius:0.75rem;padding:0.75rem;font-size:0.8rem;color:#94a3b8;cursor:pointer;font-weight:600;">
            + Add Column
        </button>
    </div>
    @endif
</div>

{{-- Task Panel --}}
<div class="panel-overlay" id="panel-overlay" onclick="closePanel()"></div>
<div class="panel" id="task-panel" style="display:none;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;gap:0.5rem;">
        <div id="panel-title-display" style="font-size:1rem;font-weight:700;color:#1e293b;flex:1;cursor:pointer;" onclick="editTitle()"></div>
        <button onclick="closePanel()" style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:#94a3b8;flex-shrink:0;">✕</button>
    </div>
    <input id="panel-title-input" type="text" style="display:none;width:100%;border:1px solid #6366f1;border-radius:0.375rem;padding:0.375rem 0.5rem;font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:1rem;box-sizing:border-box;" onblur="saveTitle()" onkeydown="if(event.key==='Enter')saveTitle()">

    <div style="display:grid;gap:0.75rem;margin-bottom:1.25rem;">
        <div>
            <label style="font-size:0.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:3px;">Assigned To</label>
            <select id="panel-assignee" onchange="saveField('assigned_to', this.value || null)"
                    style="width:100%;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.375rem 0.5rem;font-size:0.8125rem;">
                <option value="">Unassigned</option>
                @foreach($group->members as $m)
                <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
            <div>
                <label style="font-size:0.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:3px;">Label</label>
                <select id="panel-label" onchange="saveField('label', this.value)"
                        style="width:100%;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.375rem 0.5rem;font-size:0.8125rem;">
                    <option value="none">None</option>
                    <option value="yellow">🟡 Yellow</option>
                    <option value="red">🔴 Red</option>
                    <option value="green">🟢 Green</option>
                </select>
            </div>
            <div>
                <label style="font-size:0.75rem;font-weight:600;color:#64748b;display:block;margin-bottom:3px;">Due Date</label>
                <input type="date" id="panel-due" onchange="saveField('due_date', this.value || null)"
                       style="width:100%;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.375rem 0.5rem;font-size:0.8125rem;box-sizing:border-box;">
            </div>
        </div>

    </div>

    <div style="display:flex;gap:0.5rem;margin-bottom:1.25rem;">
        <button id="panel-complete-btn" onclick="toggleComplete()"
                style="flex:1;border:none;border-radius:0.5rem;padding:0.5rem;font-size:0.8125rem;font-weight:600;cursor:pointer;background:#dcfce7;color:#166534;">
            ✓ Mark Complete
        </button>
        <button onclick="deleteTask()"
                style="background:#fee2e2;color:#991b1b;border:none;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.8125rem;font-weight:600;cursor:pointer;">
            Delete
        </button>
    </div>

    <div style="border-top:1px solid #f1f5f9;padding-top:1rem;">
        <p style="font-size:0.75rem;font-weight:700;color:#64748b;margin:0 0 0.75rem;text-transform:uppercase;letter-spacing:0.05em;">Comments</p>
        <div id="comments-list" style="display:flex;flex-direction:column;gap:0.5rem;margin-bottom:0.75rem;"></div>
        <div style="display:flex;gap:0.5rem;">
            <input type="text" id="comment-input" placeholder="Add a comment…"
                   style="flex:1;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.375rem 0.5rem;font-size:0.8125rem;"
                   onkeydown="if(event.key==='Enter')submitComment()">
            <button onclick="submitComment()"
                    style="background:#1e293b;color:#fff;border:none;border-radius:0.375rem;padding:0.375rem 0.75rem;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                Send
            </button>
        </div>
    </div>
</div>

{{-- Add Task Modal --}}
<div id="add-task-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:0.75rem;padding:1.5rem;width:100%;max-width:440px;margin:1rem;box-sizing:border-box;">
        <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">New Task</h2>
        <div style="display:grid;gap:0.75rem;">
            <div>
                <label style="font-size:0.8rem;font-weight:600;color:#374151;display:block;margin-bottom:3px;">Title *</label>
                <input id="new-task-title" type="text" placeholder="Task title" required
                       style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;box-sizing:border-box;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                <div>
                    <label style="font-size:0.8rem;font-weight:600;color:#374151;display:block;margin-bottom:3px;">Assign To</label>
                    <select id="new-task-assignee" style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.5rem;font-size:0.8125rem;">
                        <option value="">Unassigned</option>
                        @foreach($group->members as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem;font-weight:600;color:#374151;display:block;margin-bottom:3px;">Label</label>
                    <select id="new-task-label" style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.5rem;font-size:0.8125rem;">
                        <option value="none">None</option>
                        <option value="yellow">🟡 Yellow</option>
                        <option value="red">🔴 Red</option>
                        <option value="green">🟢 Green</option>
                    </select>
                </div>
            </div>

        </div>
        <div style="display:flex;gap:0.5rem;justify-content:flex-end;margin-top:1rem;">
            <button onclick="closeAddTask()"
                    style="background:#f1f5f9;color:#475569;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                Cancel
            </button>
            <button onclick="submitNewTask()"
                    style="background:#1e293b;color:#fff;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                Create
            </button>
        </div>
    </div>
</div>

{{-- Manage Members Modal --}}
@if($isGroupAdmin)
<div id="members-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:0.75rem;padding:1.5rem;width:100%;max-width:440px;margin:1rem;box-sizing:border-box;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Manage Group</h2>
            <button onclick="document.getElementById('members-modal').style.display='none'"
                    style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:#94a3b8;">✕</button>
        </div>

        <div style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #f1f5f9;">
            <p style="font-size:0.8rem;font-weight:600;color:#374151;margin:0 0 0.5rem;">Group Name</p>
            <div style="display:flex;gap:0.5rem;">
                <input id="group-name-input" type="text" value="{{ $group->name }}"
                       style="flex:1;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.375rem 0.5rem;font-size:0.8125rem;">
                <button onclick="renameGroup()"
                        style="background:#1e293b;color:#fff;border:none;border-radius:0.5rem;padding:0.375rem 0.75rem;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                    Save
                </button>
            </div>
        </div>

        <div id="members-list" style="margin-bottom:1rem;">
            @foreach($group->members as $m)
            <div id="member-row-{{ $m->id }}" style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;">
                <span style="font-size:0.875rem;color:#1e293b;">{{ $m->name }}</span>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:0.7rem;color:#94a3b8;">{{ $m->pivot->role }}</span>
                    @if($m->id !== auth()->id())
                    <button onclick="removeMember({{ $m->id }})"
                            style="background:#fee2e2;color:#991b1b;border:none;border-radius:0.375rem;padding:2px 8px;font-size:0.75rem;cursor:pointer;">Remove</button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        @if(auth()->user()->isMaster() && $allUsers->isNotEmpty())
        <div style="border-top:1px solid #f1f5f9;padding-top:0.75rem;">
            <p style="font-size:0.8rem;font-weight:600;color:#374151;margin:0 0 0.5rem;">Add Member</p>
            <div style="display:flex;gap:0.5rem;">
                <select id="add-member-user" style="flex:1;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.375rem 0.5rem;font-size:0.8125rem;">
                    <option value="">Select user…</option>
                    @foreach($allUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                <select id="add-member-role" style="border:1px solid #d1d5db;border-radius:0.5rem;padding:0.375rem 0.5rem;font-size:0.8125rem;">
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                </select>
                <button onclick="addMember()"
                        style="background:#1e293b;color:#fff;border:none;border-radius:0.5rem;padding:0.375rem 0.75rem;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                    Add
                </button>
            </div>
        </div>
        @endif

        @if($isGroupAdmin)
        <div style="border-top:1px solid #f1f5f9;padding-top:0.75rem;margin-top:0.75rem;">
            <button onclick="deleteGroup()"
                    style="background:#fee2e2;color:#991b1b;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.8125rem;font-weight:600;cursor:pointer;width:100%;">
                Delete Group
            </button>
        </div>
        @endif
    </div>
</div>
@endif

{{-- Add Column Modal --}}
@if($isGroupAdmin)
<div id="add-bucket-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:0.75rem;padding:1.5rem;width:100%;max-width:380px;margin:1rem;box-sizing:border-box;">
        <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">New Column</h2>
        <input id="new-bucket-name" type="text" placeholder="Column name (e.g. Notes, Backlog)"
               style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;box-sizing:border-box;margin-bottom:1rem;"
               onkeydown="if(event.key==='Enter')submitNewBucket()">
        <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
            <button onclick="document.getElementById('add-bucket-modal').style.display='none'"
                    style="background:#f1f5f9;color:#475569;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                Cancel
            </button>
            <button onclick="submitNewBucket()"
                    style="background:#1e293b;color:#fff;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                Create
            </button>
        </div>
    </div>
</div>
@endif

<script>
const csrf       = '{{ csrf_token() }}';
const groupId    = {{ $group->id }};
const baseUrl    = `/key-actions/${groupId}`;

let activeTaskId = null;
let activeTask   = null;

// ── Panel ────────────────────────────────────────────────────────────────────

function openPanel(taskId) {
    fetch(`/key-actions/${groupId}/tasks/${taskId}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
    }).then(r => r.json()).then(data => {
        activeTaskId = taskId;
        activeTask   = data.task;
        populatePanel(data.task, data.comments);
        document.getElementById('panel-overlay').style.display = 'block';
        document.getElementById('task-panel').style.display    = 'block';
    });
}

function populatePanel(task, comments) {
    document.getElementById('panel-title-display').textContent = task.title;
    document.getElementById('panel-title-input').value         = task.title;
    document.getElementById('panel-assignee').value            = task.assigned_to ?? '';
    document.getElementById('panel-label').value               = task.label;

    const btn = document.getElementById('panel-complete-btn');
    if (task.completed) {
        btn.textContent = '↩ Reopen Task';
        btn.style.background = '#fef9c3';
        btn.style.color = '#854d0e';
    } else {
        btn.textContent = '✓ Mark Complete';
        btn.style.background = '#dcfce7';
        btn.style.color = '#166534';
    }

    renderComments(comments ?? []);
}

function closePanel() {
    document.getElementById('panel-overlay').style.display = 'none';
    document.getElementById('task-panel').style.display    = 'none';
    document.getElementById('panel-title-input').style.display   = 'none';
    document.getElementById('panel-title-display').style.display = 'block';
    activeTaskId = null;
}

function editTitle() {
    document.getElementById('panel-title-display').style.display = 'none';
    const inp = document.getElementById('panel-title-input');
    inp.style.display = 'block';
    inp.focus();
    inp.select();
}

function saveTitle() {
    const val = document.getElementById('panel-title-input').value.trim();
    if (!val || val === activeTask?.title) {
        document.getElementById('panel-title-input').style.display   = 'none';
        document.getElementById('panel-title-display').style.display = 'block';
        return;
    }
    patchTask({ title: val }).then(task => {
        document.getElementById('panel-title-display').textContent  = task.title;
        document.getElementById('panel-title-input').style.display  = 'none';
        document.getElementById('panel-title-display').style.display= 'block';
        updateCardDOM(task);
    });
}

function saveField(field, value) {
    patchTask({ [field]: value }).then(task => updateCardDOM(task));
}

async function patchTask(data) {
    const res  = await fetch(`${baseUrl}/tasks/${activeTaskId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(data),
    });
    const json = await res.json();
    if (json.ok) { activeTask = json.task; return json.task; }
}

async function toggleComplete() {
    const res  = await fetch(`${baseUrl}/tasks/${activeTaskId}/complete`, {
        method: 'PATCH',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    const json = await res.json();
    if (json.ok) location.reload();
}

async function deleteTask() {
    if (!confirm('Delete this task?')) return;
    await fetch(`${baseUrl}/tasks/${activeTaskId}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    closePanel();
    document.getElementById('task-' + activeTaskId)?.remove();
}

// ── Comments ─────────────────────────────────────────────────────────────────

function renderComments(comments) {
    const el = document.getElementById('comments-list');
    el.innerHTML = '';
    comments.forEach(c => el.appendChild(buildComment(c)));
}

function buildComment(c) {
    const div = document.createElement('div');
    div.style = 'background:#f8fafc;border-radius:0.5rem;padding:0.5rem 0.75rem;';
    div.innerHTML = `<p style="font-size:0.7rem;font-weight:700;color:#64748b;margin:0 0 2px;">${c.user_name} · ${c.created_at}</p>
                     <p style="font-size:0.8125rem;color:#1e293b;margin:0;">${c.body}</p>`;
    return div;
}

async function submitComment() {
    const inp  = document.getElementById('comment-input');
    const body = inp.value.trim();
    if (!body) return;
    const res  = await fetch(`${baseUrl}/tasks/${activeTaskId}/comments`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ body }),
    });
    const json = await res.json();
    if (json.ok) {
        inp.value = '';
        document.getElementById('comments-list').appendChild(buildComment(json.comment));
        // update comment count badge on card
        const card = document.getElementById('task-' + activeTaskId);
        if (card) {
            const badge = card.querySelector('.comment-badge');
            if (badge) badge.textContent = '💬 ' + ((parseInt(badge.dataset.count || 0) + 1));
        }
    }
}

// ── Add Task ─────────────────────────────────────────────────────────────────

let addTaskAssignee  = null;
let addTaskBucketId  = null;

function openAddTask(userId, bucketId) {
    addTaskAssignee = userId;
    addTaskBucketId = bucketId;
    document.getElementById('new-task-title').value    = '';
    document.getElementById('new-task-assignee').value = userId ?? '';
    document.getElementById('new-task-label').value    = 'none';
    document.getElementById('add-task-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('new-task-title').focus(), 50);
}

function closeAddTask() {
    document.getElementById('add-task-modal').style.display = 'none';
}

async function submitNewTask() {
    const title = document.getElementById('new-task-title').value.trim();
    if (!title) return;

    const payload = {
        title,
        assigned_to: addTaskBucketId ? null : (document.getElementById('new-task-assignee').value || null),
        bucket_id:   addTaskBucketId ?? null,
        label:       document.getElementById('new-task-label').value,
    };

    const res  = await fetch(`${baseUrl}/tasks`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(payload),
    });
    const json = await res.json();
    if (json.ok) {
        closeAddTask();
        location.reload();
    }
}

// ── Members ───────────────────────────────────────────────────────────────────

function openManageMembers() {
    document.getElementById('members-modal').style.display = 'flex';
}

async function addMember() {
    const userId = document.getElementById('add-member-user').value;
    const role   = document.getElementById('add-member-role').value;
    if (!userId) return;

    const res  = await fetch(`${baseUrl}/members`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ user_id: userId, role }),
    });
    const json = await res.json();
    if (json.ok) location.reload();
}

async function removeMember(userId) {
    if (!confirm('Remove this member?')) return;
    const res  = await fetch(`${baseUrl}/members/${userId}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    const json = await res.json();
    if (json.ok) document.getElementById('member-row-' + userId)?.remove();
}

async function deleteGroup() {
    if (!confirm('Delete this entire group and all its tasks?')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${baseUrl}`;
    form.innerHTML = `<input name="_token" value="${csrf}"><input name="_method" value="DELETE">`;
    document.body.appendChild(form);
    form.submit();
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function toggleDone(colId) {
    const el = document.getElementById('done-' + colId);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function updateCardDOM(task) {
    const card = document.getElementById('task-' + task.id);
    if (!card) return;
    card.className = 'task-card' + (task.label !== 'none' ? ' label-' + task.label : '');
    card.querySelector('.task-title').textContent = task.title;
    const meta = card.querySelector('.task-meta');
    meta.innerHTML = buildMetaHTML(task);
}

function buildMetaHTML(task) {
    let html = '';
    if (task.comment_count > 0) {
        html += `<span class="badge due-ok comment-badge" data-count="${task.comment_count}">💬 ${task.comment_count}</span>`;
    }
    return html;
}

async function renameGroup() {
    const name = document.getElementById('group-name-input').value.trim();
    if (!name) return;
    const res  = await fetch(`${baseUrl}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ name }),
    });
    const json = await res.json();
    if (json.ok) {
        document.querySelector('h1').textContent = json.name;
        document.getElementById('members-modal').style.display = 'none';
    }
}

async function quickComplete(taskId, event) {
    event.stopPropagation();
    const res  = await fetch(`${baseUrl}/tasks/${taskId}/complete`, {
        method: 'PATCH',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    const json = await res.json();
    if (json.ok) location.reload();
}

// ── Buckets ───────────────────────────────────────────────────────────────────

function openAddBucket() {
    document.getElementById('new-bucket-name').value = '';
    document.getElementById('add-bucket-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('new-bucket-name').focus(), 50);
}

async function submitNewBucket() {
    const name = document.getElementById('new-bucket-name').value.trim();
    if (!name) return;
    const res  = await fetch(`${baseUrl}/buckets`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ name }),
    });
    const json = await res.json();
    if (json.ok) location.reload();
}

async function renameBucket(bucketId, currentName) {
    const name = prompt('Rename column:', currentName);
    if (!name || name === currentName) return;
    const res  = await fetch(`${baseUrl}/buckets/${bucketId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ name }),
    });
    const json = await res.json();
    if (json.ok) location.reload();
}

async function deleteBucket(bucketId) {
    if (!confirm('Delete this column? Tasks inside will become unassigned.')) return;
    const res  = await fetch(`${baseUrl}/buckets/${bucketId}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    const json = await res.json();
    if (json.ok) location.reload();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
// ── Task drag-and-drop ────────────────────────────────────────────────────────
document.querySelectorAll('.task-list').forEach(list => {
    Sortable.create(list, {
        group:      'tasks',
        animation:  150,
        ghostClass: 'sortable-ghost',
        dragClass:  'sortable-drag',
        onStart() { window._dragging = true; },
        onEnd()    { setTimeout(() => { window._dragging = false; }, 50); saveTaskOrder(); },
    });
});

async function saveTaskOrder() {
    const tasks = [];
    let order   = 0;
    document.querySelectorAll('.col[data-col-type]').forEach(col => {
        const colType = col.dataset.colType;
        const colId   = col.dataset.colId ? parseInt(col.dataset.colId) : null;
        col.querySelectorAll('.task-list .task-card').forEach(card => {
            tasks.push({
                id:         parseInt(card.id.replace('task-', '')),
                sort_order: order++,
                col_type:   colType,
                col_id:     colId,
            });
        });
    });
    await fetch(`${baseUrl}/tasks/reorder`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body:    JSON.stringify({ tasks }),
    });
}

// ── Column drag-and-drop ──────────────────────────────────────────────────────
Sortable.create(document.getElementById('board'), {
    animation:  150,
    handle:     '.col-drag-handle',
    filter:     '.col-add-btn',
    ghostClass: 'col-ghost',
    dragClass:  'col-drag',
    onEnd() { saveColumnOrder(); },
});

async function saveColumnOrder() {
    const columns = [];
    document.querySelectorAll('#board .col[data-col-type]').forEach(col => {
        columns.push({ type: col.dataset.colType, id: parseInt(col.dataset.colId) });
    });
    await fetch(`${baseUrl}/columns/reorder`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body:    JSON.stringify({ columns }),
    });
}

// Suppress panel open when the pointer was a drag not a click
document.querySelectorAll('.task-list').forEach(list => {
    list.addEventListener('click', e => {
        if (window._dragging) e.stopImmediatePropagation();
    }, true);
});
</script>

</x-layout>
