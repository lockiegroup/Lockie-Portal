<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ClockingEmployee;
use App\Models\ClockingPunch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ClockingReportsController extends Controller
{
    public function index(Request $request): View
    {
        $tz = config('app.timezone');

        $from = $request->filled('from')
            ? Carbon::parse($request->from, $tz)->startOfDay()
            : now($tz)->startOfMonth()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to, $tz)->endOfDay()
            : now($tz)->endOfMonth()->endOfDay();

        $employees = ClockingEmployee::with('category')->where('is_active', true)->orderBy('name')->get();

        $allPunches = ClockingPunch::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('punched_at', [$from, $to])
            ->whereNull('voided_at')
            ->orderBy('punched_at')
            ->get()
            ->groupBy('employee_id');

        $summary = $employees->map(function ($emp) use ($allPunches) {
            $punches  = $allPunches->get($emp->id, collect());
            $totalMins = 0;
            $days      = 0;
            $open      = null;
            $workedDays = [];

            foreach ($punches as $p) {
                if ($p->direction === 'in') {
                    $open = $p;
                } elseif ($p->direction === 'out' && $open) {
                    $totalMins += (int) $open->punched_at->diffInMinutes($p->punched_at);
                    $workedDays[$open->punched_at->format('Y-m-d')] = true;
                    $open = null;
                }
            }

            return [
                'employee'   => $emp,
                'total_mins' => $totalMins,
                'days'       => count($workedDays),
            ];
        });

        return view('clocking.reports', compact('summary', 'from', 'to'));
    }

    public function export(Request $request): Response
    {
        $tz = config('app.timezone');

        $from = $request->filled('from')
            ? Carbon::parse($request->from, $tz)->startOfDay()
            : now($tz)->startOfMonth()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to, $tz)->endOfDay()
            : now($tz)->endOfMonth()->endOfDay();

        $employees = ClockingEmployee::with('category')->where('is_active', true)->orderBy('name')->get();

        $allPunches = ClockingPunch::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('punched_at', [$from, $to])
            ->whereNull('voided_at')
            ->orderBy('punched_at')
            ->get()
            ->groupBy('employee_id');

        $headers = ['Employee', 'Category', 'Days Worked', 'Total Hours'];
        $rows    = [];

        foreach ($employees as $emp) {
            $punches   = $allPunches->get($emp->id, collect());
            $totalMins = 0;
            $open      = null;
            $workedDays = [];

            foreach ($punches as $p) {
                if ($p->direction === 'in') {
                    $open = $p;
                } elseif ($p->direction === 'out' && $open) {
                    $totalMins += (int) $open->punched_at->diffInMinutes($p->punched_at);
                    $workedDays[$open->punched_at->format('Y-m-d')] = true;
                    $open = null;
                }
            }

            $rows[] = [
                $emp->name,
                $emp->category?->name ?? '',
                count($workedDays),
                sprintf('%d:%02d', intdiv($totalMins, 60), $totalMins % 60),
            ];
        }

        $csv = implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\n";
        }

        $filename = 'clocking-summary-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
