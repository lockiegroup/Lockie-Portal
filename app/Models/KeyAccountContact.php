<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyAccountContact extends Model
{
    protected $fillable = ['key_account_id', 'user_id', 'contacted_at', 'note'];

    protected $casts = ['contacted_at' => 'date'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(KeyAccount::class, 'key_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
