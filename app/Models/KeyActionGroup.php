<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeyActionGroup extends Model
{
    protected $fillable = ['name', 'created_by', 'column_order'];

    protected function casts(): array
    {
        return ['column_order' => 'array'];
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'key_action_group_members', 'group_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(KeyActionTask::class, 'group_id');
    }

    public function buckets(): HasMany
    {
        return $this->hasMany(KeyActionBucket::class, 'group_id')->orderBy('sort_order')->orderBy('name');
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isAdmin(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->wherePivot('role', 'admin')->exists();
    }
}
