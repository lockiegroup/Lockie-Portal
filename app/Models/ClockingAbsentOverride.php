<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockingAbsentOverride extends Model
{
    public $timestamps = false;
    protected $table   = 'clocking_absent_overrides';
    protected $fillable = ['employee_id', 'date', 'created_by', 'created_at'];

    protected function casts(): array
    {
        return ['date' => 'date', 'created_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(ClockingEmployee::class, 'employee_id');
    }
}
