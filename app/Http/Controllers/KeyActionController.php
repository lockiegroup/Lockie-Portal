<?php

namespace App\Http\Controllers;

use App\Models\KeyActionComment;
use App\Models\KeyActionGroup;
use App\Models\KeyActionTask;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeyActionController extends Controller
{
    // ── Groups ────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $user   = auth()->user();
        $groups = $user->isMaster()
            ? KeyActionGroup::withCount('tasks')->with('members')->orderBy('name')->get()
            : KeyActionGroup::whereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->withCount('tasks')
                ->with('members')
                ->orderBy('name')
                ->get();

        $allUsers = $user->isMaster() ? User::where('is_active', true)->orderBy('name')->get() : collect();

        return view('key-actions.index', compact('groups', 'allUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isMaster(), 403);

        $data = $request->validate(['name' => 'required|string|max:100']);

        $group = KeyActionGroup::create([
            'name'       => $data['name'],
            'created_by' => auth()->id(),
        ]);

        // Creator is automatically a group admin
        $group->members()->attach(auth()->id(), ['role' => 'admin']);

        return redirect()->route('key-actions.show', $group);
    }

    public function show(KeyActionGroup $group): View
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->hasMember($user), 403);

        $group->load(['members' => fn($q) => $q->orderBy('name')]);

        $tasks = $group->tasks()
            ->with(['assignee', 'comments'])
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        $columns = $group->members->map(fn($member) => [
            'user'  => $member,
            'tasks' => $tasks->where('assigned_to', $member->id)->where('completed', false)->values(),
            'done'  => $tasks->where('assigned_to', $member->id)->where('completed', true)->values(),
        ]);

        $unassigned     = $tasks->whereNull('assigned_to')->where('completed', false)->values();
        $unassignedDone = $tasks->whereNull('assigned_to')->where('completed', true)->values();

        $isGroupAdmin = $user->isMaster() || $group->isAdmin($user);
        $allUsers     = $user->isMaster()
            ? User::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('key-actions.show', compact(
            'group', 'columns', 'unassigned', 'unassignedDone', 'isGroupAdmin', 'allUsers'
        ));
    }

    public function destroy(KeyActionGroup $group): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);

        $group->delete();
        return redirect()->route('key-actions.index');
    }

    // ── Members ───────────────────────────────────────────────────────────────

    public function addMember(Request $request, KeyActionGroup $group): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'in:admin,member',
        ]);

        $group->members()->syncWithoutDetaching([
            $data['user_id'] => ['role' => $data['role'] ?? 'member'],
        ]);

        $member = User::find($data['user_id']);
        return response()->json(['ok' => true, 'user' => ['id' => $member->id, 'name' => $member->name]]);
    }

    public function removeMember(KeyActionGroup $group, User $member): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);

        $group->members()->detach($member->id);
        return response()->json(['ok' => true]);
    }

    // ── Tasks ─────────────────────────────────────────────────────────────────

    public function showTask(KeyActionGroup $group, KeyActionTask $task): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->hasMember($user), 403);
        abort_unless($task->group_id === $group->id, 404);

        $task->load(['assignee', 'comments.user']);

        return response()->json([
            'task'     => $this->taskJson($task),
            'comments' => $task->comments->map(fn($c) => [
                'id'         => $c->id,
                'body'       => $c->body,
                'user_name'  => $c->user->name,
                'created_at' => $c->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function storeTask(Request $request, KeyActionGroup $group): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->hasMember($user), 403);

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'label'       => 'in:none,yellow,red,green',
            'due_date'    => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $maxOrder = $group->tasks()->max('sort_order') ?? 0;

        $task = $group->tasks()->create([
            'title'       => $data['title'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'label'       => $data['label'] ?? 'none',
            'due_date'    => $data['due_date'] ?? null,
            'description' => $data['description'] ?? null,
            'created_by'  => auth()->id(),
            'sort_order'  => $maxOrder + 1,
        ]);

        $task->load(['assignee', 'comments']);

        return response()->json(['ok' => true, 'task' => $this->taskJson($task)]);
    }

    public function updateTask(Request $request, KeyActionGroup $group, KeyActionTask $task): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->hasMember($user), 403);
        abort_unless($task->group_id === $group->id, 404);

        $data = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'label'       => 'sometimes|in:none,yellow,red,green',
            'due_date'    => 'sometimes|nullable|date',
            'description' => 'sometimes|nullable|string',
        ]);

        $task->update($data);
        $task->load(['assignee', 'comments']);

        return response()->json(['ok' => true, 'task' => $this->taskJson($task)]);
    }

    public function completeTask(KeyActionGroup $group, KeyActionTask $task): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->hasMember($user), 403);
        abort_unless($task->group_id === $group->id, 404);

        $task->update([
            'completed'    => !$task->completed,
            'completed_at' => $task->completed ? null : now(),
        ]);

        return response()->json(['ok' => true, 'completed' => $task->completed]);
    }

    public function destroyTask(KeyActionGroup $group, KeyActionTask $task): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->hasMember($user), 403);
        abort_unless($task->group_id === $group->id, 404);

        $task->delete();
        return response()->json(['ok' => true]);
    }

    public function reorderTasks(Request $request, KeyActionGroup $group): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->hasMember($user), 403);

        $data = $request->validate([
            'tasks'              => 'required|array',
            'tasks.*.id'         => 'required|integer',
            'tasks.*.sort_order' => 'required|integer',
            'tasks.*.assigned_to' => 'nullable|integer',
        ]);

        foreach ($data['tasks'] as $item) {
            KeyActionTask::where('id', $item['id'])->where('group_id', $group->id)->update([
                'sort_order'  => $item['sort_order'],
                'assigned_to' => $item['assigned_to'] ?? null,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    // ── Comments ──────────────────────────────────────────────────────────────

    public function storeComment(Request $request, KeyActionGroup $group, KeyActionTask $task): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->hasMember($user), 403);
        abort_unless($task->group_id === $group->id, 404);

        $data = $request->validate(['body' => 'required|string|max:2000']);

        $comment = $task->comments()->create([
            'user_id' => auth()->id(),
            'body'    => $data['body'],
        ]);

        $comment->load('user');

        return response()->json([
            'ok'      => true,
            'comment' => [
                'id'         => $comment->id,
                'body'       => $comment->body,
                'user_name'  => $comment->user->name,
                'created_at' => $comment->created_at->diffForHumans(),
            ],
        ]);
    }

    public function destroyComment(KeyActionGroup $group, KeyActionTask $task, KeyActionComment $comment): JsonResponse
    {
        $user = auth()->user();
        abort_unless($task->group_id === $group->id, 404);
        abort_unless($user->isMaster() || $comment->user_id === $user->id, 403);

        $comment->delete();
        return response()->json(['ok' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function taskJson(KeyActionTask $task): array
    {
        return [
            'id'            => $task->id,
            'title'         => $task->title,
            'description'   => $task->description,
            'label'         => $task->label,
            'due_date'      => $task->due_date?->toDateString(),
            'completed'     => $task->completed,
            'sort_order'    => $task->sort_order,
            'assigned_to'   => $task->assigned_to,
            'assignee_name' => $task->assignee?->name,
            'comment_count' => $task->comments->count(),
            'overdue'       => $task->isOverdue(),
        ];
    }
}
