<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClockingPunch extends Model
{
    protected $fillable = [
        'employee_id', 'fob_id', 'direction', 'punched_at',
        'device_id', 'client_uuid', 'source',
        'is_flagged', 'flag_reason',
        'voided_at', 'voided_by', 'voided_reason',
    ];

    protected function casts(): array
    {
        return [
            'punched_at' => 'datetime',
            'voided_at'  => 'datetime',
            'is_flagged' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(ClockingEmployee::class, 'employee_id');
    }

    public function fob(): BelongsTo
    {
        return $this->belongsTo(ClockingFob::class, 'fob_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(ClockingDevice::class, 'device_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function edits(): HasMany
    {
        return $this->hasMany(ClockingPunchEdit::class, 'punch_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('voided_at');
    }
}
