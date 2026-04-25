<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockWatchlistStock extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_code', 'product_name',
        'qty_on_hand', 'qty_allocated', 'qty_on_order',
        'po_expected_date', 'synced_at',
    ];

    protected $casts = [
        'qty_on_hand'     => 'decimal:4',
        'qty_allocated'   => 'decimal:4',
        'qty_on_order'    => 'decimal:4',
        'po_expected_date' => 'date',
        'synced_at'       => 'datetime',
    ];
}
