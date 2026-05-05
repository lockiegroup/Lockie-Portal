<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonProfitSnapshot extends Model
{
    protected $fillable = [
        'period_start', 'period_end', 'fulfillment_channel', 'product_type', 'sku',
        'gross_sales', 'returns', 'referral_fees_net', 'fba_fees_net',
        'ad_spend_net', 'cogs', 'gross_profit', 'gross_margin_pct',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end'   => 'date',
        ];
    }
}
