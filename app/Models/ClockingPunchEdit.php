<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockingPunchEdit extends Model
{
    public $timestamps = false;

    protected $fillable = ['punch_id', 'edited_by', 'before_state', 'after_state', 'reason', 'created_at'];

    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state'  => 'array',
            'created_at'   => 'datetime',
        ];
    }

    public function punch(): BelongsTo
    {
        return $this->belongsTo(ClockingPunch::class, 'punch_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
