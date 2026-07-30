<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJobRunProgress extends Model
{
    protected $table = 'print_job_run_progress';

    protected $fillable = ['print_job_run_id', 'packs_cumulative', 'logged_at'];

    protected $casts = ['logged_at' => 'datetime'];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PrintJobRun::class, 'print_job_run_id');
    }
}
