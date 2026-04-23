<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockSnapshot extends Model
{
    protected $fillable = ['snapshot_date', 'total_value', 'warehouse_data'];

    protected function casts(): array
    {
        return [
            'snapshot_date'  => 'date',
            'total_value'    => 'float',
            'warehouse_data' => 'array',
        ];
    }
}
