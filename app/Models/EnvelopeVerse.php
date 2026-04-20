<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvelopeVerse extends Model
{
    protected $fillable = ['label', 'lines', 'sort_order'];

    protected function casts(): array
    {
        return [
            'lines' => 'array',
        ];
    }
}
