<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ClockingDevice;
use App\Models\ClockingFob;
use App\Models\ClockingPunch;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KioskController extends Controller
{
    /** Resolve the authenticated device from the request cookie or header (header used in tests). */
    private function device(Request $request): ?ClockingDevice
    {
        $token = $request->cookie('clocking_device')
              ?? $request->header('X-Clocking-Device');
        if (!$token) return null;

        return ClockingDevice::where('token_hash', hash('sha256', $token))
            ->where('is_active', true)
            ->first();
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public function show(Request $request): View
    {
        $device = $this->device($request);
        if (!$device) {
            return view('clocking.kiosk-unregistered');
        }
        $device->touchSeen();
        return view('clocking.kiosk');
    }

    // ── Device registration ───────────────────────────────────────────────────

    /**
     * Exchange a one-time registration code for a long-lived device cookie.
     * Called by the kiosk form on the unregistered screen.
     */
    public function activate(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));
        if (strlen($code) !== 6) {
            return response()->json(['error' => 'Invalid code.'], 422);
        }

        $device = ClockingDevice::where('registration_code', $code)
            ->where('is_active', true)
            ->where('registration_code_expires_at', '>', now())
            ->first();

        if (!$device) {
            return response()->json(['error' => 'Code not found or expired.'], 422);
        }

        $token = $device->issueToken();
        $device->touchSeen();

        return response()
            ->json(['ok' => true, 'device' => $device->name])
            ->cookie('clocking_device', $token, 60 * 24 * 365, '/', null, null, true); // 1 year, httpOnly
    }

    // ── Kiosk API ─────────────────────────────────────────────────────────────

    /** Roster: returns fob UID → {employee_id, name, state, since} for active fobs. */
    public function roster(Request $request): JsonResponse
    {
        $device = $this->device($request);
        if (!$device) {
            return response()->json(['error' => 'Device not registered.'], 403);
        }
        $device->touchSeen();

        // Fetch all active fobs with their employee and that employee's last punch
        $fobs = ClockingFob::where('is_active', true)
            ->whereNotNull('employee_id')
            ->with(['employee:id,name,is_active'])
            ->get();

        // Last non-voided punch per employee (single query)
        $employeeIds = $fobs->pluck('employee_id')->unique()->values();
        $lastPunches = ClockingPunch::whereIn('employee_id', $employeeIds)
            ->whereNull('voided_at')
            ->orderBy('punched_at')
            ->get()
            ->groupBy('employee_id')
            ->map(fn($ps) => $ps->sortByDesc('punched_at')->first());

        $roster = [];
        foreach ($fobs as $fob) {
            if (!$fob->employee || !$fob->employee->is_active) continue;
            $last  = $lastPunches->get($fob->employee_id);
            $roster[$fob->uid] = [
                'employee_id' => $fob->employee_id,
                'name'        => $fob->employee->name,
                'state'       => $last?->direction ?? 'out',
                'since'       => $last?->punched_at?->toIso8601String(),
            ];
        }

        return response()->json(['fobs' => $roster]);
    }

    /**
     * Record a punch. Idempotent on client_uuid.
     *
     * POST body: { fob_uid, direction, punched_at (ISO UTC), client_uuid }
     */
    public function punch(Request $request): JsonResponse
    {
        $device = $this->device($request);
        if (!$device) {
            return response()->json(['error' => 'Device not registered.'], 403);
        }

        $data = $request->validate([
            'fob_uid'     => 'required|string|max:20',
            'direction'   => 'required|in:in,out',
            'punched_at'  => 'required|date',
            'client_uuid' => 'required|string|max:36',
        ]);

        // Idempotency: return existing record unchanged
        $existing = ClockingPunch::where('client_uuid', $data['client_uuid'])->first();
        if ($existing) {
            $device->touchSeen();
            return response()->json([
                'ok'        => true,
                'duplicate' => true,
                'employee'  => $existing->employee->name ?? '',
                'direction' => $existing->direction,
                'punched_at'=> $existing->punched_at->toIso8601String(),
                'flagged'   => $existing->is_flagged,
            ]);
        }

        // Find fob
        $fob = ClockingFob::where('uid', $data['fob_uid'])->first();
        if (!$fob) {
            return response()->json(['error' => 'unknown_fob', 'message' => 'Fob not found.'], 404);
        }
        if (!$fob->is_active) {
            return response()->json(['error' => 'inactive_fob', 'message' => 'Fob is deactivated.'], 422);
        }
        if (!$fob->employee || !$fob->employee->is_active) {
            return response()->json(['error' => 'inactive_employee', 'message' => 'Employee is inactive.'], 422);
        }

        $employee  = $fob->employee;
        $punchedAt = Carbon::parse($data['punched_at'])->utc();
        $direction = $data['direction'];

        // 60-second debounce: same direction within 60 s of this punch time
        $debounce = ClockingPunch::where('employee_id', $employee->id)
            ->where('direction', $direction)
            ->whereNull('voided_at')
            ->whereBetween('punched_at', [
                $punchedAt->copy()->subSeconds(60),
                $punchedAt->copy()->addSeconds(60),
            ])
            ->first();

        if ($debounce) {
            $device->touchSeen();
            $localTime = $debounce->punched_at->setTimezone('Europe/London')->format('H:i');
            return response()->json([
                'error'      => 'debounce',
                'message'    => "Already clocked " . strtoupper($direction) . " at {$localTime}.",
                'employee'   => $employee->name,
                'punched_at' => $debounce->punched_at->toIso8601String(),
            ], 409);
        }

        // Check state mismatch: server's current state vs client's intended direction
        $serverState = ClockingPunch::where('employee_id', $employee->id)
            ->whereNull('voided_at')
            ->latest('punched_at')
            ->value('direction'); // 'in' | 'out' | null

        // If server says they're already in this direction, flag (but still record)
        // Treat null (no punches yet) as 'out' — the default resting state
        $flagged    = false;
        $flagReason = null;
        if (($serverState ?? 'out') === $direction) {
            $flagged    = true;
            $flagReason = "State mismatch: server had '{$serverState}', client clocked '{$direction}'.";
        }

        $punch = ClockingPunch::create([
            'employee_id' => $employee->id,
            'fob_id'      => $fob->id,
            'direction'   => $direction,
            'punched_at'  => $punchedAt,
            'device_id'   => $device->id,
            'client_uuid' => $data['client_uuid'],
            'source'      => 'kiosk',
            'is_flagged'  => $flagged,
            'flag_reason' => $flagReason,
        ]);

        $device->touchSeen();

        return response()->json([
            'ok'         => true,
            'employee'   => $employee->name,
            'direction'  => $direction,
            'punched_at' => $punch->punched_at->toIso8601String(),
            'flagged'    => $flagged,
        ]);
    }
}
