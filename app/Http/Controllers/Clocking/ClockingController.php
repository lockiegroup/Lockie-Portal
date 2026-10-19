<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ClockingAbsentOverride;
use App\Models\ClockingAlertLog;
use App\Models\ClockingDevice;
use App\Models\ClockingEmployee;
use App\Models\ClockingPunch;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClockingController extends Controller
{
    /** Admin dashboard — who's in now + absent today + recent alerts + device registration. */
    public function index(): View
    {
        $tz     = config('app.timezone');
        $now    = now($tz);
        $today  = $now->format('Y-m-d');
        $dayKey = strtolower($now->format('D'));

        $scopedCategoryIds = $this->scopedCategoryIds();

        // Who's currently clocked in
        $inNow = ClockingPunch::with('employee.category')
            ->whereNull('voided_at')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('clocking_punches')
                    ->whereNull('voided_at')
                    ->groupBy('employee_id');
            })
            ->where('direction', 'in')
            ->when($scopedCategoryIds, fn($q) => $q->whereHas('employee', fn($eq) => $eq->whereIn('category_id', $scopedCategoryIds)))
            ->orderBy('punched_at')
            ->get();

        // Scheduled today but not yet clocked in (excluding absent overrides)
        $scheduledToday = ClockingEmployee::with('category')
            ->where('is_active', true)
            ->when($scopedCategoryIds, fn($q) => $q->whereIn('category_id', $scopedCategoryIds))
            ->get()
            ->filter(fn($emp) => !empty(($emp->schedule ?? [])[$dayKey]['start']));

        $clockedInIds = ClockingPunch::whereIn('employee_id', $scheduledToday->pluck('id'))
            ->where('direction', 'in')
            ->whereNull('voided_at')
            ->whereDate('punched_at', $today)
            ->pluck('employee_id')
            ->unique();

        $absentIds = ClockingAbsentOverride::whereIn('employee_id', $scheduledToday->pluck('id'))
            ->where('date', $today)
            ->pluck('employee_id')
            ->unique();

        $notClockedIn = $scheduledToday
            ->filter(function ($emp) use ($clockedInIds, $absentIds, $dayKey, $now, $tz) {
                if ($clockedInIds->contains($emp->id) || $absentIds->contains($emp->id)) return false;
                $start = $emp->schedule[$dayKey]['start'] ?? null;
                if (!$start) return false;
                $scheduledStart = Carbon::createFromFormat('H:i', $start, $tz)->setDateFrom($now);
                // Only show if shift starts within 15 minutes or has already started
                return $now->gte($scheduledStart->copy()->subMinutes(15));
            })
            ->sortBy(fn($emp) => ($emp->schedule[$dayKey]['start'] ?? '99:99'))
            ->values();

        // Recent alert history (both absent + clockout)
        $recentAlerts = ClockingAlertLog::with('employee')
            ->when($scopedCategoryIds, fn($q) => $q->whereHas('employee', fn($eq) => $eq->whereIn('category_id', $scopedCategoryIds)))
            ->orderByDesc('sent_at')
            ->limit(20)
            ->get();

        $isAdmin = auth()->user()->can('clocking_admin');
        $devices = $isAdmin ? ClockingDevice::orderBy('name')->get() : collect();

        return view('clocking.index', compact('inNow', 'notClockedIn', 'dayKey', 'now', 'recentAlerts', 'devices', 'isAdmin'));
    }

    private function scopedCategoryIds(): ?array
    {
        $user = auth()->user();
        if ($user->can('clocking_admin')) return null; // null = no restriction
        return $user->clocking_category_ids ?: [];
    }

    /** Generate a one-time registration code for a new or existing device. */
    public function generateCode(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:80']);

        $device = ClockingDevice::create(['name' => $request->name, 'is_active' => true]);
        $code   = $device->generateRegistrationCode();

        return response()->json(['ok' => true, 'code' => $code, 'device_id' => $device->id]);
    }

    /** Delete a device. */
    public function revokeDevice(ClockingDevice $device): JsonResponse
    {
        $device->delete();
        return response()->json(['ok' => true]);
    }
}
