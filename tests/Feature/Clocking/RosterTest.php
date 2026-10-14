<?php

namespace Tests\Feature\Clocking;

use App\Models\ClockingDevice;
use App\Models\ClockingEmployee;
use App\Models\ClockingFob;
use App\Models\ClockingPunch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_returns_all_active_fobs_with_state(): void
    {
        $device = ClockingDevice::create(['name' => 'K', 'is_active' => true]);
        $token  = $device->issueToken();

        $emp1 = ClockingEmployee::create(['name' => 'Alice', 'is_active' => true]);
        $emp2 = ClockingEmployee::create(['name' => 'Bob',   'is_active' => true]);
        $fob1 = ClockingFob::create(['employee_id' => $emp1->id, 'uid' => '1000000001', 'is_active' => true, 'assigned_at' => now()]);
        $fob2 = ClockingFob::create(['employee_id' => $emp2->id, 'uid' => '1000000002', 'is_active' => true, 'assigned_at' => now()]);

        // Clock emp1 in
        ClockingPunch::create(['employee_id' => $emp1->id, 'fob_id' => $fob1->id, 'direction' => 'in',
            'punched_at' => now()->subHour(), 'client_uuid' => Str::uuid(), 'source' => 'kiosk']);

        $res = $this->withHeader('X-Clocking-Device', $token)
            ->postJson('/clocking/kiosk/roster', []);

        $res->assertOk();
        $fobs = $res->json('fobs');

        $this->assertArrayHasKey('1000000001', $fobs);
        $this->assertArrayHasKey('1000000002', $fobs);
        $this->assertSame('in',  $fobs['1000000001']['state']);
        $this->assertSame('out', $fobs['1000000002']['state']); // default — no punches
    }

    public function test_inactive_employee_excluded_from_roster(): void
    {
        $device = ClockingDevice::create(['name' => 'K', 'is_active' => true]);
        $token  = $device->issueToken();

        $emp = ClockingEmployee::create(['name' => 'Ghost', 'is_active' => false]);
        ClockingFob::create(['employee_id' => $emp->id, 'uid' => '9000000001', 'is_active' => true, 'assigned_at' => now()]);

        $res = $this->withHeader('X-Clocking-Device', $token)->postJson('/clocking/kiosk/roster', []);
        $this->assertArrayNotHasKey('9000000001', $res->json('fobs'));
    }

    public function test_voided_punch_not_counted_toward_state(): void
    {
        $device = ClockingDevice::create(['name' => 'K', 'is_active' => true]);
        $token  = $device->issueToken();

        $emp = ClockingEmployee::create(['name' => 'Cas', 'is_active' => true]);
        $fob = ClockingFob::create(['employee_id' => $emp->id, 'uid' => '8000000001', 'is_active' => true, 'assigned_at' => now()]);

        // Clock in, then void it
        ClockingPunch::create([
            'employee_id' => $emp->id, 'fob_id' => $fob->id,
            'direction'   => 'in',     'punched_at' => now()->subHour(),
            'client_uuid' => Str::uuid(), 'source' => 'kiosk',
            'voided_at'   => now(),
        ]);

        $res  = $this->withHeader('X-Clocking-Device', $token)->postJson('/clocking/kiosk/roster', []);
        $fobs = $res->json('fobs');

        // Voided punch should not count — state reverts to 'out'
        $this->assertSame('out', $fobs['8000000001']['state']);
    }
}
