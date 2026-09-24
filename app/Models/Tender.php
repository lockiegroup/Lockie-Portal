<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tender extends Model
{
    public const RELEVANCE_HIGH     = 'high';
    public const RELEVANCE_RELEVANT = 'relevant';
    public const RELEVANCE_POSSIBLE = 'possible';
    public const RELEVANCE_NOT      = 'not_relevant';

    public const STATUS_NEW         = 'new';
    public const STATUS_REVIEWED    = 'reviewed';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_APPLIED     = 'applied';
    public const STATUS_WON         = 'won';
    public const STATUS_LOST        = 'lost';
    public const STATUS_IGNORED     = 'ignored';

    public const RELEVANCE_LABELS = [
        'high'         => '🔥 High relevance',
        'relevant'     => '🟢 Relevant',
        'possible'     => '🟡 Possible',
        'not_relevant' => '🔴 Not relevant',
    ];

    public const RELEVANCE_COLOURS = [
        'high'         => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca'],
        'relevant'     => ['bg' => '#f0fdf4', 'text' => '#15803d', 'border' => '#bbf7d0'],
        'possible'     => ['bg' => '#fefce8', 'text' => '#a16207', 'border' => '#fde68a'],
        'not_relevant' => ['bg' => '#f8fafc', 'text' => '#64748b', 'border' => '#e2e8f0'],
    ];

    public const SOURCE_LABELS = [
        'contracts_finder' => 'Contracts Finder',
        'find_a_tender'    => 'Find a Tender',
    ];

    protected $fillable = [
        'source', 'source_ref', 'title', 'description',
        'buyer_name', 'buyer_location',
        'value_low', 'value_high',
        'published_at', 'deadline_at',
        'contract_start', 'contract_end',
        'status', 'ai_relevance', 'ai_score', 'ai_reasoning', 'ai_products',
        'source_url', 'cpv_codes', 'incumbent_supplier', 'notes', 'alerted_at',
    ];

    protected $casts = [
        'published_at'   => 'date',
        'deadline_at'    => 'datetime',
        'contract_start' => 'date',
        'contract_end'   => 'date',
        'alerted_at'     => 'datetime',
        'ai_products'    => 'array',
        'cpv_codes'      => 'array',
        'value_low'      => 'integer',
        'value_high'     => 'integer',
        'ai_score'       => 'integer',
    ];

    public function historicalContracts(): HasMany
    {
        return $this->hasMany(HistoricalContract::class, 'related_tender_id');
    }

    public function getValueDisplayAttribute(): string
    {
        if ($this->value_low && $this->value_high && $this->value_low !== $this->value_high) {
            return '£' . $this->formatValue($this->value_low) . ' – £' . $this->formatValue($this->value_high);
        }
        if ($this->value_high) {
            return '£' . $this->formatValue($this->value_high);
        }
        if ($this->value_low) {
            return '£' . $this->formatValue($this->value_low);
        }
        return 'Value not specified';
    }

    public function getIsClosingSoonAttribute(): bool
    {
        return $this->deadline_at && $this->deadline_at->isFuture() && $this->deadline_at->diffInDays(now()) <= 14;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->deadline_at && $this->deadline_at->isPast();
    }

    private function formatValue(int $val): string
    {
        if ($val >= 1_000_000) return number_format($val / 1_000_000, 1) . 'm';
        if ($val >= 1_000)     return number_format($val / 1_000, 0) . 'k';
        return number_format($val);
    }
}
