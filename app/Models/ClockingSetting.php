<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClockingSetting extends Model
{
    protected $fillable = ['key', 'value'];

    // Default values for all settings
    const DEFAULTS = [
        'break_enabled'          => '0',     // '1' = on
        'break_shift_minutes'    => '360',   // shifts >= this many minutes get deducted
        'break_deduct_minutes'   => '30',
        'missed_clockin_grace'   => '30',    // minutes after scheduled start
        'open_shift_threshold'   => '240',   // minutes after scheduled end to flag open shift
        'alert_emails'           => '',      // comma-separated
        'csv_emails'             => '',
    ];

    public static function get(string $key): ?string
    {
        $row = static::where('key', $key)->value('value');
        return $row ?? (static::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
