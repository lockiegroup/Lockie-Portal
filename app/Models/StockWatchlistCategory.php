<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockWatchlistCategory extends Model
{
    protected $fillable = ['name', 'position', 'lead_time_months'];

    protected $casts = ['lead_time_months' => 'integer'];

    public function items(): HasMany
    {
        return $this->hasMany(StockWatchlistItem::class, 'category_id')->orderBy('position');
    }
}
