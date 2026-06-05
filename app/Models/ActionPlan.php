<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActionPlan extends Model
{
    protected $fillable = ['name', 'description', 'created_by', 'is_archived', 'start_date', 'end_date', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
            'start_date'  => 'date',
            'end_date'    => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ActionPlanMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'action_plan_members')->withTimestamps();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ActionPlanItem::class)->orderBy('week_commencing')->orderBy('sort_order')->orderBy('id');
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }
}
