<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ClockingDevice;
use App\Models\ClockingPunch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClockingController extends Controller
{
    /** Admin dashboard — who's in now + device registration. */
    public function index(): View
    {
        $inNow = ClockingPunch::with('employee')
            ->whereNull('voided_at')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('clocking_punches')
                    ->whereNull('voided_at')
                    ->groupBy('employee_id');
            })
            ->where('direction', 'in')
            ->orderBy('punched_at')
            ->get();

        $devices = ClockingDevice::orderBy('name')->get();

        return view('clocking.index', compact('inNow', 'devices'));
    }

    /** Generate a one-time registration code for a new or existing device. */
    public function generateCode(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:80']);

        $device = ClockingDevice::create(['name' => $request->name, 'is_active' => true]);
        $code   = $device->generateRegistrationCode();

        return response()->json(['ok' => true, 'code' => $code, 'device_id' => $device->id]);
    }

    /** Revoke a device. */
    public function revokeDevice(ClockingDevice $device): JsonResponse
    {
        $device->update(['is_active' => false, 'token_hash' => null]);
        return response()->json(['ok' => true]);
    }
}
