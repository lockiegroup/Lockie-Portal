<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionPlanItem extends Model
{
    protected $fillable = [
        'action_plan_id', 'brand', 'type', 'title',
        'assigned_user_id', 'week_commencing', 'status', 'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['week_commencing' => 'date'];
    }

    const STATUSES = [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
        'booked_in'   => 'Booked In',
    ];

    const STATUS_STYLES = [
        'not_started' => ['bg' => '#f1f5f9', 'color' => '#64748b'],
        'in_progress' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
        'completed'   => ['bg' => '#dcfce7', 'color' => '#166534'],
        'cancelled'   => ['bg' => '#fee2e2', 'color' => '#991b1b'],
        'booked_in'   => ['bg' => '#ede9fe', 'color' => '#6d28d9'],
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ActionPlan::class, 'action_plan_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
