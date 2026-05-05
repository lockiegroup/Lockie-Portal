<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesLine extends Model
{
    protected $fillable = [
        'order_no', 'order_date', 'required_date', 'completed_date',
        'warehouse', 'customer_code', 'customer', 'customer_type',
        'product_code', 'product_group', 'status', 'quantity', 'sub_total',
    ];

    protected $casts = [
        'order_date'     => 'date',
        'required_date'  => 'date',
        'completed_date' => 'date',
        'quantity'       => 'decimal:4',
        'sub_total'      => 'decimal:2',
    ];
}
