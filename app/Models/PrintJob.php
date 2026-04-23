<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintJob extends Model
{
    public const BOARDS = [
        'unplanned'          => 'Unplanned',
        'call_off'           => 'Call Off',
        'on_hold'            => 'On Hold',
        'auto_1'             => 'Auto 1',
        'auto_2'             => 'Auto 2',
        'auto_3'             => 'Auto 3',
        'baby'               => 'Baby',
        'awaiting_despatch'  => 'Awaiting Despatch',
    ];

    public const MACHINES = ['auto_1', 'auto_2', 'auto_3', 'baby'];

    protected $fillable = [
        'unleashed_guid',
        'line_number',
        'order_number',
        'order_date',
        'customer_name',
        'customer_ref',
        'product_code',
        'product_description',
        'line_comment',
        'order_total',
        'line_total',
        'order_quantity',
        'quantity_completed',
        'material_checked',
        'required_date',
        'original_required_date',
        'board',
        'position',
        'unleashed_status',
        'synced_at',
        'archived_at',
        'archive_reason',
        'despatched_at',
    ];

    protected $casts = [
        'required_date'          => 'date',
        'original_required_date' => 'date',
        'order_date'             => 'date',
        'synced_at'              => 'datetime',
        'order_total'            => 'decimal:2',
        'line_total'             => 'decimal:2',
        'order_quantity'         => 'integer',
        'quantity_completed'     => 'integer',
        'material_checked'       => 'boolean',
        'line_number'            => 'integer',
        'position'               => 'integer',
        'archived_at'            => 'datetime',
        'despatched_at'          => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(PrintJobNote::class);
    }

    public function dateChanges(): HasMany
    {
        return $this->hasMany(PrintJobDateChange::class);
    }

    public function getDateChangedAttribute(): bool
    {
        if ($this->required_date === null && $this->original_required_date === null) {
            return false;
        }

        if ($this->required_date === null || $this->original_required_date === null) {
            return true;
        }

        return $this->required_date->format('Y-m-d') !== $this->original_required_date->format('Y-m-d');
    }

    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->order_quantity - $this->quantity_completed);
    }
}
