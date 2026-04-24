<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierSetting extends Model
{
    protected $fillable = ['supplier_name', 'lead_time_weeks'];
}
