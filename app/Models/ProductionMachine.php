<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionMachine extends Model
{
    protected $fillable = ['key', 'name', 'division', 'hue', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hue'       => 'integer',
        ];
    }
}
