<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashFlowEntry extends Model
{
    protected $fillable = [
        'entry_date',
        'description',
        'category',
        'type',
        'amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount'     => 'decimal:2',
    ];
}
