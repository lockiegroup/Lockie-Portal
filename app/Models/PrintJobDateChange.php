<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJobDateChange extends Model
{
    protected $fillable = [
        'print_job_id',
        'user_id',
        'old_date',
        'new_date',
    ];

    protected $casts = [
        'old_date' => 'date',
        'new_date' => 'date',
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
