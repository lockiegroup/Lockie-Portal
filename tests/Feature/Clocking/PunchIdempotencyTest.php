<?php

namespace Tests\Feature\Clocking;

use App\Models\ClockingDevice;
use App\Models\ClockingEmployee;
use App\Models\ClockingFob;
use App\Models\ClockingPunch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PunchIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_client_uuid_returns_duplicate_without_creating_new_punch(): void
    {
        $device = ClockingDevice::create(['name' => 'K', 'is_active' => true]);
        $token  = $device->issueToken();
        $emp    = ClockingEmployee::create(['name' => 'Ali', 'is_active' => true]);
        $fob    = ClockingFob::create(['employee_id' => $emp->id, 'uid' => '2222222222', 'is_active' => true, 'assigned_at' => now()]);

        $uuid    = Str::uuid()->toString();
        $payload = [
            'fob_uid' => $fob->uid, 'direction' => 'in',
            'punched_at' => now()->toISOString(), 'client_uuid' => $uuid,
        ];

        $this->withHeader('X-Clocking-Device', $token)->postJson('/clocking/kiosk/punch', $payload)->assertOk();
        $this->withHeader('X-Clocking-Device', $token)->postJson('/clocking/kiosk/punch', $payload)
            ->assertOk()
            ->assertJsonFragment(['duplicate' => true]);

        // Only one punch created
        $this->assertSame(1, ClockingPunch::where('client_uuid', $uuid)->count());
    }

    public function test_out_of_order_offline_punches_all_record_but_flag_mismatches(): void
    {
        $device = ClockingDevice::create(['name' => 'K', 'is_active' => true]);
        $token  = $device->issueToken();
        $emp    = ClockingEmployee::create(['name' => 'Sam', 'is_active' => true]);
        $fob    = ClockingFob::create(['employee_id' => $emp->id, 'uid' => '3333333333', 'is_active' => true, 'assigned_at' => now()]);

        $base = now()->subHour();

        // Simulate: kiosk was offline, queued IN then OUT, server receives them in order
        $inUuid  = Str::uuid()->toString();
        $outUuid = Str::uuid()->toString();

        $this->withHeader('X-Clocking-Device', $token)->postJson('/clocking/kiosk/punch', [
            'fob_uid' => $fob->uid, 'direction' => 'in',
            'punched_at' => $base->toISOString(), 'client_uuid' => $inUuid,
        ])->assertOk()->assertJsonFragment(['flagged' => false]);

        $this->withHeader('X-Clocking-Device', $token)->postJson('/clocking/kiosk/punch', [
            'fob_uid' => $fob->uid, 'direction' => 'out',
            'punched_at' => $base->addMinutes(30)->toISOString(), 'client_uuid' => $outUuid,
        ])->assertOk()->assertJsonFragment(['flagged' => false]);

        $this->assertSame(2, ClockingPunch::where('employee_id', $emp->id)->count());
    }

    public function test_late_arriving_punch_with_state_mismatch_is_flagged(): void
    {
        $device = ClockingDevice::create(['name' => 'K', 'is_active' => true]);
        $token  = $device->issueToken();
        $emp    = ClockingEmployee::create(['name' => 'Pat', 'is_active' => true]);
        $fob    = ClockingFob::create(['employee_id' => $emp->id, 'uid' => '4444444444', 'is_active' => true, 'assigned_at' => now()]);

        // Someone is clocked in — use explicit UTC to avoid BST storage/comparison mismatch
        ClockingPunch::create([
            'employee_id' => $emp->id, 'fob_id' => $fob->id,
            'direction' => 'in', 'punched_at' => now()->utc()->subHours(2),
            'client_uuid' => Str::uuid(), 'source' => 'kiosk',
        ]);

        // A late-arriving offline punch also says 'in' — mismatch
        $this->withHeader('X-Clocking-Device', $token)->postJson('/clocking/kiosk/punch', [
            'fob_uid' => $fob->uid, 'direction' => 'in',
            'punched_at' => now()->subHour()->toISOString(),
            'client_uuid' => Str::uuid(),
        ])->assertOk()->assertJsonFragment(['flagged' => true]);
    }
}
