<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingRule extends Model
{
    protected $fillable = ['marketing_division_id', 'body', 'sort_order', 'user_id'];

    public function division(): BelongsTo
    {
        return $this->belongsTo(MarketingDivision::class, 'marketing_division_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
