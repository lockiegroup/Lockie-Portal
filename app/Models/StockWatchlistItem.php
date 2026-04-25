<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockWatchlistItem extends Model
{
    protected $fillable = [
        'category_id', 'product_code', 'product_name', 'info',
        'lead_time_months', 'unit_price', 'to_order_qty',
        'discontinued', 'position',
    ];

    protected $casts = [
        'lead_time_months' => 'integer',
        'unit_price'       => 'decimal:4',
        'to_order_qty'     => 'decimal:2',
        'discontinued'     => 'boolean',
        'position'         => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(StockWatchlistCategory::class, 'category_id');
    }
}
