<?php

namespace App\Http\Controllers;

use App\Models\KeyActionBucket;
use App\Models\KeyActionComment;
use App\Models\KeyActionGroup;
use App\Models\KeyActionTask;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        $buckets = $group->buckets()->get();

        $memberCols = $group->members->map(fn($member) => [
            'type'   => 'user',
            'id'     => $member->id,
            'user'   => $member,
            'tasks'  => $tasks->where('assigned_to', $member->id)->whereNull('bucket_id')->where('completed', false)->values(),
            'done'   => $tasks->where('assigned_to', $member->id)->whereNull('bucket_id')->where('completed', true)->values(),
        ]);

        $bucketCols = $buckets->map(fn($bucket) => [
            'type'   => 'bucket',
            'id'     => $bucket->id,
            'bucket' => $bucket,
            'tasks'  => $tasks->where('bucket_id', $bucket->id)->where('completed', false)->values(),
            'done'   => $tasks->where('bucket_id', $bucket->id)->where('completed', true)->values(),
        ]);

        $allRaw = $memberCols->concat($bucketCols);

        if ($group->column_order) {
            $ordered = collect();
            foreach ($group->column_order as $entry) {
                $found = $allRaw->first(fn($c) => $c['type'] === $entry['type'] && (int)$c['id'] === (int)$entry['id']);
                if ($found) $ordered->push($found);
            }
            // Append columns added after the order was last saved
            $allRaw->each(function ($col) use ($ordered) {
                if (!$ordered->contains(fn($c) => $c['type'] === $col['type'] && $c['id'] === $col['id'])) {
                    $ordered->push($col);
                }
            });
            $allColumns = $ordered;
        } else {
            $allColumns = $allRaw;
        }

        $isGroupAdmin = $user->isMaster() || $group->isAdmin($user);
        $allUsers     = $user->isMaster()
            ? User::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('key-actions.show', compact(
            'group', 'allColumns', 'isGroupAdmin', 'allUsers'
        ));
    }

    public function update(Request $request, KeyActionGroup $group): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);

        $data = $request->validate(['name' => 'required|string|max:100']);
        $group->update($data);

        return response()->json(['ok' => true, 'name' => $group->name]);
    }

    public function destroy(KeyActionGroup $group): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);

        $group->delete();
        return redirect()->route('key-actions.index');
    }

    // ── Agenda ───────────────────────────────────────────────────────────────

    public function uploadAgenda(Request $request, KeyActionGroup $group): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);

        $request->validate([
            'agenda' => 'required|file|mimes:pdf,doc,docx|max:20480',
        ]);

        // Delete old file if present
        if ($group->agenda_path && Storage::exists($group->agenda_path)) {
            Storage::delete($group->agenda_path);
        }

        $file     = $request->file('agenda');
        $ext      = $file->getClientOriginalExtension();
        $path     = $file->storeAs('key-action-agendas', "group_{$group->id}.{$ext}");
        $origName = $file->getClientOriginalName();

        $group->update(['agenda_path' => $path, 'agenda_original_name' => $origName]);

        return response()->json(['ok' => true, 'name' => $origName]);
    }

    public function downloadAgenda(KeyActionGroup $group): BinaryFileResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->hasMember($user), 403);
        abort_unless($group->agenda_path && Storage::exists($group->agenda_path), 404);

        return response()->file(
            Storage::path($group->agenda_path),
            ['Content-Disposition' => 'inline; filename="' . $group->agenda_original_name . '"']
        );
    }

    public function deleteAgenda(KeyActionGroup $group): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);

        if ($group->agenda_path && Storage::exists($group->agenda_path)) {
            Storage::delete($group->agenda_path);
        }

        $group->update(['agenda_path' => null, 'agenda_original_name' => null]);

        return response()->json(['ok' => true]);
    }

    // ── Buckets ───────────────────────────────────────────────────────────────

    public function storeBucket(Request $request, KeyActionGroup $group): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);

        $data = $request->validate(['name' => 'required|string|max:100']);

        $maxOrder = $group->buckets()->max('sort_order') ?? 0;
        $bucket   = $group->buckets()->create([
            'name'       => $data['name'],
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json(['ok' => true, 'bucket' => ['id' => $bucket->id, 'name' => $bucket->name]]);
    }

    public function updateBucket(Request $request, KeyActionGroup $group, KeyActionBucket $bucket): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);
        abort_unless($bucket->group_id === $group->id, 404);

        $data = $request->validate(['name' => 'required|string|max:100']);
        $bucket->update($data);

        return response()->json(['ok' => true, 'name' => $bucket->name]);
    }

    public function destroyBucket(KeyActionGroup $group, KeyActionBucket $bucket): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);
        abort_unless($bucket->group_id === $group->id, 404);

        $bucket->delete();
        return response()->json(['ok' => true]);
    }

    public function reorderColumns(Request $request, KeyActionGroup $group): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);

        $data = $request->validate([
            'columns'        => 'required|array',
            'columns.*.type' => 'required|in:user,bucket',
            'columns.*.id'   => 'required|integer',
        ]);

        $group->update(['column_order' => $data['columns']]);
        return response()->json(['ok' => true]);
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

    public function updateMember(Request $request, KeyActionGroup $group, User $member): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isMaster() || $group->isAdmin($user), 403);

        $data = $request->validate(['role' => 'required|in:admin,member']);
        $group->members()->updateExistingPivot($member->id, ['role' => $data['role']]);

        return response()->json(['ok' => true, 'role' => $data['role']]);
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
            'bucket_id'   => 'nullable|exists:key_action_buckets,id',
            'label'       => 'in:none,yellow,red,green',
        ]);

        $maxOrder = $group->tasks()->max('sort_order') ?? 0;

        $task = $group->tasks()->create([
            'title'       => $data['title'],
            'assigned_to' => $data['bucket_id'] ? null : ($data['assigned_to'] ?? null),
            'bucket_id'   => $data['bucket_id'] ?? null,
            'label'       => $data['label'] ?? 'none',
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
            'tasks'               => 'required|array',
            'tasks.*.id'          => 'required|integer',
            'tasks.*.sort_order'  => 'required|integer',
            'tasks.*.col_type'    => 'required|in:unassigned,user,bucket',
            'tasks.*.col_id'      => 'nullable|integer',
        ]);

        foreach ($data['tasks'] as $item) {
            $update = ['sort_order' => $item['sort_order']];
            if ($item['col_type'] === 'user') {
                $update['assigned_to'] = $item['col_id'];
                $update['bucket_id']   = null;
            } elseif ($item['col_type'] === 'bucket') {
                $update['bucket_id']   = $item['col_id'];
                $update['assigned_to'] = null;
            } else {
                $update['assigned_to'] = null;
                $update['bucket_id']   = null;
            }
            KeyActionTask::where('id', $item['id'])->where('group_id', $group->id)->update($update);
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
            'label'         => $task->label,
            'completed'     => $task->completed,
            'sort_order'    => $task->sort_order,
            'assigned_to'   => $task->assigned_to,
            'bucket_id'     => $task->bucket_id,
            'assignee_name' => $task->assignee?->name,
            'comment_count' => $task->comments->count(),
        ];
    }
}
