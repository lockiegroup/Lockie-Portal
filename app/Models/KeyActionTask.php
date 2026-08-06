<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeyActionTask extends Model
{
    protected $fillable = [
        'group_id', 'assigned_to', 'bucket_id', 'created_by', 'title', 'description',
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

    public function bucket(): BelongsTo
    {
        return $this->belongsTo(KeyActionBucket::class, 'bucket_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'key_action_task_members', 'task_id', 'user_id');
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
