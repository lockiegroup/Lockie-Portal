<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyPolicy extends Model
{
    protected $fillable = [
        'title',
        'category',
        'description',
        'file_name',
        'file_path',
        'sort_order',
    ];
}
