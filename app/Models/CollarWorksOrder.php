<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollarWorksOrder extends Model
{
    protected $fillable = ['title', 'period', 'notes', 'created_by'];

    protected $casts = ['period' => 'date'];

    public function lines(): HasMany
    {
        return $this->hasMany(CollarWorksOrderLine::class, 'works_order_id');
    }
}
