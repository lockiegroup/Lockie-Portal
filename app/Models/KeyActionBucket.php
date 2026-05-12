<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeyActionBucket extends Model
{
    protected $fillable = ['group_id', 'name', 'sort_order'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(KeyActionGroup::class, 'group_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(KeyActionTask::class, 'bucket_id');
    }
}
