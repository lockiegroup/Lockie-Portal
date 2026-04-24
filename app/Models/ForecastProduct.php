<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForecastProduct extends Model
{
    protected $fillable = ['guid', 'product_code', 'product_name', 'supplier_name'];

    public function lines()
    {
        return $this->hasMany(ForecastLine::class, 'product_id');
    }
}
