<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollarStockAdjustment extends Model
{
    public $timestamps = false;

    protected $fillable = ['collar_product_id', 'type', 'qty', 'note', 'created_by', 'created_at'];

    protected $casts = [
        'qty'        => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(CollarProduct::class, 'collar_product_id');
    }
}
