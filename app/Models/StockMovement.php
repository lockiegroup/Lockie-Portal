<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'moved_at', 'description', 'colour', 'quantity',
        'from_location', 'to_location', 'notes',
    ];

    protected $casts = [
        'moved_at' => 'date',
    ];
}
