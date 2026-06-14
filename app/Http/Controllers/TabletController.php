<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Models\PrintJobRun;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class TabletController extends Controller
{
    private function sessionKey(string $machine): string
    {
        return "tablet_op_{$machine}";
    }

    private function getOperator(string $machine): ?User
    {
        $userId = session($this->sessionKey($machine));
        return $userId ? User::find($userId) : null;
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

        $pin = $request->input('pin', '');

        if (strlen($pin) < 4) {
            return back()->with('pin_error', 'PIN must be at least 4 digits.');
        }

        $user = User::whereNotNull('operator_pin')
            ->get()
            ->first(fn($u) => Hash::check($pin, $u->operator_pin));

        if (!$user) {
            return back()->with('pin_error', 'Incorrect PIN. Please try again.');
        }

        session([$this->sessionKey($machine) => $user->id]);

        return redirect()->route('tablet.show', $machine);
    }

    public function logout(string $machine): RedirectResponse
    {
        session()->forget($this->sessionKey($machine));
        return redirect()->route('tablet.show', $machine);
    }

    public function startJob(string $machine, PrintJob $job): RedirectResponse
    {
        if (!$this->validMachine($machine)) abort(404);
        $operator = $this->getOperator($machine);
        if (!$operator) return redirect()->route('tablet.show', $machine);

        $active = $job->runs()->where('machine', $machine)->whereNull('ended_at')->first();
        if ($active) {
            return redirect()->route('tablet.show', $machine)->with('error', 'Job is already running.');
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
            'pause_reason'   => 'nullable|string|max:255',
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
        ]);

        $run = $job->runs()->where('machine', $machine)->whereNull('ended_at')->first();
        if ($run) {
            $run->update([
                'ended_at'       => now(),
                'end_reason'     => 'complete',
                'packs_produced' => $data['packs_produced'] ?? null,
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
