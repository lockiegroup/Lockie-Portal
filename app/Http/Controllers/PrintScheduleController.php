<?php

namespace App\Http\Controllers;

use App\Mail\PrintJobDateChangedMail;
use App\Models\PrintJob;
use App\Models\PrintJobRun;
use App\Models\PrintJobDateChange;
use App\Models\PrintJobNote;
use App\Models\PrintScheduleSetting;
use App\Services\PrintScheduleSyncService;
use App\Services\UnleashedService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PrintScheduleController extends Controller
{
    public function index(): View
    {
        $boards      = PrintJob::BOARDS;
        $boardLabels = PrintJob::BOARDS;
        $machines    = PrintJob::MACHINES;
        $throughputs = $this->loadThroughputs();

        $lastSync  = PrintJob::active()->max('synced_at');
        $boardJobs = [];
        foreach (array_keys($boards) as $boardKey) {
            $boardJobs[$boardKey] = PrintJob::active()->where('board', $boardKey)
                ->orderBy('position')
                ->with([
                    'notes',
                    'dateChanges.user',
                    'runs' => fn($q) => $q->orderBy('started_at')->with('user'),
                ])
                ->get();
        }

        // Machine lead times using per-machine throughput
        $machineLeadTimes = [];
        foreach ($machines as $machine) {
            $total = $boardJobs[$machine]->sum(fn($job) => $job->remaining_quantity);
            $tp    = $throughputs[$machine] ?? 350;
            $machineLeadTimes[$machine] = $tp > 0 ? round($total / $tp, 1) : 0;
        }

        // Compute estimated completion and late flags for machine board jobs
        $today = now()->startOfDay();
        foreach ($machines as $machine) {
            $throughput = $throughputs[$machine] ?? 350;
            $cumulative = 0;
            foreach ($boardJobs[$machine] as $job) {
                $cumulative += $job->remaining_quantity;
                if ($job->required_date && $throughput > 0 && $cumulative > 0) {
                    $estimated           = $this->estimatedCompletion($today, $cumulative, $throughput);
                    $daysLate            = (int) $job->required_date->diffInDays($estimated, false);
                    $job->is_late        = $daysLate > 0;
                    $job->days_overdue   = max(0, $daysLate);
                    $job->est_completion = $estimated;
                } else {
                    $job->is_late        = false;
                    $job->days_overdue   = 0;
                    $job->est_completion = null;
                }
            }
        }

        // Count active jobs per effective SO number (for multi-line badge on cards).
        // Regular jobs: group by order_number (e.g. SO-00026477).
        // Assembly jobs: group by the sales order number extracted from line_comment
        //   ("Created for Invoice SO-XXXXX."), since each assembly has its own ASM number.
        $orderLineCounts = [];
        PrintJob::active()
            ->whereNotNull('order_number')
            ->where('order_number', '!=', 'MANUAL')
            ->select(['order_number', 'line_comment'])
            ->get()
            ->each(function ($job) use (&$orderLineCounts) {
                if (str_starts_with($job->order_number, 'ASM-')
                    && preg_match('/\b(SO-\d+)\b/', $job->line_comment ?? '', $m)) {
                    $key = $m[1];
                } else {
                    $key = $job->order_number;
                }
                $orderLineCounts[$key] = ($orderLineCounts[$key] ?? 0) + 1;
            });

        // Today's completed/paused runs per machine for the history log
        $machineHistory = [];
        foreach ($machines as $machine) {
            $machineHistory[$machine] = \App\Models\PrintJobRun::where('machine', $machine)
                ->whereNotNull('ended_at')
                ->whereDate('started_at', today())
                ->orderBy('started_at')
                ->with(['user', 'printJob:id,customer_name,product_code,order_number'])
                ->get();
        }

        return view('print-schedule.index', compact(
            'boardJobs',
            'boards',
            'boardLabels',
            'machines',
            'machineLeadTimes',
            'throughputs',
            'lastSync',
            'orderLineCounts',
            'machineHistory'
        ));
    }

    private function buildMachineLogQuery(Request $request): array
    {
        $dateFrom = $request->input('date_from', today()->format('Y-m-d'));
        $dateTo   = $request->input('date_to',   $dateFrom);
        $machine  = $request->input('machine', 'all');
        $operator = $request->input('operator', 'all');
        $machines = PrintJob::MACHINES;

        // Clamp so date_to is never before date_from
        if ($dateTo < $dateFrom) $dateTo = $dateFrom;

        $query = \App\Models\PrintJobRun::with(['user', 'printJob:id,customer_name,product_code,order_number,order_quantity'])
            ->whereIn('machine', $machines)
            ->whereDate('started_at', '>=', $dateFrom)
            ->whereDate('started_at', '<=', $dateTo)
            ->orderBy('machine')
            ->orderBy('started_at');

        if ($machine !== 'all' && in_array($machine, $machines)) {
            $query->where('machine', $machine);
        }
        if ($operator !== 'all') {
            $query->where('user_id', $operator);
        }

        $allRuns = $query->get();

        // For jobs that started before this date range, find the packs baseline
        // (the highest packs_produced from runs before the range) so per-period
        // pack counts and rates are not inflated by carry-over from prior days.
        $jobIds = $allRuns->pluck('print_job_id')->unique()->filter()->values();
        $jobBaselines = [];
        if ($jobIds->isNotEmpty()) {
            $jobBaselines = \App\Models\PrintJobRun::whereIn('print_job_id', $jobIds)
                ->whereDate('started_at', '<', $dateFrom)
                ->whereNotNull('packs_produced')
                ->selectRaw('print_job_id, MAX(packs_produced) as baseline')
                ->groupBy('print_job_id')
                ->pluck('baseline', 'print_job_id')
                ->map(fn($v) => (int) $v)
                ->toArray();
        }

        // Group by machine, compute gaps
        $byMachine = [];
        foreach ($machines as $m) {
            if ($machine !== 'all' && $machine !== $m) continue;
            $machineRuns = $allRuns->where('machine', $m)->values();
            if ($machineRuns->isEmpty()) continue;

            $entries = [];
            foreach ($machineRuns as $i => $run) {
                $gapSeconds = null;
                if ($i > 0) {
                    $prev = $machineRuns[$i - 1];
                    if ($prev->ended_at !== null) {
                        $gap = $run->started_at->diffInSeconds($prev->ended_at);
                        if ($gap > 60) $gapSeconds = $gap;
                    }
                }
                $entries[] = ['run' => $run, 'gap' => $gapSeconds];
            }
            $byMachine[$m] = $entries;
        }

        // All operators who have runs in this date range (for the dropdown)
        $operators = \App\Models\User::whereHas('runs', function ($q) use ($dateFrom, $dateTo) {
            $q->whereDate('started_at', '>=', $dateFrom)->whereDate('started_at', '<=', $dateTo);
        })->orderBy('name')->get(['id', 'name']);

        return compact('byMachine', 'machines', 'operators', 'dateFrom', 'dateTo', 'machine', 'operator', 'jobBaselines');
    }

    public function machineLog(Request $request): View
    {
        $data = $this->buildMachineLogQuery($request);
        return view('print-schedule.machine-log', $data);
    }

    public function machineLogExport(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data    = $this->buildMachineLogQuery($request);
        $from    = $data['dateFrom'];
        $to      = $data['dateTo'];
        $suffix  = $from === $to ? $from : "{$from}_to_{$to}";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"machine-log-{$suffix}.csv\"",
        ];

        return response()->stream(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Machine', 'Operator', 'Customer', 'Order #', 'Product', 'Order Qty', 'Start', 'End', 'Duration (min)', 'Packs Produced', 'Progress Packs', 'Status', 'Pause Reason', 'Fully Complete', 'Rate (packs/hr)']);

            foreach ($data['byMachine'] as $machineName => $entries) {
                foreach ($entries as $entry) {
                    $run = $entry['run'];
                    $dur = $run->ended_at
                        ? abs((int) $run->ended_at->diffInSeconds($run->started_at))
                        : abs((int) now()->diffInSeconds($run->started_at));
                    $hours = $dur > 0 ? $dur / 3600 : 0;
                    $packs = $run->packs_produced ?? $run->progress_packs;
                    $rate  = ($packs && $hours >= 0.1) ? (int) round($packs / $hours) : null;

                    fputcsv($out, [
                        $run->started_at->format('Y-m-d'),
                        ucwords(str_replace('_', ' ', $machineName)),
                        $run->user?->name ?? '',
                        $run->printJob?->customer_name ?? '',
                        $run->printJob?->order_number ?? '',
                        $run->printJob?->product_code ?? '',
                        $run->printJob?->order_quantity ?? '',
                        $run->started_at->format('H:i'),
                        $run->ended_at?->format('H:i') ?? 'Running',
                        round($dur / 60, 1),
                        $run->packs_produced ?? '',
                        $run->progress_packs ?? '',
                        $run->ended_at === null ? 'Running' : ($run->end_reason ?? ''),
                        $run->pause_reason ?? '',
                        $run->fully_complete ? 'Yes' : 'No',
                        $rate ?? '',
                    ]);
                }
            }
            fclose($out);
        }, 200, $headers);
    }

    // Mon–Thu = full 8h day (weight 1.0), Fri = 5h day (8:00–13:30 minus 30min break, weight 5/8)
    private const DAY_WEIGHTS = [
        1 => 1.0,          // Monday
        2 => 1.0,          // Tuesday
        3 => 1.0,          // Wednesday
        4 => 1.0,          // Thursday
        5 => 5.0 / 8.0,    // Friday
    ];

    private function loadThroughputs(): array
    {
        return [
            'auto_1' => (int) PrintScheduleSetting::getValue('throughput_auto_1', '350'),
            'auto_2' => (int) PrintScheduleSetting::getValue('throughput_auto_2', '350'),
            'auto_3' => (int) PrintScheduleSetting::getValue('throughput_auto_3', '350'),
            'baby'   => (int) PrintScheduleSetting::getValue('throughput_baby',   '180'),
            // laser and coditherm are not throughput-tracked machines — no lead time shown
        ];
    }

    private function estimatedCompletion(Carbon $from, int $packsNeeded, int $throughput): Carbon
    {
        $date      = $from->copy()->addDay()->startOfDay(); // work starts next working day
        $remaining = (float) $packsNeeded;
        for ($i = 0; $i < 500; $i++) {
            $weight = self::DAY_WEIGHTS[$date->dayOfWeek] ?? 0.0;
            if ($weight > 0.0) {
                $remaining -= $throughput * $weight;
                if ($remaining <= 0.0) return $date;
            }
            $date->addDay();
        }
        return $date;
    }

    public function overview(): View
    {
        $machines    = PrintJob::MACHINES;
        $throughputs = $this->loadThroughputs();
        $today       = now()->startOfDay();

        $machineStats = [];
        foreach ($machines as $machine) {
            $jobs           = PrintJob::active()->where('board', $machine)->orderBy('position')->get();
            $totalRemaining = $jobs->sum(fn($j) => $j->remaining_quantity);
            $tp             = $throughputs[$machine] ?? 350;
            $leadDays       = $tp > 0 ? round($totalRemaining / $tp, 1) : 0;

            $lateCount  = 0;
            $cumulative = 0;
            foreach ($jobs as $job) {
                $cumulative += $job->remaining_quantity;
                if ($job->required_date && $tp > 0 && $cumulative > 0) {
                    $estimated = $this->estimatedCompletion($today, $cumulative, $tp);
                    if ($estimated->gt($job->required_date)) {
                        $lateCount++;
                    }
                }
            }

            $machineStats[$machine] = [
                'label'      => PrintJob::BOARDS[$machine],
                'job_count'  => $jobs->count(),
                'remaining'  => $totalRemaining,
                'lead_days'  => $leadDays,
                'throughput' => $tp,
                'late_count' => $lateCount,
            ];
        }

        $dashboardNotes = PrintScheduleSetting::getValue('dashboard_notes', '');
        $lastSync       = PrintJob::active()->max('synced_at');

        return view('print-schedule.overview', compact('machineStats', 'dashboardNotes', 'lastSync'));
    }

    public function production(): View
    {
        $machines = PrintJob::BOARDS; // label map
        return view('print-schedule.production', compact('machines'));
    }

    public function productionStatus(): JsonResponse
    {
        $machines    = PrintJob::MACHINES;
        $throughputs = $this->loadThroughputs();
        $result      = [];

        // Target packs/hr per machine = daily throughput ÷ net working hours per day
        $workStart     = PrintScheduleSetting::getValue('work_start',    '08:00');
        $workEnd       = PrintScheduleSetting::getValue('work_end',      '16:30');
        $breakMins     = (int) PrintScheduleSetting::getValue('break_minutes', '30');
        [$sh, $sm]     = array_map('intval', explode(':', $workStart));
        [$eh, $em]     = array_map('intval', explode(':', $workEnd));
        $workHours     = max(1, (($eh * 60 + $em) - ($sh * 60 + $sm) - $breakMins) / 60);

        foreach ($machines as $machine) {
            // Active run (machine is running)
            $active = PrintJobRun::where('machine', $machine)
                ->whereNull('ended_at')
                ->with(['user', 'printJob'])
                ->latest('started_at')
                ->first();

            if ($active) {
                $dayStart  = now()->startOfDay();
                $todayRuns = PrintJobRun::where('machine', $machine)
                    ->where('started_at', '>=', $dayStart)
                    ->get();

                $jobRuns = $todayRuns->where('print_job_id', $active->print_job_id);

                // Packs this run: progress_packs minus last-ended run's packs_produced.
                // Use last-by-id (not MAX) so operator corrections to a lower value are respected.
                $lastEndedPacks = PrintJobRun::where('machine', $machine)
                    ->where('print_job_id', $active->print_job_id)
                    ->whereNotNull('ended_at')
                    ->whereNotNull('packs_produced')
                    ->orderBy('id', 'desc')
                    ->value('packs_produced') ?? 0;

                $packsThisRun = $active->progress_packs !== null
                    ? max(0, $active->progress_packs - $lastEndedPacks)
                    : null;

                // Rate helper: mirrors the machine-log approach — uses the operator's latest packs
                // reading (progress_packs) minus a prior-day baseline, divided by total run time.
                // This is robust against wrong packs entries because it never reconstructs per-run
                // deltas; a corrected entry just moves the total forward from whatever it was.
                $rateForRuns = function (\Illuminate\Support\Collection $runs, int $jobId) use ($machine, $active, $dayStart): array {
                    $priorBase = PrintJobRun::where('machine', $machine)
                        ->where('print_job_id', $jobId)
                        ->whereNotNull('ended_at')
                        ->whereNotNull('packs_produced')
                        ->where('started_at', '<', $dayStart)
                        ->max('packs_produced') ?? 0;

                    $activeRun = $runs->firstWhere('ended_at', null);
                    $currTotal = $activeRun?->progress_packs
                        ?? $runs->filter(fn($r) => $r->packs_produced !== null && $r->ended_at !== null)
                               ->sortByDesc(fn($r) => $r->id)->first()?->packs_produced;

                    if ($currTotal === null) return [null, null, 0, 0];
                    $packs = max(0, $currTotal - $priorBase);

                    $secs = 0;
                    foreach ($runs as $run) {
                        if ($run->ended_at) {
                            $secs += abs((int) $run->ended_at->diffInSeconds($run->started_at));
                        } elseif ($activeRun && $run->id === $activeRun->id) {
                            $end   = $activeRun->progress_at ?? now();
                            $secs += abs((int) $end->diffInSeconds($run->started_at));
                        }
                    }

                    if ($packs <= 0 || $secs < 60) return [null, null, $packs, $secs];
                    $pph = $packs / ($secs / 3600);
                    $n   = (int) round($pph);
                    $str = $n >= 1000 ? number_format($pph / 1000, 1) . 'k/hr' : number_format($n) . '/hr';
                    return [$pph, $str, $packs, $secs];
                };

                // Avg job: only runs for this specific job on this machine today
                [$rateJobPPH, $rateJobStr] = $rateForRuns($jobRuns, $active->print_job_id);

                // Avg today: aggregate across all jobs on this machine today
                $todayJobIds = $todayRuns->pluck('print_job_id')->unique()->filter()->values();
                if ($todayJobIds->count() === 1) {
                    [$rateTodayPPH, $rateTodayStr] = [$rateJobPPH, $rateJobStr];
                } else {
                    $allPacks = 0;
                    $allSecs  = 0;
                    foreach ($todayJobIds as $jid) {
                        [, , $p, $s] = $rateForRuns($todayRuns->where('print_job_id', $jid), $jid);
                        $allPacks += $p;
                        $allSecs  += $s;
                    }
                    if ($allPacks > 0 && $allSecs >= 60) {
                        $pph           = $allPacks / ($allSecs / 3600);
                        $n             = (int) round($pph);
                        $rateTodayPPH  = $pph;
                        $rateTodayStr  = $n >= 1000 ? number_format($pph / 1000, 1) . 'k/hr' : number_format($n) . '/hr';
                    } else {
                        $rateTodayPPH = null;
                        $rateTodayStr = null;
                    }
                }

                // Progress % — use current run's progress_packs (cumulative), or fall back
                // to the last ended run's packs_produced if no update logged on this run yet
                $orderQty  = $active->printJob?->order_quantity;
                $totalDone = $active->progress_packs;
                if ($totalDone === null) {
                    $totalDone = $jobRuns->whereNotNull('ended_at')
                        ->whereNotNull('packs_produced')
                        ->sortByDesc(fn($r) => $r->ended_at?->timestamp)
                        ->first()?->packs_produced;
                }
                $pctComplete = ($orderQty > 0 && $totalDone !== null)
                    ? min(100, (int) round($totalDone / $orderQty * 100))
                    : null;

                // On-track: compare avg job rate vs machine target (job rate is more meaningful)
                $onTrack   = null;
                $targetPPH = isset($throughputs[$machine]) ? $throughputs[$machine] / $workHours : null;
                $targetStr = $targetPPH !== null
                    ? ((int) round($targetPPH) >= 1000
                        ? number_format($targetPPH / 1000, 1) . 'k/hr'
                        : number_format((int) round($targetPPH)) . '/hr')
                    : null;

                $rateStatus = fn(?float $pph) => $pph === null || $targetPPH === null ? null
                    : ($pph / $targetPPH >= 0.9 ? 'on_track' : ($pph / $targetPPH >= 0.7 ? 'at_risk' : 'behind'));

                $onTrack      = $rateStatus($rateJobPPH);
                $onTrackToday = $rateStatus($rateTodayPPH);

                $result[$machine] = [
                    'state'           => 'running',
                    'label'           => PrintJob::BOARDS[$machine],
                    'operator'        => $active->user?->name,
                    'job_number'      => $active->printJob?->order_number,
                    'product_code'    => $active->printJob?->product_code,
                    'customer'        => $active->printJob?->customer_name,
                    'started_at'      => $active->started_at->toIso8601String(),
                    'progress_packs'  => $active->progress_packs,
                    'packs_this_run'  => $packsThisRun,
                    'progress_at'     => $active->progress_at?->toIso8601String(),
                    'rate_today_str'  => $rateTodayStr,
                    'on_track_today'  => $onTrackToday,
                    'rate_job_str'    => $rateJobStr,
                    'target_str'      => $targetStr,
                    'order_qty'       => $orderQty,
                    'pct_complete'    => $pctComplete,
                    'on_track'        => $onTrack,
                ];
                continue;
            }

            // Most recent ended run — only treat as paused if the last thing that happened was a pause
            $lastRun = PrintJobRun::where('machine', $machine)
                ->whereNotNull('ended_at')
                ->where('ended_at', '>=', now()->subHours(16))
                ->with(['user', 'printJob'])
                ->latest('ended_at')
                ->first();

            $paused = ($lastRun && $lastRun->end_reason === 'pause') ? $lastRun : null;

            if ($paused) {
                $result[$machine] = [
                    'state'        => 'paused',
                    'label'        => PrintJob::BOARDS[$machine],
                    'operator'     => $paused->user?->name,
                    'job_number'   => $paused->printJob?->order_number,
                    'product_code' => $paused->printJob?->product_code,
                    'customer'     => $paused->printJob?->customer_name,
                    'pause_reason' => $paused->pause_reason ?: 'No reason given',
                    'paused_at'    => $paused->ended_at->toIso8601String(),
                    'packs_at_pause' => $paused->packs_produced,
                ];
                continue;
            }

            $result[$machine] = [
                'state' => 'idle',
                'label' => PrintJob::BOARDS[$machine],
            ];
        }

        return response()->json([
            'machines'   => $result,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function sync(): JsonResponse
    {
        $current = \Illuminate\Support\Facades\Cache::get('print_sync_status', []);
        if (($current['status'] ?? '') === 'running') {
            return response()->json(['queued' => true, 'already_running' => true]);
        }

        \Illuminate\Support\Facades\Cache::put('print_sync_status', ['status' => 'running', 'at' => now()->toIso8601String()], 600);

        $artisan = PHP_BINARY . ' ' . base_path('artisan');
        exec("nohup {$artisan} print:sync > /dev/null 2>&1 &");

        return response()->json(['queued' => true]);
    }

    public function syncStatus(): JsonResponse
    {
        $status = \Illuminate\Support\Facades\Cache::get('print_sync_status', ['status' => 'idle']);
        return response()->json($status);
    }

    public function unarchive(\App\Models\PrintJob $job): JsonResponse
    {
        $job->update(['archived_at' => null, 'archive_reason' => null, 'despatched_at' => null]);
        return response()->json(['ok' => true]);
    }

    public function moveBoard(Request $request, PrintJob $job): JsonResponse
    {
        $request->validate([
            'board' => ['required', 'in:' . implode(',', array_keys(PrintJob::BOARDS))],
        ]);

        $board       = $request->input('board');
        $maxPosition = PrintJob::where('board', $board)->max('position') ?? 0;

        $job->update([
            'board'    => $board,
            'position' => $maxPosition + 1,
        ]);

        $boardLabel = PrintJob::BOARDS[$board] ?? $board;
        \App\Models\ActivityLog::record('print.board_move', "Moved {$job->order_number} to {$boardLabel}");

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($request->input('order') as $position => $id) {
            PrintJob::where('id', $id)->update(['position' => $position]);
        }

        return response()->json(['success' => true]);
    }

    public function partComplete(Request $request, PrintJob $job): JsonResponse
    {
        $request->validate([
            'quantity_completed' => ['required', 'integer', 'min:0', 'max:' . $job->order_quantity],
        ]);

        $qty = $request->integer('quantity_completed');
        $job->update(['quantity_completed' => $qty]);

        \App\Models\ActivityLog::record('print.complete', "Updated completion on {$job->order_number}: {$qty} qty completed");

        return response()->json([
            'success'   => true,
            'remaining' => $job->remaining_quantity,
        ]);
    }

    public function toggleMaterial(Request $request, PrintJob $job): JsonResponse
    {
        $request->validate(['checked' => ['required', 'boolean']]);
        $job->update(['material_checked' => $request->boolean('checked')]);
        return response()->json(['success' => true, 'material_checked' => $job->material_checked]);
    }

    public function updateDate(Request $request, PrintJob $job): JsonResponse
    {
        $request->validate([
            'required_date' => ['required', 'date'],
        ]);

        $newDate = $request->input('required_date');
        $oldDate = $job->required_date ? $job->required_date->format('Y-m-d') : null;

        if ($oldDate !== $newDate) {
            PrintJobDateChange::create([
                'print_job_id' => $job->id,
                'user_id'      => auth()->id(),
                'old_date'     => $oldDate,
                'new_date'     => $newDate,
            ]);

            Mail::to('sales@jwproducts.co.uk')->send(new PrintJobDateChangedMail(
                job:       $job,
                oldDate:   $oldDate,
                newDate:   $newDate,
                changedBy: auth()->user()->name,
            ));
        }

        $job->update(['required_date' => $newDate]);

        return response()->json([
            'success'      => true,
            'date_changed' => $job->fresh()->date_changed,
        ]);
    }

    public function storeNote(Request $request, PrintJob $job): JsonResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $note = PrintJobNote::create([
            'print_job_id' => $job->id,
            'user_id'      => auth()->id(),
            'body'         => $request->input('body'),
        ]);

        $note->load('user');

        \App\Models\ActivityLog::record('print.note_add', "Added note to {$job->order_number}");

        return response()->json([
            'success' => true,
            'note'    => [
                'id'         => $note->id,
                'body'       => $note->body,
                'user_name'  => $note->user?->name ?? 'Unknown',
                'created_at' => $note->created_at->format('d M Y, H:i'),
            ],
        ]);
    }

    public function destroyNote(PrintJob $job, PrintJobNote $note): JsonResponse
    {
        abort_unless($note->print_job_id === $job->id, 404);

        $note->delete();

        \App\Models\ActivityLog::record('print.note_delete', "Deleted note from {$job->order_number}");

        return response()->json(['success' => true]);
    }

    public function storeManual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_code'        => ['nullable', 'string', 'max:100'],
            'product_description' => ['required', 'string', 'max:255'],
            'line_comment'        => ['nullable', 'string', 'max:2000'],
            'customer_name'       => ['nullable', 'string', 'max:255'],
            'customer_ref'        => ['nullable', 'string', 'max:255'],
            'order_number'        => ['nullable', 'string', 'max:100'],
            'quantity'            => ['required', 'integer', 'min:1'],
            'required_date'       => ['nullable', 'date'],
            'board'               => ['required', 'in:' . implode(',', array_keys(PrintJob::BOARDS))],
        ]);

        $job = PrintJob::create([
            'product_code'           => $data['product_code'] ?: null,
            'product_description'    => $data['product_description'],
            'line_comment'           => $data['line_comment'] ?: null,
            'customer_name'          => $data['customer_name'] ?: 'Manual',
            'customer_ref'           => $data['customer_ref'] ?: null,
            'order_number'           => $data['order_number'] ?: 'MANUAL',
            'order_quantity'         => $data['quantity'],
            'quantity_completed'     => 0,
            'required_date'          => $data['required_date'] ?: null,
            'original_required_date' => $data['required_date'] ?: null,
            'board'                  => $data['board'],
            'position'               => PrintJob::where('board', $data['board'])->max('position') + 1,
            'is_manual'              => true,
        ]);

        \App\Models\ActivityLog::record('print.manual_add', "Added manual job: {$job->product_description}");

        return response()->json(['success' => true, 'redirect' => route('print.index')]);
    }

    public function completeManual(PrintJob $job): JsonResponse
    {
        abort_unless($job->is_manual, 403);

        $job->closeOpenRuns();
        $job->update([
            'archived_at'    => now(),
            'archive_reason' => 'completed',
            'despatched_at'  => now()->toDateString(),
        ]);

        \App\Models\ActivityLog::record('print.manual_complete', "Completed manual job: {$job->product_description}");

        return response()->json(['success' => true]);
    }

    public function updateManual(Request $request, PrintJob $job): JsonResponse
    {
        abort_unless($job->is_manual, 403);

        $data = $request->validate([
            'product_code'        => ['nullable', 'string', 'max:100'],
            'product_description' => ['required', 'string', 'max:255'],
            'line_comment'        => ['nullable', 'string', 'max:2000'],
            'customer_name'       => ['nullable', 'string', 'max:255'],
            'customer_ref'        => ['nullable', 'string', 'max:255'],
            'order_number'        => ['nullable', 'string', 'max:100'],
            'order_quantity'      => ['required', 'integer', 'min:1'],
            'required_date'       => ['nullable', 'date'],
        ]);

        $job->update([
            'product_code'        => $data['product_code'] ?: null,
            'product_description' => $data['product_description'],
            'line_comment'        => $data['line_comment'] ?: null,
            'customer_name'       => $data['customer_name'] ?: 'Manual',
            'customer_ref'        => $data['customer_ref'] ?: null,
            'order_number'        => $data['order_number'] ?: 'MANUAL',
            'order_quantity'      => $data['order_quantity'],
            'required_date'       => $data['required_date'] ?: null,
        ]);

        \App\Models\ActivityLog::record('print.manual_add', "Edited manual job: {$job->product_description}");

        return response()->json(['success' => true]);
    }

    public function deleteManual(PrintJob $job): JsonResponse
    {
        abort_unless($job->is_manual, 403);

        $description = $job->product_description;
        $job->delete();

        \App\Models\ActivityLog::record('print.manual_archive', "Deleted manual job: {$description}");

        return response()->json(['success' => true]);
    }

    public function archiveManual(PrintJob $job): JsonResponse
    {
        abort_unless($job->is_manual, 403);

        $job->closeOpenRuns();
        $job->update([
            'archived_at'    => now(),
            'archive_reason' => 'deleted',
        ]);

        \App\Models\ActivityLog::record('print.manual_archive', "Archived manual job: {$job->product_description}");

        return response()->json(['success' => true]);
    }

    // ── Label generation ─────────────────────────────────────────────────────────

    public function downloadLabels(Request $request, PrintJob $job): Response
    {
        $branded     = $request->boolean('branded', true);
        $packSize    = max(1, (int) $request->input('pack_size', 100));
        $isUniverseal = $request->boolean('universeal', false)
                     || stripos($job->customer_name ?? '', 'universeal') !== false;

        $parsed = $this->parseJobComment($job->line_comment ?? '');

        if ($parsed['num_start'] !== null) {
            $uniqueLabels = [];
            $prefix = $parsed['num_prefix'];
            $width  = $parsed['num_width'];
            $start  = $parsed['num_start'];
            $end    = $parsed['num_end'];
            $count  = (int) ceil(($end - $start + 1) / $packSize);
            for ($i = 0; $i < $count; $i++) {
                $from           = $start + $i * $packSize;
                $to             = min($from + $packSize - 1, $end);
                $fmt            = $prefix . sprintf('%0' . $width . 'd', $from);
                $fmtTo          = $prefix . sprintf('%0' . $width . 'd', $to);
                $uniqueLabels[] = $isUniverseal
                    ? $fmt . '   ' . $fmtTo   // Universeal: wide space, no dash
                    : $fmt . ' - ' . $fmtTo;
            }
        } else {
            $count        = max(1, (int) ($job->order_quantity ?? 1));
            $uniqueLabels = array_fill(0, $count, null);
        }

        // Universeal: duplicate each label (×2)
        if ($isUniverseal) {
            $labels = [];
            foreach ($uniqueLabels as $lbl) {
                $labels[] = $lbl;
                $labels[] = $lbl;
            }
        } else {
            $labels = $uniqueLabels;
        }

        $pdf      = $this->buildLabelPdf($labels, $parsed['printed'], $branded, $isUniverseal, $job->customer_name ?? '');
        $filename = 'labels-' . preg_replace('/[^A-Za-z0-9-]/', '-', $job->order_number ?? 'job') . '.pdf';

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function parseJobComment(string $text): array
    {
        $result = ['num_start' => null, 'num_end' => null, 'num_prefix' => '', 'num_width' => 6, 'printed' => null];

        // Handles "NUMBERED  L 060001 - L 260000" and "Numbered: 337001 - 347000"
        if (preg_match('/NUMBERED[:\s]+([A-Z]+\s+)?(\d+)\s*[-–]+\s*(?:[A-Z]+\s+)?(\d+)/i', $text, $m)) {
            $result['num_prefix'] = isset($m[1]) ? rtrim($m[1]) : '';  // e.g. "L" (no trailing space)
            $result['num_start']  = (int) $m[2];
            $result['num_end']    = (int) $m[3];
            $result['num_width']  = strlen($m[2]);
        }

        // Handles "PRINTED  SJH 01 410 3000" and "Printed: 0300 300 3636"
        if (preg_match('/PRINTED[:\s]+(.+)/i', $text, $m)) {
            $result['printed'] = trim($m[1]);
        }

        return $result;
    }

    private function buildLabelPdf(array $labels, ?string $printed, bool $branded, bool $isUniverseal = false, string $customerName = ''): string
    {
        // Avery L7651 – 5 columns × 13 rows = 65 per page (standard sheet for all label types)
        $cols = 5;
        $lw   = 38.1;
        $lh  = 21.2;
        $ml  = 4.65;
        $mt  = 10.65;
        $cg  = 2.5;
        $per = $cols * 13;

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage();

        foreach ($labels as $idx => $range) {
            if ($idx > 0 && $idx % $per === 0) {
                $pdf->AddPage();
            }
            $pos = $idx % $per;
            $col = $pos % $cols;
            $row = intdiv($pos, $cols);
            $x   = $ml + $col * ($lw + $cg);
            $y   = $mt + $row * $lh;

            if ($isUniverseal) {
                $this->drawUniversealLabel($pdf, $x, $y, $lw, $lh, $customerName, $range, $printed);
            } else {
                $this->drawLabel($pdf, $x, $y, $lw, $lh, $range, $printed, $branded);
            }
        }

        return $pdf->Output('', 'S');
    }

    private function drawUniversealLabel(\FPDF $pdf, float $x, float $y, float $w, float $h, string $customerName, ?string $range, ?string $printed): void
    {
        $pad = 1.0;
        $iw  = $w - $pad * 2;
        $pdf->SetTextColor(0, 0, 0);

        // Three lines: customer name (top) / range (middle, large bold) / printed (bottom)
        $nameText  = strtoupper(explode(' ', trim($customerName))[0]); // first word e.g. "UNIVERSEAL"
        $lines = array_filter([
            ['text' => $nameText,    'style' => 'B', 'size' => 9.0,  'cellH' => 3.8],
            $range   ? ['text' => $range,   'style' => 'B', 'size' => 10.5, 'cellH' => 4.8] : null,
            $printed ? ['text' => $printed, 'style' => '',  'size' => 8.0,  'cellH' => 3.4] : null,
        ]);
        $lines  = array_values($lines);
        $totalH = array_sum(array_column($lines, 'cellH'));
        $lineY  = $y + ($h - $totalH) / 2;

        foreach ($lines as $line) {
            $size = $line['size'];
            $pdf->SetFont('Helvetica', $line['style'], $size);
            while ($size > 4.0 && $pdf->GetStringWidth($line['text']) > $w) {
                $size -= 0.5;
                $pdf->SetFont('Helvetica', $line['style'], $size);
            }
            $pdf->SetXY($x, $lineY);
            $pdf->Cell($w, $line['cellH'], $line['text'], 0, 0, 'C');
            $lineY += $line['cellH'];
        }
    }

    private function drawLabel(\FPDF $pdf, float $x, float $y, float $w, float $h, ?string $range, ?string $printed, bool $branded): void
    {
        $pad = 1.0;
        $iw  = $w - $pad * 2;
        $pdf->SetTextColor(0, 0, 0);

        if ($branded) {
            // Lines: JW Products / email / phone / range / printed
            $lines = [
                ['text' => 'JW Products',            'style' => 'B', 'size' => 8.0, 'cellH' => 3.0],
                ['text' => 'sales@jwproducts.co.uk', 'style' => '',  'size' => 5.5, 'cellH' => 2.2],
                ['text' => '01252 624 305',           'style' => '',  'size' => 5.5, 'cellH' => 2.2],
                ['text' => $range   ?? '',            'style' => 'B', 'size' => $range   ? 9.0 : 0.0, 'cellH' => 3.4],
                ['text' => $printed ?? '',            'style' => '',  'size' => $printed ? 7.0 : 0.0, 'cellH' => 2.6],
            ];
            $totalH = array_sum(array_column($lines, 'cellH'));
            $lineY  = $y + ($h - $totalH) / 2;

            foreach ($lines as $line) {
                if ($line['size'] < 0.5 || $line['text'] === '') {
                    $lineY += $line['cellH'];
                    continue;
                }
                $size = $line['size'];
                $pdf->SetFont('Helvetica', $line['style'], $size);
                while ($size > 3.5 && $pdf->GetStringWidth($line['text']) > $w) {
                    $size -= 0.5;
                    $pdf->SetFont('Helvetica', $line['style'], $size);
                }
                $pdf->SetXY($x, $lineY);
                $pdf->Cell($w, $line['cellH'], $line['text'], 0, 0, 'C');
                $lineY += $line['cellH'];
            }
        } else {
            // Unbranded: range (big) + printed text, vertically centred
            $lines = array_filter([
                $range   ? ['text' => $range,   'style' => 'B', 'size' => 11.0, 'cellH' => 4.8] : null,
                $printed ? ['text' => $printed, 'style' => '',  'size' => 8.5,  'cellH' => 3.6] : null,
            ]);
            $lines  = array_values($lines);
            $totalH = array_sum(array_column($lines, 'cellH'));
            $lineY  = $y + ($h - $totalH) / 2;

            foreach ($lines as $line) {
                $size = $line['size'];
                $pdf->SetFont('Helvetica', $line['style'], $size);
                while ($size > 3.5 && $pdf->GetStringWidth($line['text']) > $w) {
                    $size -= 0.5;
                    $pdf->SetFont('Helvetica', $line['style'], $size);
                }
                $pdf->SetXY($x, $lineY);
                $pdf->Cell($w, $line['cellH'], $line['text'], 0, 0, 'C');
                $lineY += $line['cellH'];
            }
        }
    }
}
