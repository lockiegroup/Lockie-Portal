<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashFlowCategory extends Model
{
    protected $fillable = ['name', 'type', 'sort_order'];

    public function weeklyEntries(): HasMany
    {
        return $this->hasMany(CashFlowWeekly::class, 'category_id');
    }
}
