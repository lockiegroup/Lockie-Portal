<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockWatchlistSale extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_code', 'year', 'month', 'qty_sold'];

    protected $casts = [
        'year'     => 'integer',
        'month'    => 'integer',
        'qty_sold' => 'decimal:4',
    ];
}
