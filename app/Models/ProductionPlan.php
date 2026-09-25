<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    protected $fillable = ['week_key', 'data'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
