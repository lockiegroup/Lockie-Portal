<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJobRun extends Model
{
    protected $fillable = [
        'print_job_id',
        'user_id',
        'machine',
        'started_at',
        'ended_at',
        'end_reason',
        'packs_produced',
        'pause_reason',
    ];

    protected $casts = [
        'started_at'     => 'datetime',
        'ended_at'       => 'datetime',
        'packs_produced' => 'integer',
    ];

    public function printJob(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
