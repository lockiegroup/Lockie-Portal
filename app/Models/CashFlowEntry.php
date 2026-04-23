<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlowEntry extends Model
{
    protected $fillable = [
        'category_id',
        'entry_date',
        'description',
        'type',
        'amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount'     => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CashFlowCategory::class, 'category_id');
    }
}
