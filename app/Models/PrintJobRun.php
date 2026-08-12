<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'packs_corrected_from',
        'pause_reason',
        'pause_type',
        'progress_packs',
        'progress_at',
        'fully_complete',
    ];

    protected $casts = [
        'started_at'           => 'datetime',
        'ended_at'             => 'datetime',
        'progress_at'          => 'datetime',
        'packs_produced'       => 'integer',
        'packs_corrected_from' => 'integer',
        'progress_packs'       => 'integer',
        'fully_complete'       => 'boolean',
    ];

    public function printJob(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(PrintJobRunProgress::class)->orderBy('logged_at');
    }
}
