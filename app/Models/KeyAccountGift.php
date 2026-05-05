<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyAccountGift extends Model
{
    protected $fillable = ['key_account_id', 'recipient', 'gifted_at', 'description'];

    protected $casts = ['gifted_at' => 'date'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(KeyAccount::class, 'key_account_id');
    }
}
