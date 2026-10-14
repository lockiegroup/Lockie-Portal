<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ClockingEmployee;
use App\Models\ClockingFob;
use App\Models\ClockingFobHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClockingFobController extends Controller
{
    public function index(): View
    {
        $fobs      = ClockingFob::with('employee')->orderByDesc('is_active')->orderBy('uid')->get();
        $employees = ClockingEmployee::where('is_active', true)->orderBy('name')->get();
        return view('clocking.fobs', compact('fobs', 'employees'));
    }

    /** Register a new fob (UID captured by RFID reader, optionally assign immediately). */
    public function enrol(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uid'         => 'required|string|max:20|unique:clocking_fobs,uid',
            'employee_id' => 'nullable|exists:clocking_employees,id',
            'notes'       => 'nullable|string|max:200',
        ]);

        $fob = ClockingFob::create([
            'uid'         => $data['uid'],
            'employee_id' => $data['employee_id'] ?? null,
            'is_active'   => true,
            'assigned_at' => isset($data['employee_id']) ? now() : null,
            'notes'       => $data['notes'] ?? null,
        ]);

        if ($fob->employee_id) {
            ClockingFobHistory::create([
                'fob_id'      => $fob->id,
                'employee_id' => $fob->employee_id,
                'action'      => 'assigned',
                'created_by'  => auth()->id(),
            ]);
        }

        $fob->load('employee');
        return response()->json([
            'ok'       => true,
            'id'       => $fob->id,
            'uid'      => $fob->uid,
            'employee' => $fob->employee?->name,
        ]);
    }

    /** Re-assign a fob to a different employee (or unassign with employee_id=null). */
    public function assign(Request $request, ClockingFob $fob): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => 'nullable|exists:clocking_employees,id',
        ]);

        $fob->update([
            'employee_id' => $data['employee_id'] ?? null,
            'assigned_at' => $data['employee_id'] ? now() : null,
        ]);

        ClockingFobHistory::create([
            'fob_id'      => $fob->id,
            'employee_id' => $data['employee_id'] ?? null,
            'action'      => $data['employee_id'] ? 'assigned' : 'unassigned',
            'created_by'  => auth()->id(),
        ]);

        return response()->json(['ok' => true]);
    }

    /** Deactivate (retire) a fob. */
    public function deactivate(ClockingFob $fob): JsonResponse
    {
        $fob->update(['is_active' => false]);

        ClockingFobHistory::create([
            'fob_id'      => $fob->id,
            'employee_id' => $fob->employee_id,
            'action'      => 'deactivated',
            'created_by'  => auth()->id(),
        ]);

        return response()->json(['ok' => true]);
    }
}
