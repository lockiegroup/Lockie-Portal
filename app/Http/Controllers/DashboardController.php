<?php

namespace App\Http\Controllers;

use App\Models\KeyActionGroup;
use App\Models\KeyActionTask;
use App\Models\PrintJob;
use App\Models\PrintScheduleSetting;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        // Key Actions: tasks assigned to this user that are not complete
        $myTasks = null;
        if (KeyActionGroup::whereHas('members', fn($q) => $q->where('user_id', $user->id))->exists()) {
            $myTasks = KeyActionTask::with('group')
                ->where('assigned_to', $user->id)
                ->where('completed', false)
                ->orderBy('created_at')
                ->get()
                ->groupBy('group_id');
        }

        // Print Schedule: machine stats (only if user has module)
        $printStats = null;
        if ($user->hasModule('print_schedule')) {
            $throughputs = [
                'auto_1' => (int) PrintScheduleSetting::getValue('throughput_auto_1', '350'),
                'auto_2' => (int) PrintScheduleSetting::getValue('throughput_auto_2', '350'),
                'auto_3' => (int) PrintScheduleSetting::getValue('throughput_auto_3', '350'),
                'baby'   => (int) PrintScheduleSetting::getValue('throughput_baby',   '180'),
            ];

            $today      = now()->startOfDay();
            $printStats = [];

            foreach (PrintJob::MACHINES as $machine) {
                $jobs           = PrintJob::active()->where('board', $machine)->orderBy('position')->get();
                $totalRemaining = $jobs->sum(fn($j) => $j->remaining_quantity);
                $tp             = $throughputs[$machine] ?? 350;
                $leadDays       = $tp > 0 ? round($totalRemaining / $tp, 1) : 0;

                $lateCount  = 0;
                $cumulative = 0;
                foreach ($jobs as $job) {
                    $cumulative += $job->remaining_quantity;
                    if ($job->required_date && $tp > 0) {
                        $daysNeeded = $tp > 0 ? ceil($cumulative / $tp) : 0;
                        $estimated  = now()->startOfDay()->addDays($daysNeeded);
                        if ($estimated->gt($job->required_date)) {
                            $lateCount++;
                        }
                    }
                }

                $printStats[$machine] = [
                    'label'      => PrintJob::BOARDS[$machine],
                    'job_count'  => $jobs->count(),
                    'lead_days'  => $leadDays,
                    'late_count' => $lateCount,
                ];
            }
        }

        return view('dashboard', compact('myTasks', 'printStats'));
    }
}
