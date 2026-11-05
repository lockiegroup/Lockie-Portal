<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteLine extends Model
{
    protected $fillable = ['quote_id', 'description', 'unit', 'quantity', 'unit_price', 'line_total', 'sort_order'];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(QuoteLineBreak::class)->orderBy('min_qty');
    }
}
