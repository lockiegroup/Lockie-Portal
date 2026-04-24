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
        'last_reviewed_at',
    ];

    protected $casts = [
        'last_reviewed_at' => 'date',
    ];
}
