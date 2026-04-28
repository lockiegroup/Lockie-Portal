<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeyAccount extends Model
{
    protected $fillable = ['account_code', 'name', 'type', 'user_id', 'notes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(KeyAccountContact::class)->orderByDesc('contacted_at')->orderByDesc('id');
    }

    public function gifts(): HasMany
    {
        return $this->hasMany(KeyAccountGift::class)->orderByDesc('gifted_at')->orderByDesc('id');
    }

    public function getLastContactDateAttribute(): ?string
    {
        return $this->contacts()->value('contacted_at');
    }

    public function getLastGiftDateAttribute(): ?string
    {
        return $this->gifts()->value('gifted_at');
    }
}
