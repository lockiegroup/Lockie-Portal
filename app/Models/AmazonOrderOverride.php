<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonOrderOverride extends Model
{
    protected $table = 'amazon_order_overrides';

    protected $fillable = ['settlement_id', 'amazon_order_id', 'amount_override', 'notes'];

    protected $casts = ['amount_override' => 'decimal:2'];
}
