<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClockingFob extends Model
{
    protected $fillable = ['employee_id', 'uid', 'is_active', 'assigned_at', 'notes'];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(ClockingEmployee::class, 'employee_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ClockingFobHistory::class, 'fob_id');
    }
}
