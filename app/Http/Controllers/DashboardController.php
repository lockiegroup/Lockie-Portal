<?php

namespace App\Http\Controllers;

use App\Models\KeyActionGroup;
use App\Models\KeyActionTask;
use App\Models\OrderBoardNote;
use App\Models\PrintJob;
use App\Models\PrintScheduleSetting;
use App\Models\ReminderEntry;
use App\Models\SalesLine;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        // ── Sales stats ───────────────────────────────────────────────────────
        $salesStats = null;
        if ($user->hasModule('sales')) {
            $now           = now();
            $mtdStart      = $now->copy()->startOfMonth();
            $lastMonthSame = $now->copy()->subMonthNoOverflow();
            $ytdStart      = $now->copy()->startOfYear();

            $mtd = (float) SalesLine::where('warehouse', 'JW Products')
                ->whereBetween('order_date', [$mtdStart, $now])
                ->sum('sub_total');

            $lastMtd = (float) SalesLine::where('warehouse', 'JW Products')
                ->whereYear('order_date',  $lastMonthSame->year)
                ->whereMonth('order_date', $lastMonthSame->month)
                ->whereDay('order_date', '<=', $now->day)
                ->sum('sub_total');

            $ytd = (float) SalesLine::where('warehouse', 'JW Products')
                ->whereBetween('order_date', [$ytdStart, $now])
                ->sum('sub_total');

            $salesStats = [
                'mtd'          => $mtd,
                'last_mtd'     => $lastMtd,
                'mtd_change'   => $lastMtd > 0 ? (($mtd - $lastMtd) / $lastMtd) * 100 : null,
                'ytd'          => $ytd,
            ];
        }

        // ── Order board ───────────────────────────────────────────────────────
        $orderStats = null;
        if ($user->hasModule('order_board')) {
            $openOrders = SalesLine::where('warehouse', 'JW Products')
                ->whereIn('status', ['Placed', 'Parked', 'Back Order'])
                ->distinct('order_no')
                ->count('order_no');

            $orderStats = ['open' => $openOrders];
        }

        // ── Key Actions ───────────────────────────────────────────────────────
        $myTasks    = null;
        $overdueCount = 0;
        if (KeyActionGroup::whereHas('members', fn($q) => $q->where('user_id', $user->id))->exists()) {
            $myTasks = KeyActionTask::with('group')
                ->where('assigned_to', $user->id)
                ->where('completed', false)
                ->orderBy('due_date')
                ->orderBy('created_at')
                ->get()
                ->groupBy('group_id');

            $overdueCount = KeyActionTask::where('assigned_to', $user->id)
                ->where('completed', false)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->startOfDay())
                ->count();
        }

        // ── Reminders ─────────────────────────────────────────────────────────
        $reminderStats = null;
        if ($user->hasModule('reminders')) {
            $pendingCount = ReminderEntry::where('status', 'pending')->count();
            $reminderStats = ['pending' => $pendingCount];
        }

        // ── Print Schedule ────────────────────────────────────────────────────
        $printStats = null;
        if ($user->hasModule('print_schedule')) {
            $throughputs = [
                'auto_1' => (int) PrintScheduleSetting::getValue('throughput_auto_1', '350'),
                'auto_2' => (int) PrintScheduleSetting::getValue('throughput_auto_2', '350'),
                'auto_3' => (int) PrintScheduleSetting::getValue('throughput_auto_3', '350'),
                'baby'   => (int) PrintScheduleSetting::getValue('throughput_baby',   '180'),
            ];

            $printStats = [];
            foreach (array_keys($throughputs) as $machine) {
                $jobs           = PrintJob::active()->where('board', $machine)->orderBy('position')->get();
                $totalRemaining = $jobs->sum(fn($j) => $j->remaining_quantity);
                $tp             = $throughputs[$machine] ?? 350;
                $leadDays       = $tp > 0 ? round($totalRemaining / $tp, 1) : 0;

                $lateCount  = 0;
                $cumulative = 0;
                foreach ($jobs as $job) {
                    $cumulative += $job->remaining_quantity;
                    if ($job->required_date && $tp > 0) {
                        $daysNeeded = ceil($cumulative / $tp);
                        $estimated  = now()->startOfDay()->addDays($daysNeeded);
                        if ($estimated->gt($job->required_date)) $lateCount++;
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

        $printNotes = $printStats !== null
            ? PrintScheduleSetting::getValue('dashboard_notes', '')
            : null;

        return view('dashboard', compact(
            'myTasks', 'overdueCount',
            'salesStats', 'orderStats', 'reminderStats',
            'printStats', 'printNotes'
        ));
    }
}
