<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClockingAlertLog extends Model
{
    public $timestamps = false;
    protected $table   = 'clocking_alert_log';

    protected $fillable = ['employee_id', 'alert_date', 'sent_at', 'recipients'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'recipients' => 'array'];
    }
}
