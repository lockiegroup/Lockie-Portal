<?php

namespace Tests\Feature\Clocking;

use App\Models\ClockingDevice;
use App\Models\ClockingEmployee;
use App\Models\ClockingFob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PunchDebounceTest extends TestCase
{
    use RefreshDatabase;

    private function makeScenario(): array
    {
        $device = ClockingDevice::create(['name' => 'Kiosk', 'is_active' => true]);
        $token  = $device->issueToken();
        $emp    = ClockingEmployee::create(['name' => 'Bob', 'is_active' => true]);
        $fob    = ClockingFob::create(['employee_id' => $emp->id, 'uid' => '1111111111', 'is_active' => true, 'assigned_at' => now()]);
        return [$device, $token, $emp, $fob];
    }

    public function test_same_direction_within_60s_is_rejected(): void
    {
        [, $token, , $fob] = $this->makeScenario();
        $at = now()->toISOString();

        $this->withHeader('X-Clocking-Device', $token)
            ->postJson('/clocking/kiosk/punch', [
                'fob_uid' => $fob->uid, 'direction' => 'in',
                'punched_at' => $at, 'client_uuid' => \Illuminate\Support\Str::uuid(),
            ])->assertOk();

        // Second tap same direction 5 seconds later — within debounce window
        $this->withHeader('X-Clocking-Device', $token)
            ->postJson('/clocking/kiosk/punch', [
                'fob_uid' => $fob->uid, 'direction' => 'in',
                'punched_at' => now()->addSeconds(5)->toISOString(),
                'client_uuid' => \Illuminate\Support\Str::uuid(),
            ])->assertStatus(409)->assertJsonFragment(['error' => 'debounce']);
    }

    public function test_same_direction_after_60s_is_allowed(): void
    {
        [, $token, , $fob] = $this->makeScenario();

        $this->withHeader('X-Clocking-Device', $token)
            ->postJson('/clocking/kiosk/punch', [
                'fob_uid' => $fob->uid, 'direction' => 'in',
                'punched_at' => now()->subSeconds(61)->toISOString(),
                'client_uuid' => \Illuminate\Support\Str::uuid(),
            ])->assertOk();

        $this->withHeader('X-Clocking-Device', $token)
            ->postJson('/clocking/kiosk/punch', [
                'fob_uid' => $fob->uid, 'direction' => 'in',
                'punched_at' => now()->toISOString(),
                'client_uuid' => \Illuminate\Support\Str::uuid(),
            ])->assertOk();
    }

    public function test_opposite_direction_is_never_debounced(): void
    {
        [, $token, , $fob] = $this->makeScenario();
        $at = now()->toISOString();

        $this->withHeader('X-Clocking-Device', $token)
            ->postJson('/clocking/kiosk/punch', [
                'fob_uid' => $fob->uid, 'direction' => 'in',
                'punched_at' => $at, 'client_uuid' => \Illuminate\Support\Str::uuid(),
            ])->assertOk();

        // Opposite direction immediately — no debounce
        $this->withHeader('X-Clocking-Device', $token)
            ->postJson('/clocking/kiosk/punch', [
                'fob_uid' => $fob->uid, 'direction' => 'out',
                'punched_at' => now()->addSeconds(1)->toISOString(),
                'client_uuid' => \Illuminate\Support\Str::uuid(),
            ])->assertOk();
    }
}
