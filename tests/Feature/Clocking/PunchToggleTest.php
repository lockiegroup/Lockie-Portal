<?php

namespace Tests\Feature\Clocking;

use App\Models\ClockingDevice;
use App\Models\ClockingEmployee;
use App\Models\ClockingFob;
use App\Models\ClockingPunch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PunchToggleTest extends TestCase
{
    use RefreshDatabase;

    private function makeDevice(): array
    {
        $device = ClockingDevice::create(['name' => 'Test Kiosk', 'is_active' => true]);
        $token  = $device->issueToken();
        return [$device, $token];
    }

    private function makeEmployee(string $name = 'Jane Smith'): ClockingEmployee
    {
        return ClockingEmployee::create(['name' => $name, 'is_active' => true]);
    }

    private function makeFob(ClockingEmployee $employee, string $uid = '0123456789'): ClockingFob
    {
        return ClockingFob::create([
            'employee_id' => $employee->id,
            'uid'         => $uid,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);
    }

    private function punch(string $token, string $uid, string $direction, string $uuid = null, string $at = null): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('X-Clocking-Device', $token)
            ->postJson('/clocking/kiosk/punch', [
                'fob_uid'     => $uid,
                'direction'   => $direction,
                'punched_at'  => $at ?? now()->toISOString(),
                'client_uuid' => $uuid ?? \Illuminate\Support\Str::uuid()->toString(),
            ]);
    }

    public function test_first_tap_clocks_in(): void
    {
        [$device, $token] = $this->makeDevice();
        $emp = $this->makeEmployee();
        $fob = $this->makeFob($emp);

        $res = $this->punch($token, $fob->uid, 'in');

        $res->assertOk()->assertJson(['ok' => true, 'direction' => 'in']);
        $this->assertDatabaseHas('clocking_punches', ['employee_id' => $emp->id, 'direction' => 'in']);
    }

    public function test_second_tap_clocks_out(): void
    {
        [$device, $token] = $this->makeDevice();
        $emp = $this->makeEmployee();
        $fob = $this->makeFob($emp);

        $this->punch($token, $fob->uid, 'in');
        $res = $this->punch($token, $fob->uid, 'out');

        $res->assertOk()->assertJson(['ok' => true, 'direction' => 'out']);
        $this->assertSame(2, ClockingPunch::where('employee_id', $emp->id)->count());
    }

    public function test_unknown_fob_returns_404(): void
    {
        [$device, $token] = $this->makeDevice();

        $this->punch($token, '9999999999', 'in')->assertStatus(404);
    }

    public function test_inactive_fob_returns_422(): void
    {
        [$device, $token] = $this->makeDevice();
        $emp = $this->makeEmployee();
        $fob = $this->makeFob($emp);
        $fob->update(['is_active' => false]);

        $this->punch($token, $fob->uid, 'in')->assertStatus(422);
    }

    public function test_unregistered_device_returns_403(): void
    {
        $emp = $this->makeEmployee();
        $fob = $this->makeFob($emp);

        $this->withHeader('X-Clocking-Device', 'not-a-real-token')
            ->postJson('/clocking/kiosk/punch', [
                'fob_uid'     => $fob->uid,
                'direction'   => 'in',
                'punched_at'  => now()->toISOString(),
                'client_uuid' => \Illuminate\Support\Str::uuid()->toString(),
            ])->assertStatus(403);
    }

    public function test_state_mismatch_flags_punch(): void
    {
        [$device, $token] = $this->makeDevice();
        $emp = $this->makeEmployee();
        $fob = $this->makeFob($emp);

        // Server state: currently 'out' (no punches)
        // Client sends 'out' — mismatch: server will flag it
        $res = $this->punch($token, $fob->uid, 'out');

        $res->assertOk();
        $this->assertDatabaseHas('clocking_punches', [
            'employee_id' => $emp->id,
            'direction'   => 'out',
            'is_flagged'  => true,
        ]);
    }
}
