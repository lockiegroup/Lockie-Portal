<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AmazonSettlement extends Model
{
    protected $fillable = [
        'settlement_id', 'start_date', 'end_date', 'deposit_amount',
        'currency', 'status', 'raw_data', 'processed_at', 'xero_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date'   => 'date',
            'end_date'     => 'date',
            'processed_at' => 'datetime',
            'raw_data'     => 'array',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AmazonSettlementLine::class, 'settlement_id');
    }
}
