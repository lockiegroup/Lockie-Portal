<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ClockingAbsentOverride;
use App\Models\ClockingCategory;
use App\Models\ClockingEmployee;
use App\Models\ClockingPunch;
use App\Models\ClockingSettingRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ClockingReportsController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $report, $defaultBreakMins] = $this->buildReport($request);
        $categories = ClockingCategory::orderBy('name')->get();
        return view('clocking.reports', compact('report', 'from', 'to', 'defaultBreakMins', 'categories'));
    }

    public function export(Request $request): Response
    {
        [$from, $to, $report, ] = $this->buildReport($request);

        $headers = ['Employee', 'Category', 'Date', 'Day', 'Scheduled Start', 'Scheduled End',
                    'Clock In', 'Clock Out', 'Break (mins)', 'Hours Worked', 'Notes'];
        $rows = [];

        foreach ($report as $empRow) {
            foreach ($empRow['days'] as $day) {
                $notes = [];
                if ($day['no_show'])          $notes[] = 'No show';
                if ($day['is_absent'])         $notes[] = 'Absent';
                if ($day['late_by'] !== null)  $notes[] = 'Late ' . $day['late_by'] . 'm';
                if ($day['early_by'] !== null) $notes[] = 'Left early ' . $day['early_by'] . 'm';

                $billable = $day['billable_mins'];
                $rows[] = [
                    $empRow['employee']->name,
                    $empRow['employee']->category?->name ?? '',
                    $day['date']->format('d/m/Y'),
                    $day['date']->format('l'),
                    $day['schedule']['start'] ?? '',
                    $day['schedule']['end']   ?? '',
                    $day['first_in'] ? $day['first_in']->format('H:i') : '',
                    $day['last_out'] ? $day['last_out']->format('H:i') : '',
                    $day['break_mins'],
                    $billable > 0 ? sprintf('%d:%02d', intdiv($billable, 60), $billable % 60) : '',
                    implode(', ', $notes),
                ];
            }
        }

        $csv = implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\n";
        }

        $filename = 'clocking-detail-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildReport(Request $request): array
    {
        $tz = config('app.timezone');

        $from = $request->filled('from')
            ? Carbon::parse($request->from, $tz)->startOfDay()
            : now($tz)->startOfMonth()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to, $tz)->endOfDay()
            : now($tz)->endOfMonth()->endOfDay();

        $defaultBreakMins = (int) (ClockingSettingRecord::where('key', 'default_break_minutes')->value('value') ?? 0);

        $employeeQuery = ClockingEmployee::with('category')->where('is_active', true)->orderBy('name');

        if ($request->filled('category_id')) {
            $employeeQuery->where('category_id', $request->category_id);
        }
        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        $employees = $employeeQuery->get();

        $allPunches = ClockingPunch::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('punched_at', [$from, $to])
            ->whereNull('voided_at')
            ->orderBy('punched_at')
            ->get()
            ->groupBy('employee_id');

        $absentByEmp = ClockingAbsentOverride::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy('employee_id')
            ->map(fn($rows) => $rows->pluck('date')->map(fn($d) => (string) $d)->flip()->toArray());

        $report = $employees->map(function ($emp) use ($allPunches, $absentByEmp, $from, $to, $tz, $defaultBreakMins) {
            $punches   = $allPunches->get($emp->id, collect())->groupBy(fn($p) => $p->punched_at->format('Y-m-d'));
            $absentSet = $absentByEmp->get($emp->id, []);
            $breakMins = $emp->break_override_minutes ?? $defaultBreakMins;

            $days   = [];
            $cursor = $from->copy()->startOfDay();

            while ($cursor->lte($to)) {
                $dateStr     = $cursor->format('Y-m-d');
                $dayKey      = strtolower($cursor->format('D'));
                $schedule    = ($emp->schedule ?? [])[$dayKey] ?? [];
                $isScheduled = !empty($schedule['start']);
                $isAbsent    = isset($absentSet[$dateStr]);
                $dayPunches  = $punches->get($dateStr, collect())->sortBy('punched_at')->values();

                // Skip days with nothing relevant
                if (!$isScheduled && $dayPunches->isEmpty() && !$isAbsent) {
                    $cursor->addDay();
                    continue;
                }

                // Build sessions (pair in/out)
                $sessions = [];
                $open     = null;
                foreach ($dayPunches as $p) {
                    if ($p->direction === 'in') {
                        $open = $p;
                    } elseif ($p->direction === 'out' && $open) {
                        $sessions[] = ['in' => $open, 'out' => $p];
                        $open = null;
                    }
                }
                if ($open) {
                    $sessions[] = ['in' => $open, 'out' => null];
                }

                $firstIn = $sessions ? $sessions[0]['in']->punched_at : null;
                $lastOut = null;
                foreach (array_reverse($sessions) as $s) {
                    if ($s['out']) { $lastOut = $s['out']->punched_at; break; }
                }

                $workedMins = 0;
                foreach ($sessions as $s) {
                    if ($s['out']) {
                        $workedMins += (int) $s['in']->punched_at->diffInMinutes($s['out']->punched_at);
                    }
                }

                // Deduct break only on complete days (clock-in AND clock-out exist)
                $actualBreak  = ($firstIn && $lastOut) ? $breakMins : 0;
                $billableMins = max(0, $workedMins - $actualBreak);

                // Late / left early detection
                $lateBy  = null;
                $earlyBy = null;
                if ($isScheduled && $firstIn && !empty($schedule['start'])) {
                    $sStart = Carbon::createFromFormat('H:i', $schedule['start'], $tz)->setDateFrom($cursor);
                    if ($firstIn->gt($sStart)) {
                        $lateBy = (int) $sStart->diffInMinutes($firstIn);
                    }
                }
                if ($isScheduled && $lastOut && !empty($schedule['end'])) {
                    $sEnd = Carbon::createFromFormat('H:i', $schedule['end'], $tz)->setDateFrom($cursor);
                    if ($lastOut->lt($sEnd)) {
                        $earlyBy = (int) $lastOut->diffInMinutes($sEnd);
                    }
                }

                $days[] = [
                    'date'          => $cursor->copy(),
                    'is_absent'     => $isAbsent,
                    'schedule'      => $schedule,
                    'first_in'      => $firstIn,
                    'last_out'      => $lastOut,
                    'worked_mins'   => $workedMins,
                    'break_mins'    => $actualBreak,
                    'billable_mins' => $billableMins,
                    'late_by'       => $lateBy,
                    'early_by'      => $earlyBy,
                    'no_show'       => $isScheduled && !$isAbsent && $firstIn === null,
                ];

                $cursor->addDay();
            }

            return [
                'employee'    => $emp,
                'days'        => $days,
                'total_mins'  => collect($days)->sum('billable_mins'),
                'days_worked' => collect($days)->filter(fn($d) => $d['first_in'] !== null)->count(),
                'break_mins'  => $breakMins,
            ];
        });

        return [$from, $to, $report, $defaultBreakMins];
    }
}
