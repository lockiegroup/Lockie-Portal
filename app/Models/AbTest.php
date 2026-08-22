<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbTest extends Model
{
    protected $fillable = [
        'marketing_division_id', 'campaign_name', 'sent_at',
        'test_type', 'variant_a', 'variant_a_result',
        'variant_b', 'variant_b_result', 'winner', 'notes', 'user_id',
    ];

    protected $casts = ['sent_at' => 'date'];

    public const TEST_TYPES = [
        'subject_line' => 'Subject Line',
        'send_time'    => 'Send Time',
        'content'      => 'Content',
        'sender_name'  => 'Sender Name',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(MarketingDivision::class, 'marketing_division_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTestTypeLabelAttribute(): string
    {
        return self::TEST_TYPES[$this->test_type] ?? $this->test_type;
    }
}
