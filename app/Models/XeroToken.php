<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XeroToken extends Model
{
    protected $fillable = [
        'tenant_id', 'tenant_name', 'access_token', 'refresh_token', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
