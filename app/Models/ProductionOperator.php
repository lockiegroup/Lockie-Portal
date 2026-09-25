<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOperator extends Model
{
    private const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri'];

    protected $fillable = ['name', 'schedule', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'schedule'  => 'array',
        ];
    }

    public function defaultSchedule(): array
    {
        return array_fill_keys(self::DAYS, ['am' => 4.0, 'pm' => 4.0]);
    }

    public function getScheduledHours(string $day, string $shift): float
    {
        return (float) (($this->schedule[$day][$shift]) ?? 4.0);
    }
}
