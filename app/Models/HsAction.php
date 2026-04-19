<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HsAction extends Model
{
    protected $fillable = [
        'title', 'description', 'location', 'assigned_to', 'raised_by',
        'priority', 'status', 'due_date', 'completed_at',
        'is_recurring', 'recurrence_type', 'parent_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed_at' => 'datetime',
            'is_recurring' => 'boolean',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function raisedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(HsAction::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(HsAction::class, 'parent_id');
    }

    public function nextDueDate(): Carbon
    {
        return match ($this->recurrence_type) {
            'daily'     => $this->due_date->copy()->addDay(),
            'weekly'    => $this->due_date->copy()->addWeek(),
            'monthly'   => $this->due_date->copy()->addMonth(),
            'quarterly' => $this->due_date->copy()->addMonths(3),
            'annually'  => $this->due_date->copy()->addYear(),
            default     => $this->due_date->copy()->addMonth(),
        };
    }
}
