<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLineBreak extends Model
{
    protected $fillable = ['quote_line_id', 'min_qty', 'unit_price'];

    protected $casts = [
        'min_qty'    => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function quoteLine(): BelongsTo
    {
        return $this->belongsTo(QuoteLine::class);
    }
}
