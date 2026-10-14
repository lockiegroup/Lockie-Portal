<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ClockingAbsentOverride;
use App\Models\ClockingEmployee;
use App\Models\ClockingPunch;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        $employees = ClockingEmployee::with('category')->where('is_active', true)->orderBy('name')->get();

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

        // Absent overrides for the week
        $absentOverrides = ClockingAbsentOverride::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->groupBy(fn($o) => $o->employee_id . '_' . $o->date->format('Y-m-d'));

        // Pre-build punch JSON for the day-detail modal (keyed by "empId_YYYY-MM-DD")
        $punchJson = [];
        foreach ($grid as $row) {
            foreach ($row['days'] as $dayInfo) {
                $key = $row['employee']->id . '_' . $dayInfo['date']->format('Y-m-d');
                $punchJson[$key] = [
                    'employee_id'   => $row['employee']->id,
                    'employee_name' => $row['employee']->name,
                    'date'          => $dayInfo['date']->format('Y-m-d'),
                    'date_label'    => $dayInfo['date']->format('l, j F Y'),
                    'absent'        => isset($absentOverrides[$key]),
                    'punches'       => $dayInfo['punches']->map(fn($p) => [
                        'id'            => $p->id,
                        'direction'     => $p->direction,
                        'time'          => $p->punched_at->format('H:i'),
                        'punched_at'    => $p->punched_at->format('Y-m-d\TH:i'),
                        'source'        => $p->source,
                        'flagged'       => $p->is_flagged,
                        'voided'        => (bool) $p->voided_at,
                        'voided_reason' => $p->voided_reason,
                    ])->values()->toArray(),
                ];
            }
        }

        return view('clocking.timesheets', compact('grid', 'days', 'weekStart', 'punchJson', 'absentOverrides'));
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

    public function toggleAbsent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:clocking_employees,id',
            'date'        => 'required|date_format:Y-m-d',
        ]);

        $existing = ClockingAbsentOverride::where('employee_id', $data['employee_id'])
            ->where('date', $data['date'])->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['ok' => true, 'absent' => false]);
        }

        ClockingAbsentOverride::create([
            'employee_id' => $data['employee_id'],
            'date'        => $data['date'],
            'created_by'  => auth()->id(),
            'created_at'  => now(),
        ]);

        return response()->json(['ok' => true, 'absent' => true]);
    }

    public function export(Request $request): Response
    {
        $tz = config('app.timezone');

        $weekStart = $request->filled('week')
            ? Carbon::parse($request->week, $tz)->startOfWeek(Carbon::MONDAY)->startOfDay()
            : now($tz)->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        $employees  = ClockingEmployee::with('category')->where('is_active', true)->orderBy('name')->get();
        $allPunches = ClockingPunch::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('punched_at', [$weekStart, $weekEnd])
            ->whereNull('voided_at')
            ->orderBy('punched_at')
            ->get()
            ->groupBy('employee_id');

        $days = collect(range(0, 6))->map(fn($i) => $weekStart->copy()->addDays($i));

        $filename = 'timesheets-' . $weekStart->format('Y-m-d') . '.csv';

        // Build headers
        $headers = ['Employee', 'Category'];
        foreach ($days as $day) {
            $d = $day->format('D j M');
            $headers[] = $d . ' In';
            $headers[] = $d . ' Out';
            $headers[] = $d . ' Hours';
        }
        $headers[] = 'Week Total';

        $rows = [];
        foreach ($employees as $emp) {
            $empPunches  = $allPunches->get($emp->id, collect());
            $weekTotalMins = 0;
            $row = [$emp->name, $emp->category?->name ?? ''];

            foreach ($days as $day) {
                $dateStr = $day->format('Y-m-d');
                $active  = $empPunches->filter(fn($p) => $p->punched_at->format('Y-m-d') === $dateStr)
                    ->sortBy('punched_at')->values();

                [$sessions] = $this->buildSessions($active);

                if (empty($sessions)) {
                    $row[] = ''; $row[] = ''; $row[] = '';
                } else {
                    $s = $sessions[0];
                    $row[] = $s['in']->punched_at->format('H:i');
                    $row[] = $s['ongoing'] ? '' : $s['out']->punched_at->format('H:i');
                    $dayMins = array_sum(array_column($sessions, 'mins'));
                    $weekTotalMins += $dayMins;
                    $row[] = sprintf('%d:%02d', intdiv($dayMins, 60), $dayMins % 60);
                }
            }

            $row[] = sprintf('%d:%02d', intdiv($weekTotalMins, 60), $weekTotalMins % 60);
            $rows[] = $row;
        }

        $csv = implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
