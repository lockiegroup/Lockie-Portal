<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Models\PrintJobRun;
use App\Models\PrintScheduleSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TabletController extends Controller
{
    private const SESSION_TTL_HOURS = 12;

    private const DAY_WEIGHTS = [
        1 => 1.0,
        2 => 1.0,
        3 => 1.0,
        4 => 1.0,
        5 => 5.0 / 8.0,
    ];

    private function sessionKey(string $machine): string
    {
        return "tablet_op_{$machine}";
    }

    private function sessionAtKey(string $machine): string
    {
        return "tablet_op_{$machine}_at";
    }

    private function getOperator(string $machine): ?User
    {
        $userId  = session($this->sessionKey($machine));
        $loginAt = session($this->sessionAtKey($machine));

        if (!$userId) return null;

        // Auto-logout after 12 hours
        if (!$loginAt || (now()->timestamp - $loginAt) > self::SESSION_TTL_HOURS * 3600) {
            session()->forget([$this->sessionKey($machine), $this->sessionAtKey($machine)]);
            return null;
        }

        return User::find($userId);
    }

    private function validMachine(string $machine): bool
    {
        return in_array($machine, PrintJob::MACHINES, true);
    }

    public function show(string $machine)
    {
        if (!$this->validMachine($machine)) {
            abort(404);
        }

        $operator    = $this->getOperator($machine);
        $machineName = ucwords(str_replace('_', ' ', $machine));

        $jobs = PrintJob::active()
            ->where('board', $machine)
            ->orderBy('position')
            ->with([
                'runs' => fn($q) => $q->where('machine', $machine)
                                      ->orderBy('started_at')
                                      ->with('user'),
            ])
            ->get();

        return view('tablet.show', compact('machine', 'machineName', 'operator', 'jobs'));
    }

    public function pinLogin(string $machine, Request $request): RedirectResponse
    {
        if (!$this->validMachine($machine)) {
            abort(404);
        }

        $pin     = $request->input('pin', '');
        $rateKey = 'tablet-pin:' . $request->ip();

        // Check lockout before anything else
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            $minutes = ceil($seconds / 60);
            return back()->with('pin_error', "Too many failed attempts. Try again in {$minutes} minute(s).");
        }

        if (strlen($pin) < 6) {
            RateLimiter::hit($rateKey, 300);
            return back()->with('pin_error', 'PIN must be at least 6 digits.');
        }

        $user = User::whereNotNull('operator_pin')
            ->get()
            ->first(fn($u) => Hash::check($pin, $u->operator_pin));

        if (!$user) {
            RateLimiter::hit($rateKey, 300); // 5 minute decay window
            $remaining = 5 - RateLimiter::attempts($rateKey);
            $msg = $remaining > 0
                ? "Incorrect PIN. {$remaining} attempt(s) remaining."
                : 'Incorrect PIN. Please try again.';
            return back()->with('pin_error', $msg);
        }

        RateLimiter::clear($rateKey);
        session([
            $this->sessionKey($machine)   => $user->id,
            $this->sessionAtKey($machine) => now()->timestamp,
        ]);

        return redirect()->route('tablet.show', $machine);
    }

    public function logout(string $machine): RedirectResponse
    {
        session()->forget([$this->sessionKey($machine), $this->sessionAtKey($machine)]);
        return redirect()->route('tablet.show', $machine);
    }

    public function startJob(string $machine, PrintJob $job): RedirectResponse
    {
        if (!$this->validMachine($machine)) abort(404);
        $operator = $this->getOperator($machine);
        if (!$operator) return redirect()->route('tablet.show', $machine);

        // Block if this exact job is already running
        $active = $job->runs()->where('machine', $machine)->whereNull('ended_at')->first();
        if ($active) {
            return redirect()->route('tablet.show', $machine)->with('error', 'This job is already running.');
        }

        // Block if a different job is already running on this machine
        $otherActive = PrintJobRun::where('machine', $machine)
            ->where('print_job_id', '!=', $job->id)
            ->whereNull('ended_at')
            ->with('printJob:id,customer_name')
            ->first();
        if ($otherActive) {
            return redirect()->route('tablet.show', $machine)
                ->with('start_blocked', $otherActive->printJob?->customer_name ?? 'another job');
        }

        PrintJobRun::create([
            'print_job_id' => $job->id,
            'user_id'      => $operator->id,
            'machine'      => $machine,
            'started_at'   => now(),
        ]);

        return redirect()->route('tablet.show', $machine);
    }

    public function pauseJob(string $machine, Request $request, PrintJob $job): RedirectResponse
    {
        if (!$this->validMachine($machine)) abort(404);
        $operator = $this->getOperator($machine);
        if (!$operator) return redirect()->route('tablet.show', $machine);

        $data = $request->validate([
            'packs_produced' => 'nullable|integer|min:0',
            'pause_reason'   => 'required|string|min:1|max:255',
        ]);

        $run = $job->runs()->where('machine', $machine)->whereNull('ended_at')->first();
        if ($run) {
            $run->update([
                'ended_at'       => now(),
                'end_reason'     => 'pause',
                'packs_produced' => $data['packs_produced'] ?? null,
                'pause_reason'   => $data['pause_reason'] ?? null,
            ]);
        }

        return redirect()->route('tablet.show', $machine);
    }

    public function resumeJob(string $machine, PrintJob $job): RedirectResponse
    {
        if (!$this->validMachine($machine)) abort(404);
        $operator = $this->getOperator($machine);
        if (!$operator) return redirect()->route('tablet.show', $machine);

        $otherActive = PrintJobRun::where('machine', $machine)
            ->where('print_job_id', '!=', $job->id)
            ->whereNull('ended_at')
            ->with('printJob:id,customer_name')
            ->first();
        if ($otherActive) {
            return redirect()->route('tablet.show', $machine)
                ->with('start_blocked', $otherActive->printJob?->customer_name ?? 'another job');
        }

        PrintJobRun::create([
            'print_job_id' => $job->id,
            'user_id'      => $operator->id,
            'machine'      => $machine,
            'started_at'   => now(),
        ]);

        return redirect()->route('tablet.show', $machine);
    }

    public function endJob(string $machine, Request $request, PrintJob $job): RedirectResponse
    {
        if (!$this->validMachine($machine)) abort(404);
        $operator = $this->getOperator($machine);
        if (!$operator) return redirect()->route('tablet.show', $machine);

        $data = $request->validate([
            'packs_produced' => 'nullable|integer|min:0',
            'fully_complete' => 'nullable|boolean',
        ]);

        $fullyComplete = filter_var($request->input('fully_complete', false), FILTER_VALIDATE_BOOLEAN);

        // Active run (currently running)
        $run = $job->runs()->where('machine', $machine)->whereNull('ended_at')->first();
        if ($run) {
            $run->update([
                'ended_at'       => now(),
                'end_reason'     => 'complete',
                'packs_produced' => $data['packs_produced'] ?? null,
                'fully_complete' => $fullyComplete,
            ]);
            return redirect()->route('tablet.show', $machine);
        }

        // Paused run — promote it to complete
        $pausedRun = $job->runs()
            ->where('machine', $machine)
            ->where('end_reason', 'pause')
            ->orderByDesc('ended_at')
            ->first();

        if ($pausedRun) {
            $pausedRun->update([
                'end_reason'     => 'complete',
                'packs_produced' => $data['packs_produced'] ?? $pausedRun->packs_produced,
                'pause_reason'   => null,
                'fully_complete' => $fullyComplete,
            ]);
        }

        return redirect()->route('tablet.show', $machine);
    }

    private function productSize(string $code): int
    {
        if (preg_match('/(200|300|370)/', $code, $m)) {
            return (int) $m[1];
        }
        return 200;
    }

    private function machineGroup(string $machine): string
    {
        return str_starts_with($machine, 'baby') ? 'baby' : 'auto';
    }

    private function loadSizeThroughputs(): array
    {
        return [
            'auto' => [
                200 => (int) PrintScheduleSetting::getValue('throughput_auto_200', '350'),
                300 => (int) PrintScheduleSetting::getValue('throughput_auto_300', '350'),
                370 => (int) PrintScheduleSetting::getValue('throughput_auto_370', '350'),
            ],
            'baby' => [
                200 => (int) PrintScheduleSetting::getValue('throughput_baby_200', '180'),
                300 => (int) PrintScheduleSetting::getValue('throughput_baby_300', '180'),
                370 => (int) PrintScheduleSetting::getValue('throughput_baby_370', '180'),
            ],
        ];
    }

    private function getThroughputForMachineCode(string $machine, ?string $productCode, array $throughputs): int
    {
        $group = $this->machineGroup($machine);
        $size  = $this->productSize($productCode ?? '');
        return $throughputs[$group][$size] ?? 350;
    }

    private function packsOnMachineSince(string $machine, Carbon $since): int
    {
        $runs = PrintJobRun::where('machine', $machine)
            ->where('started_at', '>=', $since)
            ->get(['id', 'print_job_id', 'ended_at', 'packs_produced', 'progress_packs']);

        if ($runs->isEmpty()) return 0;

        $jobIds = $runs->pluck('print_job_id')->unique()->filter()->values();

        $baselines = [];
        if ($jobIds->isNotEmpty()) {
            $lastRunIds = PrintJobRun::whereIn('print_job_id', $jobIds)
                ->where('started_at', '<', $since)
                ->whereNotNull('packs_produced')
                ->selectRaw('print_job_id, MAX(id) as max_id')
                ->groupBy('print_job_id')
                ->pluck('max_id');

            if ($lastRunIds->isNotEmpty()) {
                $baselines = PrintJobRun::whereIn('id', $lastRunIds)
                    ->pluck('packs_produced', 'print_job_id')
                    ->map(fn($v) => (int) $v)
                    ->toArray();
            }
        }

        $total = 0;
        foreach ($runs->groupBy('print_job_id') as $jobId => $jobRuns) {
            $baseline       = $baselines[$jobId] ?? 0;
            $activeRun      = $jobRuns->firstWhere('ended_at', null);
            $lastEndedPacks = $jobRuns->filter(fn($r) => $r->packs_produced !== null && $r->ended_at !== null)
                ->sortByDesc(fn($r) => $r->id)->first()?->packs_produced ?? 0;

            $activePacks   = $activeRun?->progress_packs;
            $includeActive = $activePacks !== null && $activePacks > $lastEndedPacks;
            $currTotal     = $includeActive ? $activePacks : ($lastEndedPacks ?: null);

            if ($currTotal !== null) {
                $total += max(0, $currTotal - $baseline);
            }
        }

        return $total;
    }

    private function operatorStatsForPeriod(string $machine, int $userId, Carbon $since, array $throughputs): array
    {
        // Fetch all runs on this machine in the period (all users) ordered by job then id,
        // so we can compute per-run packs deltas and attribute them to each operator.
        $allRuns = PrintJobRun::where('machine', $machine)
            ->where('started_at', '>=', $since)
            ->orderBy('print_job_id')
            ->orderBy('id')
            ->get(['id', 'print_job_id', 'user_id', 'ended_at', 'packs_produced', 'progress_packs',
                   'started_at']);

        $jobIds = $allRuns->pluck('print_job_id')->unique()->filter()->values();
        $baselines = [];
        if ($jobIds->isNotEmpty()) {
            $lastRunIds = PrintJobRun::whereIn('print_job_id', $jobIds)
                ->where('started_at', '<', $since)
                ->whereNotNull('packs_produced')
                ->selectRaw('print_job_id, MAX(id) as max_id')
                ->groupBy('print_job_id')
                ->pluck('max_id');
            if ($lastRunIds->isNotEmpty()) {
                $baselines = PrintJobRun::whereIn('id', $lastRunIds)
                    ->pluck('packs_produced', 'print_job_id')
                    ->map(fn($v) => (int) $v)
                    ->toArray();
            }
        }

        // Walk through each job's runs in order, tracking the cumulative baseline.
        // Each run's packs contribution = packs_produced (or progress_packs) - previous total.
        $opPacks = 0;
        foreach ($allRuns->groupBy('print_job_id') as $jobId => $jobRuns) {
            $prev = $baselines[$jobId] ?? 0;
            foreach ($jobRuns->sortBy('id') as $run) {
                $isActive = $run->ended_at === null;
                $curr = $isActive
                    ? ($run->progress_packs ?? $prev)
                    : ($run->packs_produced ?? $prev);
                $delta = max(0, $curr - $prev);
                if ($run->user_id === $userId) {
                    $opPacks += $delta;
                }
                if (!$isActive && $run->packs_produced !== null) {
                    $prev = $run->packs_produced;
                }
            }
        }

        // Hours run and per-job-weighted target for this operator
        $opRuns = $allRuns->where('user_id', $userId)->load('printJob:id,product_code');
        // load() doesn't work on a collection slice — re-query just operator runs with relation
        $opRunsFull = PrintJobRun::where('machine', $machine)
            ->where('user_id', $userId)
            ->where('started_at', '>=', $since)
            ->with('printJob:id,product_code')
            ->get();

        $opSecs = 0;
        $opTargetPacks = 0.0;
        foreach ($opRunsFull as $run) {
            $secs = $run->ended_at
                ? abs((int) $run->started_at->diffInSeconds($run->ended_at))
                : (int) now()->diffInSeconds($run->started_at);
            $opSecs += $secs;
            $tp = $this->getThroughputForMachineCode($machine, $run->printJob?->product_code, $throughputs);
            $opTargetPacks += ($secs / 3600) * ($tp / 8.0);
        }

        $opTarget = (int) round($opTargetPacks);
        return [
            'packs'     => $opPacks,
            'target'    => $opTarget,
            'pct'       => $opTarget > 0 ? min(150, (int) round($opPacks / $opTarget * 100)) : null,
            'hours_run' => round($opSecs / 3600, 1),
        ];
    }

    public function stats(string $machine): \Illuminate\Http\JsonResponse
    {
        if (!$this->validMachine($machine)) abort(404);

        $throughputs = $this->loadSizeThroughputs();

        // Determine product code to use for the target (currently running or most recent today)
        $productCode = PrintJobRun::where('machine', $machine)
            ->whereNull('ended_at')
            ->with('printJob:id,product_code')
            ->latest('started_at')
            ->first()?->printJob?->product_code;

        if (!$productCode) {
            $productCode = PrintJobRun::where('machine', $machine)
                ->whereDate('started_at', today())
                ->with('printJob:id,product_code')
                ->latest('started_at')
                ->first()?->printJob?->product_code;
        }

        $dailyTarget = $this->getThroughputForMachineCode($machine, $productCode, $throughputs);
        $productSize = $this->productSize($productCode ?? '');

        $todayStart = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $todayPacks = $this->packsOnMachineSince($machine, $todayStart);
        $monthPacks = $this->packsOnMachineSince($machine, $monthStart);

        // Month target: sum per-run contribution using each run's own product-size throughput.
        // This correctly weights a mix of e.g. 10h on 200mm (400/day) + 6h on 300mm (344/day)
        // rather than applying a single rate to the total hours.
        $monthRuns = PrintJobRun::where('machine', $machine)
            ->where('started_at', '>=', $monthStart)
            ->with('printJob:id,product_code')
            ->get();

        $monthTargetPacks = 0.0;
        $totalSecsThisMonth = 0;
        foreach ($monthRuns as $run) {
            $secs = $run->ended_at
                ? abs((int) $run->started_at->diffInSeconds($run->ended_at))
                : (int) now()->diffInSeconds($run->started_at);
            $totalSecsThisMonth += $secs;
            $tp = $this->getThroughputForMachineCode($machine, $run->printJob?->product_code, $throughputs);
            $monthTargetPacks += ($secs / 3600) * ($tp / 8.0);
        }

        $hoursRunThisMonth = round($totalSecsThisMonth / 3600, 1);
        $monthTarget       = (int) round($monthTargetPacks);

        // Operator-specific stats (from tablet session)
        $operator   = $this->getOperator($machine);
        $opDayStats = null;
        $opMonthStats = null;
        if ($operator) {
            $opDayStats   = $this->operatorStatsForPeriod($machine, $operator->id, $todayStart, $throughputs);
            $opMonthStats = $this->operatorStatsForPeriod($machine, $operator->id, $monthStart, $throughputs);
        }

        return response()->json([
            'machine' => [
                'day' => [
                    'packs'  => $todayPacks,
                    'target' => $dailyTarget,
                    'pct'    => $dailyTarget > 0
                        ? min(150, (int) round($todayPacks / $dailyTarget * 100))
                        : null,
                ],
                'month' => [
                    'packs'     => $monthPacks,
                    'target'    => $monthTarget,
                    'pct'       => $monthTarget > 0
                        ? min(150, (int) round($monthPacks / $monthTarget * 100))
                        : null,
                    'hours_run' => $hoursRunThisMonth,
                ],
            ],
            'operator' => $operator ? [
                'name'  => $operator->name,
                'day'   => $opDayStats,
                'month' => $opMonthStats,
            ] : null,
            'product_size' => $productSize,
            'daily_target' => $dailyTarget,
        ]);
    }

    public function jobsHash(string $machine): \Illuminate\Http\JsonResponse
    {
        if (!$this->validMachine($machine)) abort(404);

        $jobs = PrintJob::active()
            ->where('board', $machine)
            ->orderBy('position')
            ->get(['id', 'position']);

        $hash = md5($jobs->map(fn($j) => $j->id . ':' . $j->position)->implode(','));

        return response()->json(['hash' => $hash, 'count' => $jobs->count()]);
    }

    public function updateProgress(string $machine, Request $request, PrintJob $job): RedirectResponse
    {
        if (!$this->validMachine($machine)) abort(404);
        $operator = $this->getOperator($machine);
        if (!$operator) return redirect()->route('tablet.show', $machine);

        $data = $request->validate([
            'progress_packs' => 'required|integer|min:0',
        ]);

        $run = $job->runs()->where('machine', $machine)->whereNull('ended_at')->first();
        if ($run) {
            $now = now();
            $run->update([
                'progress_packs' => $data['progress_packs'],
                'progress_at'    => $now,
            ]);
            \App\Models\PrintJobRunProgress::create([
                'print_job_run_id' => $run->id,
                'packs_cumulative' => $data['progress_packs'],
                'logged_at'        => $now,
            ]);
        }

        return redirect()->route('tablet.show', $machine);
    }

    public function correctPacks(string $machine, Request $request, PrintJob $job): RedirectResponse
    {
        if (!$this->validMachine($machine)) abort(404);
        $operator = $this->getOperator($machine);
        if (!$operator) return redirect()->route('tablet.show', $machine);

        $data = $request->validate([
            'packs_produced' => 'required|integer|min:0',
        ]);

        $lastPausedRun = $job->runs()
            ->where('machine', $machine)
            ->where('end_reason', 'pause')
            ->orderByDesc('ended_at')
            ->first();

        if ($lastPausedRun) {
            $original = $lastPausedRun->packs_produced;
            $lastPausedRun->update([
                'packs_produced'       => $data['packs_produced'],
                'packs_corrected_from' => $original,
            ]);
        }

        return redirect()->route('tablet.show', $machine);
    }

    public function handoverJob(string $machine, Request $request, PrintJob $job): RedirectResponse
    {
        if (!$this->validMachine($machine)) abort(404);
        $operator = $this->getOperator($machine);
        if (!$operator) return redirect()->route('tablet.show', $machine);

        $data = $request->validate([
            'packs_produced' => 'nullable|integer|min:0',
            'new_pin'        => 'required|string',
        ]);

        $newOperator = User::whereNotNull('operator_pin')
            ->get()
            ->first(fn($u) => Hash::check($data['new_pin'], $u->operator_pin));

        if (!$newOperator) {
            return back()->with('handover_error', 'Incorrect PIN for incoming operator. Handover cancelled.');
        }

        $run = $job->runs()->where('machine', $machine)->whereNull('ended_at')->first();
        if ($run) {
            $run->update([
                'ended_at'       => now(),
                'end_reason'     => 'handover',
                'packs_produced' => $data['packs_produced'] ?? null,
            ]);
        }

        session([$this->sessionKey($machine) => $newOperator->id]);

        PrintJobRun::create([
            'print_job_id' => $job->id,
            'user_id'      => $newOperator->id,
            'machine'      => $machine,
            'started_at'   => now(),
        ]);

        return redirect()->route('tablet.show', $machine);
    }
}
