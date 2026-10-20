<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClockingCategory;
use App\Models\ClockingEmployee;
use App\Models\ClockingFob;
use App\Models\ClockingFobHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClockingEmployeeController extends Controller
{
    public function index(): View
    {
        $employees  = ClockingEmployee::with(['fobs' => fn($q) => $q->where('is_active', true), 'category'])
            ->orderBy('name')->get();
        $categories = ClockingCategory::orderBy('name')->get();
        return view('clocking.employees', compact('employees', 'categories'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:100',
            'category_id'            => 'nullable|exists:clocking_categories,id',
            'fob_uid'                => 'nullable|string|max:20',
            'schedule'               => 'nullable|array',
            'break_override_minutes' => 'nullable|integer|min:0|max:120',
        ]);
        if (!empty($data['fob_uid'])) {
            $taken = ClockingFob::where('uid', $data['fob_uid'])->whereNotNull('employee_id')->with('employee')->first();
            if ($taken) {
                return response()->json(['ok' => false, 'message' => "Fob {$data['fob_uid']} is already assigned to {$taken->employee->name}."], 422);
            }
        }
        $emp = ClockingEmployee::create([
            'name'                   => $data['name'],
            'category_id'            => $data['category_id'] ?? null,
            'is_active'              => true,
            'schedule'               => $data['schedule'] ?? null,
            'break_override_minutes' => $data['break_override_minutes'] ?? null,
        ]);
        if (!empty($data['fob_uid'])) {
            $this->syncFob($emp, $data['fob_uid']);
        }
        ActivityLog::record('clocking.employee_create', "Added employee: {$emp->name}");
        return response()->json(['ok' => true, 'id' => $emp->id]);
    }

    public function update(Request $request, ClockingEmployee $employee): JsonResponse
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:100',
            'category_id'            => 'nullable|exists:clocking_categories,id',
            'fob_uid'                => 'nullable|string|max:20',
            'schedule'               => 'nullable|array',
            'break_override_minutes' => 'nullable|integer|min:0|max:120',
        ]);
        if (!empty($data['fob_uid'])) {
            $taken = ClockingFob::where('uid', $data['fob_uid'])->whereNotNull('employee_id')->where('employee_id', '!=', $employee->id)->with('employee')->first();
            if ($taken) {
                return response()->json(['ok' => false, 'message' => "Fob {$data['fob_uid']} is already assigned to {$taken->employee->name}."], 422);
            }
        }
        $employee->update([
            'name'                   => $data['name'],
            'category_id'            => $data['category_id'] ?? null,
            'schedule'               => $data['schedule'] ?? null,
            'break_override_minutes' => $data['break_override_minutes'] ?? null,
        ]);
        // Only update fob if a value was explicitly sent (empty string = clear)
        if (array_key_exists('fob_uid', $data)) {
            $this->syncFob($employee, $data['fob_uid'] ?: null);
        }
        ActivityLog::record('clocking.employee_update', "Updated employee: {$employee->name}");
        return response()->json(['ok' => true]);
    }

    public function toggle(ClockingEmployee $employee): JsonResponse
    {
        $employee->update(['is_active' => !$employee->is_active]);
        $state = $employee->is_active ? 'activated' : 'deactivated';
        ActivityLog::record('clocking.employee_toggle', ucfirst($state) . " employee: {$employee->name}");
        return response()->json(['ok' => true, 'is_active' => $employee->is_active]);
    }

    public function destroy(ClockingEmployee $employee): JsonResponse
    {
        $name = $employee->name;
        // Unassign any active fob first
        $this->syncFob($employee, null);
        $employee->delete();
        ActivityLog::record('clocking.employee_delete', "Deleted employee: {$name}");
        return response()->json(['ok' => true]);
    }

    /**
     * Assign a fob UID to an employee, creating the fob record if needed.
     * If uid is null, the employee's current fob is unassigned.
     */
    private function syncFob(ClockingEmployee $employee, ?string $uid): void
    {
        $currentFob = ClockingFob::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->first();

        if (!$uid) {
            // Clear assignment
            if ($currentFob) {
                $currentFob->update(['employee_id' => null, 'assigned_at' => null]);
                ClockingFobHistory::create([
                    'fob_id'      => $currentFob->id,
                    'employee_id' => null,
                    'action'      => 'unassigned',
                    'created_by'  => auth()->id(),
                    'created_at'  => now(),
                ]);
            }
            return;
        }

        // Already the same fob — nothing to do
        if ($currentFob && $currentFob->uid === $uid) return;

        // Unassign the employee's previous fob
        if ($currentFob) {
            $currentFob->update(['employee_id' => null, 'assigned_at' => null]);
        }

        // Find or create the target fob
        $fob = ClockingFob::where('uid', $uid)->first();
        if (!$fob) {
            $fob = ClockingFob::create(['uid' => $uid, 'is_active' => true]);
        } else {
            // If fob belonged to someone else, clear that link
            if ($fob->employee_id && $fob->employee_id !== $employee->id) {
                $fob->update(['employee_id' => null, 'assigned_at' => null]);
            }
            $fob->update(['is_active' => true]);
        }

        $fob->update(['employee_id' => $employee->id, 'assigned_at' => now()]);

        ClockingFobHistory::create([
            'fob_id'      => $fob->id,
            'employee_id' => $employee->id,
            'action'      => 'assigned',
            'created_by'  => auth()->id(),
            'created_at'  => now(),
        ]);
    }
}
