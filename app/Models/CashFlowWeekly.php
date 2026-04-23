<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlowWeekly extends Model
{
    protected $table    = 'cash_flow_weekly';
    protected $fillable = ['week_start', 'category_id', 'amount', 'status'];

    protected $casts = [
        'week_start' => 'date',
        'amount'     => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CashFlowCategory::class, 'category_id');
    }
}
