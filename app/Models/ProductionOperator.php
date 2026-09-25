<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOperator extends Model
{
    protected $fillable = ['name', 'am_hours', 'pm_hours', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'am_hours'  => 'decimal:1',
            'pm_hours'  => 'decimal:1',
        ];
    }
}
