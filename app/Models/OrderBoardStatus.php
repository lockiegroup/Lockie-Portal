<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderBoardStatus extends Model
{
    protected $fillable = ['key', 'label', 'color', 'sort_order'];
}
