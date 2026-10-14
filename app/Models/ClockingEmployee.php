<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClockingEmployee extends Model
{
    protected $fillable = ['name', 'is_active', 'schedule', 'break_override_minutes'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'schedule'  => 'array',
        ];
    }

    public function fobs(): HasMany
    {
        return $this->hasMany(ClockingFob::class, 'employee_id');
    }

    public function activeFob(): ?ClockingFob
    {
        return $this->fobs()->where('is_active', true)->latest('assigned_at')->first();
    }

    public function punches(): HasMany
    {
        return $this->hasMany(ClockingPunch::class, 'employee_id');
    }

    public function absentOverrides(): HasMany
    {
        return $this->hasMany(ClockingAbsentOverride::class, 'employee_id');
    }

    /** Direction of the most recent non-voided punch, or null if none. */
    public function currentState(): ?string
    {
        $last = $this->punches()
            ->whereNull('voided_at')
            ->latest('punched_at')
            ->value('direction');

        return $last; // 'in' | 'out' | null
    }

    /** Days this employee is scheduled to work, keyed by lowercase 3-letter day (mon, tue…). */
    public function scheduledDays(): array
    {
        return array_keys(array_filter($this->schedule ?? [], fn($v) => !empty($v['start'])));
    }

    /** Whether this employee is scheduled on a given Carbon date. */
    public function isScheduledOn(\Carbon\Carbon $date): bool
    {
        $day = strtolower($date->format('D')); // mon, tue...
        $s   = $this->schedule ?? [];
        return isset($s[$day]['start']) && $s[$day]['start'] !== '';
    }
}
