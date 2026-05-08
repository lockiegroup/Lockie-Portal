<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollarWorksOrderLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['works_order_id', 'collar_product_id', 'type', 'qty', 'note'];

    protected $casts = ['qty' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(CollarProduct::class, 'collar_product_id');
    }

    public function worksOrder(): BelongsTo
    {
        return $this->belongsTo(CollarWorksOrder::class, 'works_order_id');
    }
}
