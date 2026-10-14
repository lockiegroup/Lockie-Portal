<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClockingSettingRecord extends Model
{
    protected $table    = 'clocking_settings';
    protected $fillable = ['key', 'value'];
}
