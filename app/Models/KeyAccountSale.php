<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeyAccountSale extends Model
{
    protected $fillable = ['account_code', 'year', 'total', 'q1', 'q2', 'q3', 'q4', 'imported_at', 'user_id'];

    protected $casts = ['imported_at' => 'datetime'];
}
