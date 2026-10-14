<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ClockingEmployee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClockingEmployeeController extends Controller
{
    public function index(): View
    {
        $employees = ClockingEmployee::with(['fobs' => fn($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();
        return view('clocking.employees', compact('employees'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:100',
            'schedule'               => 'nullable|array',
            'break_override_minutes' => 'nullable|integer|min:0|max:120',
        ]);
        $emp = ClockingEmployee::create([
            'name'                   => $data['name'],
            'is_active'              => true,
            'schedule'               => $data['schedule'] ?? null,
            'break_override_minutes' => $data['break_override_minutes'] ?? null,
        ]);
        return response()->json(['ok' => true, 'id' => $emp->id]);
    }

    public function update(Request $request, ClockingEmployee $employee): JsonResponse
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:100',
            'schedule'               => 'nullable|array',
            'break_override_minutes' => 'nullable|integer|min:0|max:120',
        ]);
        $employee->update($data);
        return response()->json(['ok' => true]);
    }

    public function toggle(ClockingEmployee $employee): JsonResponse
    {
        $employee->update(['is_active' => !$employee->is_active]);
        return response()->json(['ok' => true, 'is_active' => $employee->is_active]);
    }
}
