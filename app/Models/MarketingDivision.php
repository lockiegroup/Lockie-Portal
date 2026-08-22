<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingDivision extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public function tests(): HasMany
    {
        return $this->hasMany(AbTest::class)->orderByDesc('sent_at');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(MarketingRule::class)->orderBy('sort_order');
    }
}
