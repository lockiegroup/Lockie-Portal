<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderPhone extends Model
{
    protected $fillable = ['account_code', 'phone'];
}
