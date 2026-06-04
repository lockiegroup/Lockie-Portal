<?php

namespace App\Http\Controllers;

use App\Models\ActionPlan;
use App\Models\ActionPlanItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActionPlanController extends Controller
{
    private function isAdmin(): bool
    {
        $user = auth()->user();
        return $user->isMaster() || !empty($user->permissions);
    }

    private function authorisePlan(ActionPlan $plan): void
    {
        if (!$this->isAdmin() && !$plan->isMember(auth()->user())) {
            abort(403);
        }
    }

    public function index(): View
    {
        $user  = auth()->user();
        $plans = $this->isAdmin()
            ? ActionPlan::with(['members.user'])->orderBy('name')->get()
            : ActionPlan::whereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->with(['members.user'])->orderBy('name')->get();

        $allUsers = $this->isAdmin() ? User::where('is_active', true)->orderBy('name')->get() : collect();

        return view('action-plans.index', compact('plans', 'allUsers'));
    }

    public function show(ActionPlan $plan): View
    {
        $this->authorisePlan($plan);

        $plan->load(['members.user', 'items.assignedUser']);
        $grouped   = $plan->items->groupBy(fn($i) => $i->week_commencing ? $i->week_commencing->format('Y-m') : 'no-date');
        $allUsers  = User::where('is_active', true)->orderBy('name')->get();

        return view('action-plans.show', compact('plan', 'grouped', 'allUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $data = $request->validate(['name' => 'required|string|max:150', 'description' => 'nullable|string']);
        ActionPlan::create(array_merge($data, ['created_by' => auth()->id()]));
        return redirect()->route('action-plans.index')->with('success', 'Plan created.');
    }

    public function update(Request $request, ActionPlan $plan): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $data = $request->validate(['name' => 'required|string|max:150', 'description' => 'nullable|string']);
        $plan->update($data);
        return redirect()->back()->with('success', 'Plan updated.');
    }

    public function destroy(ActionPlan $plan): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $plan->delete();
        return redirect()->route('action-plans.index')->with('success', 'Plan deleted.');
    }

    public function addMember(Request $request, ActionPlan $plan): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $data = $request->validate(['user_id' => 'required|exists:users,id']);
        $plan->members()->firstOrCreate(['user_id' => $data['user_id']]);
        return redirect()->back()->with('success', 'Member added.');
    }

    public function removeMember(ActionPlan $plan, User $user): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $plan->members()->where('user_id', $user->id)->delete();
        return redirect()->back()->with('success', 'Member removed.');
    }

    public function storeItem(Request $request, ActionPlan $plan): RedirectResponse
    {
        $this->authorisePlan($plan);
        $data = $request->validate([
            'brand'            => 'nullable|string|max:100',
            'type'             => 'nullable|string|max:100',
            'title'            => 'required|string',
            'assigned_user_id' => 'nullable|exists:users,id',
            'week_commencing'  => 'nullable|date',
            'status'           => 'required|in:not_started,in_progress,completed,cancelled,booked_in',
            'notes'            => 'nullable|string',
        ]);
        $plan->items()->create($data);
        return redirect()->back()->with('success', 'Task added.');
    }

    public function updateItem(Request $request, ActionPlan $plan, ActionPlanItem $item): RedirectResponse
    {
        $this->authorisePlan($plan);
        abort_unless($item->action_plan_id === $plan->id, 404);
        $data = $request->validate([
            'brand'            => 'nullable|string|max:100',
            'type'             => 'nullable|string|max:100',
            'title'            => 'required|string',
            'assigned_user_id' => 'nullable|exists:users,id',
            'week_commencing'  => 'nullable|date',
            'status'           => 'required|in:not_started,in_progress,completed,cancelled,booked_in',
            'notes'            => 'nullable|string',
        ]);
        $item->update($data);
        return redirect()->back()->with('success', 'Task updated.');
    }

    public function destroyItem(ActionPlan $plan, ActionPlanItem $item): RedirectResponse
    {
        $this->authorisePlan($plan);
        abort_unless($item->action_plan_id === $plan->id, 404);
        $item->delete();
        return redirect()->back()->with('success', 'Task deleted.');
    }

    public function copyItems(Request $request, ActionPlan $plan): RedirectResponse
    {
        $this->authorisePlan($plan);
        $data = $request->validate([
            'item_ids'   => 'required|array',
            'item_ids.*' => 'integer|exists:action_plan_items,id',
            'months'     => 'required|integer|min:1|max:12',
        ]);

        $items = ActionPlanItem::whereIn('id', $data['item_ids'])
            ->where('action_plan_id', $plan->id)
            ->get();

        foreach (range(1, $data['months']) as $offset) {
            foreach ($items as $item) {
                $plan->items()->create([
                    'brand'            => $item->brand,
                    'type'             => $item->type,
                    'title'            => $item->title,
                    'assigned_user_id' => $item->assigned_user_id,
                    'week_commencing'  => $item->week_commencing
                        ? $item->week_commencing->copy()->addMonths($offset)
                        : null,
                    'status'           => 'not_started',
                    'notes'            => null,
                    'sort_order'       => $item->sort_order,
                ]);
            }
        }

        return redirect()->back()->with('success', count($items) * $data['months'] . ' tasks copied.');
    }
}
