<?php

namespace App\Http\Controllers;

use App\Models\ActionPlan;
use App\Models\ActionPlanItem;
use App\Models\ActionPlanMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActionPlanController extends Controller
{
    private function isAdmin(): bool
    {
        return auth()->user()->isMaster();
    }

    private function authorisePlan(ActionPlan $plan): void
    {
        if (!$this->isAdmin() && !$plan->isMember(auth()->user())) {
            abort(403);
        }
    }

    public function index(Request $request): View
    {
        $user       = auth()->user();
        $showArchived = $request->boolean('archived');

        $query = ActionPlan::with(['members.user'])
            ->where('is_archived', $showArchived)
            ->orderBy('name');

        $plans = $this->isAdmin()
            ? $query->get()
            : $query->whereHas('members', fn($q) => $q->where('user_id', $user->id))->get();

        $allUsers = $this->isAdmin() ? User::where('is_active', true)->orderBy('name')->get() : collect();

        return view('action-plans.index', compact('plans', 'allUsers', 'showArchived'));
    }

    public function show(ActionPlan $plan): View
    {
        $this->authorisePlan($plan);

        $plan->load(['members.user', 'items']);
        $grouped  = $plan->items->groupBy(fn($i) => $i->week_commencing ? $i->week_commencing->format('Y-m') : 'no-date');
        $allUsers = User::where('is_active', true)->orderBy('name')->get();

        return view('action-plans.show', compact('plan', 'grouped', 'allUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ]);
        ActionPlan::create(array_merge($data, ['created_by' => auth()->id()]));
        return redirect()->route('action-plans.index')->with('success', 'Plan created.');
    }

    public function update(Request $request, ActionPlan $plan): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ]);
        $plan->update($data);
        return redirect()->back()->with('success', 'Plan updated.');
    }

    public function destroy(ActionPlan $plan): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $plan->delete();
        return redirect()->route('action-plans.index')->with('success', 'Plan deleted.');
    }

    public function archive(ActionPlan $plan): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $plan->update(['is_archived' => true]);
        return redirect()->route('action-plans.index')->with('success', '"' . $plan->name . '" archived.');
    }

    public function unarchive(ActionPlan $plan): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $plan->update(['is_archived' => false]);
        return redirect()->route('action-plans.index', ['archived' => 1])->with('success', '"' . $plan->name . '" restored.');
    }

    public function duplicate(ActionPlan $plan): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);

        $newPlan = ActionPlan::create([
            'name'        => $plan->name . ' (Copy)',
            'description' => $plan->description,
            'created_by'  => auth()->id(),
        ]);

        // Copy members
        foreach ($plan->members as $member) {
            $newPlan->members()->create(['user_id' => $member->user_id]);
        }

        // Copy items, reset status and notes
        foreach ($plan->items as $item) {
            $newPlan->items()->create([
                'brand'             => $item->brand,
                'title'             => $item->title,
                'assigned_user_ids' => $item->assigned_user_ids,
                'week_commencing'   => $item->week_commencing,
                'status'            => 'not_started',
                'notes'             => null,
                'sort_order'        => $item->sort_order,
            ]);
        }

        return redirect()->route('action-plans.show', $newPlan)->with('success', 'Plan duplicated. Rename and adjust as needed.');
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

    private function itemDateRules(ActionPlan $plan): array
    {
        $after  = $plan->start_date ? 'after_or_equal:' . $plan->start_date->format('Y-m-d') : '';
        $before = $plan->end_date   ? 'before_or_equal:' . $plan->end_date->format('Y-m-d')  : '';
        $rules  = array_filter(['nullable', 'date', $after, $before]);
        return ['week_commencing' => implode('|', $rules)];
    }

    public function storeItem(Request $request, ActionPlan $plan): RedirectResponse
    {
        $this->authorisePlan($plan);
        $data = $request->validate(array_merge([
            'brand'               => 'nullable|string|max:100',
            'title'               => 'required|string',
            'assigned_user_ids'   => 'nullable|array',
            'assigned_user_ids.*' => 'integer|exists:users,id',
            'status'              => 'required|in:not_started,in_progress,completed,cancelled,booked_in',
            'notes'               => 'nullable|string',
        ], $this->itemDateRules($plan)));
        $plan->items()->create($data);
        return redirect()->back()->with('success', 'Task added.');
    }

    public function updateItem(Request $request, ActionPlan $plan, ActionPlanItem $item): RedirectResponse
    {
        $this->authorisePlan($plan);
        abort_unless($item->action_plan_id === $plan->id, 404);
        $data = $request->validate(array_merge([
            'brand'               => 'nullable|string|max:100',
            'title'               => 'required|string',
            'assigned_user_ids'   => 'nullable|array',
            'assigned_user_ids.*' => 'integer|exists:users,id',
            'status'              => 'required|in:not_started,in_progress,completed,cancelled,booked_in',
            'notes'               => 'nullable|string',
        ], $this->itemDateRules($plan)));
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

    public function import(Request $request, ActionPlan $plan): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);

        $path    = $request->file('csv_file')->getRealPath();
        $handle  = fopen($path, 'r');
        $header  = array_map('trim', fgetcsv($handle));
        $header  = array_map('strtolower', $header);

        $allUsers = User::where('is_active', true)->pluck('id', 'name');
        $brands   = array_map('strtolower', ActionPlanItem::BRANDS);
        $statuses = array_keys(ActionPlanItem::STATUSES);

        $imported = 0;
        $skipped  = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) { $skipped++; continue; }
            $data = array_combine($header, array_pad($row, count($header), ''));

            $title = trim($data['task'] ?? $data['title'] ?? '');
            if (!$title) { $skipped++; continue; }

            // Brand
            $brand     = trim($data['brand'] ?? '');
            $brandKeys = array_map('strtolower', ActionPlanItem::BRANDS);
            $brandMatch = array_search(strtolower($brand), $brandKeys);
            $brand = $brandMatch !== false ? ActionPlanItem::BRANDS[$brandMatch] : null;

            // Assigned users — match by full name
            $assignedIds = [];
            $assignedRaw = trim($data['assigned to'] ?? $data['assigned_to'] ?? '');
            if ($assignedRaw) {
                foreach (preg_split('/[,\/]/', $assignedRaw) as $name) {
                    $name = trim($name);
                    if ($id = $allUsers->get($name)) {
                        $assignedIds[] = $id;
                    }
                }
            }

            // Week commencing
            $wcRaw = trim($data['wc date'] ?? $data['week commencing'] ?? $data['week_commencing'] ?? '');
            $wcRaw = preg_replace('/^wc\s*/i', '', $wcRaw);
            $wc    = null;
            if ($wcRaw) {
                try { $wc = \Carbon\Carbon::parse($wcRaw)->format('Y-m-d'); } catch (\Exception $e) {}
            }

            // Date range check
            if ($wc && $plan->end_date && \Carbon\Carbon::parse($wc)->gt($plan->end_date)) {
                $skipped++; continue;
            }

            // Status
            $statusRaw = strtolower(trim($data['status'] ?? ''));
            $statusMap = [
                'not started' => 'not_started', 'not_started' => 'not_started',
                'in progress' => 'in_progress',  'in_progress' => 'in_progress',
                'completed'   => 'completed',
                'cancelled'   => 'cancelled',
                'booked in'   => 'booked_in',    'booked_in' => 'booked_in',
            ];
            $status = $statusMap[$statusRaw] ?? 'not_started';

            $plan->items()->create([
                'brand'             => $brand,
                'title'             => $title,
                'assigned_user_ids' => $assignedIds ?: null,
                'week_commencing'   => $wc,
                'status'            => $status,
                'notes'             => trim($data['notes'] ?? '') ?: null,
            ]);
            $imported++;
        }

        fclose($handle);

        $msg = $imported . ' ' . str('task')->plural($imported) . ' imported.';
        if ($skipped) $msg .= ' ' . $skipped . ' rows skipped (empty or out of range).';

        return redirect()->route('action-plans.show', $plan)->with('success', $msg);
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

        $created = 0;
        $skipped = 0;

        foreach (range(1, $data['months']) as $offset) {
            foreach ($items as $item) {
                $newDate = $item->week_commencing
                    ? $item->week_commencing->copy()->addMonths($offset)
                    : null;

                if ($newDate && $plan->end_date && $newDate->gt($plan->end_date)) {
                    $skipped++;
                    continue;
                }

                $plan->items()->create([
                    'brand'             => $item->brand,
                    'title'             => $item->title,
                    'assigned_user_ids' => $item->assigned_user_ids,
                    'week_commencing'   => $newDate,
                    'status'            => 'not_started',
                    'notes'             => null,
                    'sort_order'        => $item->sort_order,
                ]);
                $created++;
            }
        }

        $msg = $created . ' ' . str('task')->plural($created) . ' copied.';
        if ($skipped) {
            $msg .= ' ' . $skipped . ' skipped (outside plan date range).';
        }

        return redirect()->back()->with('success', $msg);
    }
}
