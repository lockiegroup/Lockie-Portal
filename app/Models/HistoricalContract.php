<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalContract extends Model
{
    public const STATUS_MONITORING        = 'monitoring';
    public const STATUS_REPLACEMENT_FOUND = 'replacement_found';
    public const STATUS_CLOSED            = 'closed';

    protected $fillable = [
        'buyer', 'product_category', 'description',
        'incumbent_supplier', 'value_estimate',
        'contract_start', 'contract_end', 'extension_options',
        'status', 'notes', 'related_tender_id',
    ];

    protected $casts = [
        'contract_start' => 'date',
        'contract_end'   => 'date',
        'value_estimate' => 'integer',
    ];

    public function relatedTender(): BelongsTo
    {
        return $this->belongsTo(Tender::class, 'related_tender_id');
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->contract_end) return null;
        return (int) now()->diffInDays($this->contract_end, false);
    }

    public function getExpiryAlertAttribute(): ?string
    {
        $days = $this->days_until_expiry;
        if ($days === null) return null;
        if ($days < 0)    return 'expired';
        if ($days <= 30)  return 'critical';
        if ($days <= 90)  return 'warning';
        if ($days <= 180) return 'watch';
        return null;
    }

    public function getValueDisplayAttribute(): string
    {
        if (! $this->value_estimate) return 'Unknown';
        $v = $this->value_estimate;
        if ($v >= 1_000_000) return '£' . number_format($v / 1_000_000, 1) . 'm';
        if ($v >= 1_000)     return '£' . number_format($v / 1_000, 0) . 'k';
        return '£' . number_format($v);
    }
}
