<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmazonSettlementLine extends Model
{
    protected $fillable = [
        'settlement_id', 'transaction_type', 'order_id', 'sku', 'product_type',
        'fulfillment_channel', 'amount_gross', 'amount_net', 'vat_amount',
        'vat_rate', 'account_code',
    ];

    protected function casts(): array
    {
        return [
            'amount_gross' => 'decimal:4',
            'amount_net'   => 'decimal:4',
            'vat_amount'   => 'decimal:4',
            'vat_rate'     => 'decimal:2',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(AmazonSettlement::class, 'settlement_id');
    }
}
