<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForecastLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'warehouse_code', 'warehouse_name',
        'qty_on_hand', 'qty_incoming', 'po_expected_date',
        'qty_sold_90d', 'lead_time_override', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'po_expected_date' => 'date',
            'last_synced_at'   => 'datetime',
        ];
    }

    public function product()
    {
        return $this->belongsTo(ForecastProduct::class, 'product_id');
    }
}
