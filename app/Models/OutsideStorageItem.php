<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutsideStorageItem extends Model
{
    protected $fillable = [
        'storage_date', 'colour', 'quantity', 'ref', 'year', 'return_date', 'notes',
    ];

    protected $casts = [
        'storage_date' => 'date',
        'return_date'  => 'date',
    ];
}
