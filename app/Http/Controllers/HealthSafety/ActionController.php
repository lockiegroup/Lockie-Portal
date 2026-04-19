<?php

namespace App\Http\Controllers\HealthSafety;

use App\Http\Controllers\Controller;
use App\Models\HsAction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActionController extends Controller
{
    public function index(Request $request): View
    {
        $query = HsAction::with(['assignedUser', 'raisedByUser'])
            ->orderByRaw("CASE status WHEN 'overdue' THEN 0 WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
            ->orderBy('due_date');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $actions = $query->get();

        $counts = [
            'overdue'     => HsAction::where('status', 'overdue')->count(),
            'open'        => HsAction::where('status', 'open')->count(),
            'in_progress' => HsAction::where('status', 'in_progress')->count(),
            'due_soon'    => HsAction::whereNotIn('status', ['completed'])
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->count(),
        ];

        return view('health-safety.actions.index', compact('actions', 'counts'));
    }

    public function create(): View
    {
        $users = User::where('is_active', true)->orderBy('name')->get();
        return view('health-safety.actions.create', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'location'        => 'nullable|string|max:255',
            'assigned_to'     => 'nullable|exists:users,id',
            'priority'        => 'required|in:low,medium,high,critical',
            'due_date'        => 'required|date',
            'is_recurring'    => 'boolean',
            'recurrence_type' => 'nullable|in:daily,weekly,monthly,quarterly,annually',
            'notes'           => 'nullable|string',
        ]);

        $data['raised_by']    = auth()->id();
        $data['status']       = 'open';
        $data['is_recurring'] = $request->boolean('is_recurring');

        if (!$data['is_recurring']) {
            $data['recurrence_type'] = null;
        }

        HsAction::create($data);

        return redirect()->route('hs.actions.index')->with('success', 'Action created successfully.');
    }

    public function edit(HsAction $action): View
    {
        $users = User::where('is_active', true)->orderBy('name')->get();
        return view('health-safety.actions.edit', compact('action', 'users'));
    }

    public function update(Request $request, HsAction $action): RedirectResponse
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'location'        => 'nullable|string|max:255',
            'assigned_to'     => 'nullable|exists:users,id',
            'priority'        => 'required|in:low,medium,high,critical',
            'status'          => 'required|in:open,in_progress,completed,overdue',
            'due_date'        => 'required|date',
            'is_recurring'    => 'boolean',
            'recurrence_type' => 'nullable|in:daily,weekly,monthly,quarterly,annually',
            'notes'           => 'nullable|string',
        ]);

        $data['is_recurring'] = $request->boolean('is_recurring');

        if (!$data['is_recurring']) {
            $data['recurrence_type'] = null;
        }

        // If marking complete, set timestamp and spawn next recurring action
        if ($data['status'] === 'completed' && $action->status !== 'completed') {
            $data['completed_at'] = now();
            if ($action->is_recurring && $action->recurrence_type) {
                HsAction::create([
                    'title'           => $action->title,
                    'description'     => $action->description,
                    'location'        => $action->location,
                    'assigned_to'     => $action->assigned_to,
                    'raised_by'       => $action->raised_by,
                    'priority'        => $action->priority,
                    'status'          => 'open',
                    'due_date'        => $action->nextDueDate(),
                    'is_recurring'    => true,
                    'recurrence_type' => $action->recurrence_type,
                    'parent_id'       => $action->parent_id ?? $action->id,
                    'notes'           => $action->notes,
                ]);
            }
        }

        $action->update($data);

        return redirect()->route('hs.actions.index')->with('success', 'Action updated.');
    }

    public function complete(HsAction $action): RedirectResponse
    {
        if ($action->status !== 'completed') {
            $action->update(['status' => 'completed', 'completed_at' => now()]);

            if ($action->is_recurring && $action->recurrence_type) {
                HsAction::create([
                    'title'           => $action->title,
                    'description'     => $action->description,
                    'location'        => $action->location,
                    'assigned_to'     => $action->assigned_to,
                    'raised_by'       => $action->raised_by,
                    'priority'        => $action->priority,
                    'status'          => 'open',
                    'due_date'        => $action->nextDueDate(),
                    'is_recurring'    => true,
                    'recurrence_type' => $action->recurrence_type,
                    'parent_id'       => $action->parent_id ?? $action->id,
                    'notes'           => $action->notes,
                ]);
            }
        }

        return back()->with('success', 'Action marked as complete.');
    }

    public function destroy(HsAction $action): RedirectResponse
    {
        $action->delete();
        return redirect()->route('hs.actions.index')->with('success', 'Action removed.');
    }
}
