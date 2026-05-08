<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollarProduct extends Model
{
    protected $fillable = [
        'product_code', 'description', 'reel_width', 'is_stock_line',
        'cut_blank_stock', 'cut_blank_moq', 'cut_blank_reorder_level',
        'made_moq', 'made_reorder_level', 'position',
    ];

    protected $casts = [
        'is_stock_line'           => 'boolean',
        'cut_blank_stock'         => 'decimal:2',
        'cut_blank_moq'           => 'integer',
        'cut_blank_reorder_level' => 'integer',
        'made_moq'                => 'integer',
        'made_reorder_level'      => 'integer',
        'position'                => 'integer',
    ];

    public function adjustments(): HasMany
    {
        return $this->hasMany(CollarStockAdjustment::class);
    }

    public function worksOrderLines(): HasMany
    {
        return $this->hasMany(CollarWorksOrderLine::class);
    }

    public function cutBlankStatus(int $stock): string
    {
        $reorder = $this->cut_blank_reorder_level;
        if ($reorder === null) return 'unknown';
        if ($stock <= 0) return 'out';
        if ($stock < $reorder) return 'required';
        if ($stock > $reorder * 2) return 'overstocked';
        return 'good';
    }

    public function madeStatus(int $stock): string
    {
        $reorder = $this->made_reorder_level;
        if ($reorder === null) return 'unknown';
        if ($stock <= 0) return 'out';
        if ($stock < $reorder) return 'required';
        if ($stock > $reorder * 2) return 'overstocked';
        return 'good';
    }
}
