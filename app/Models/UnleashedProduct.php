<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnleashedProduct extends Model
{
    protected $table      = 'unleashed_products';
    protected $primaryKey = 'product_code';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = ['product_code', 'product_name', 'synced_at'];

    protected $casts = ['synced_at' => 'datetime'];
}
