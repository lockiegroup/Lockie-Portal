<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeyActionTask extends Model
{
    protected $fillable = [
        'group_id', 'assigned_to', 'created_by', 'title', 'description',
        'label', 'due_date', 'completed', 'completed_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed'    => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(KeyActionGroup::class, 'group_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(KeyActionComment::class, 'task_id')->orderBy('created_at');
    }

    public function isOverdue(): bool
    {
        return !$this->completed && $this->due_date && $this->due_date->isPast();
    }
}
