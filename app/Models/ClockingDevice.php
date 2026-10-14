<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClockingDevice extends Model
{
    protected $fillable = [
        'name', 'token_hash', 'registration_code',
        'registration_code_expires_at', 'last_seen_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'                     => 'boolean',
            'registration_code_expires_at'  => 'datetime',
            'last_seen_at'                  => 'datetime',
        ];
    }

    /** Generate a new registration code and return the plain-text code. */
    public function generateRegistrationCode(): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'registration_code'            => $code,
            'registration_code_expires_at' => now()->addMinutes(10),
        ]);
        return $code;
    }

    /** Generate a new long-lived token, hash and store it. Returns the plain token. */
    public function issueToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'token_hash'       => hash('sha256', $token),
            'registration_code'            => null,
            'registration_code_expires_at' => null,
        ]);
        return $token;
    }

    public function touchSeen(): bool
    {
        return $this->forceFill(['last_seen_at' => now()])->save();
    }
}
