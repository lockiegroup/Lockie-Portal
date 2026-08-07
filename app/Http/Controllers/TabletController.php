<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Models\PrintJobRun;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TabletController extends Controller
{
    private const SESSION_TTL_HOURS = 12;

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
