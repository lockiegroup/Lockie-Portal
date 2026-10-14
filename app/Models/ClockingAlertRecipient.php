<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockingAlertRecipient extends Model
{
    protected $fillable = ['category_id', 'email', 'label'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ClockingCategory::class, 'category_id');
    }
}
