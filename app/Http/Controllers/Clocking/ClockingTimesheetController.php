<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ClockingEmployee;
use App\Models\ClockingPunch;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClockingTimesheetController extends Controller
{
    public function index(Request $request): View
    {
        $tz = config('app.timezone');

        $weekStart = $request->filled('week')
            ? Carbon::parse($request->week, $tz)->startOfWeek(Carbon::MONDAY)->startOfDay()
            : now($tz)->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        $employees = ClockingEmployee::where('is_active', true)->orderBy('name')->get();

        $allPunches = ClockingPunch::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('punched_at', [$weekStart, $weekEnd])
            ->orderBy('punched_at')
            ->get()
            ->groupBy('employee_id');

        $days = collect(range(0, 6))->map(fn($i) => $weekStart->copy()->addDays($i));

        $grid = $employees->map(function ($emp) use ($allPunches, $days) {
            $empPunches = $allPunches->get($emp->id, collect());

            $dayData = $days->map(function ($day) use ($empPunches) {
                $dateStr  = $day->format('Y-m-d');
                $dayAll   = $empPunches->filter(fn($p) => $p->punched_at->format('Y-m-d') === $dateStr)->values();
                $active   = $dayAll->filter(fn($p) => !$p->voided_at)->sortBy('punched_at')->values();
                [$sessions, $total] = $this->buildSessions($active);
                return ['date' => $day, 'punches' => $dayAll, 'sessions' => $sessions, 'total_mins' => $total];
            });

            return ['employee' => $emp, 'days' => $dayData, 'week_total' => $dayData->sum('total_mins')];
        });

        return view('clocking.timesheets', compact('grid', 'days', 'weekStart'));
    }

    private function buildSessions($punches): array
    {
        $sessions = [];
        $open     = null;
        foreach ($punches as $p) {
            if ($p->direction === 'in') {
                $open = $p;
            } elseif ($p->direction === 'out' && $open) {
                $sessions[] = ['in' => $open, 'out' => $p, 'mins' => (int) $open->punched_at->diffInMinutes($p->punched_at), 'ongoing' => false];
                $open = null;
            }
        }
        if ($open) {
            $sessions[] = ['in' => $open, 'out' => null, 'mins' => (int) $open->punched_at->diffInMinutes(now()), 'ongoing' => true];
        }
        return [$sessions, array_sum(array_column($sessions, 'mins'))];
    }

    public function void(Request $request, ClockingPunch $punch): JsonResponse
    {
        $data = $request->validate(['reason' => 'nullable|string|max:200']);
        $punch->update(['voided_at' => now(), 'voided_by' => auth()->id(), 'voided_reason' => $data['reason'] ?? null]);
        return response()->json(['ok' => true]);
    }

    public function addPunch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:clocking_employees,id',
            'direction'   => 'required|in:in,out',
            'punched_at'  => 'required|date',
        ]);

        $punch = ClockingPunch::create([
            'employee_id' => $data['employee_id'],
            'direction'   => $data['direction'],
            'punched_at'  => Carbon::parse($data['punched_at'], config('app.timezone')),
            'source'      => 'manual',
        ]);

        return response()->json(['ok' => true, 'id' => $punch->id]);
    }
}
