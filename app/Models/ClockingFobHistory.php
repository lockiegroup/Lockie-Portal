<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockingFobHistory extends Model
{
    protected $table = 'clocking_fob_history';

    public $timestamps = false;

    protected $fillable = ['fob_id', 'employee_id', 'action', 'created_by', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function fob(): BelongsTo
    {
        return $this->belongsTo(ClockingFob::class, 'fob_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(ClockingEmployee::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
