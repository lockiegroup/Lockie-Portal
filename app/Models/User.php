<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const PERMISSIONS = [
        'manage_users'      => 'Manage Users',
        'print_settings'    => 'Print Settings',
        'envelope_settings' => 'Envelope Settings',
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'permissions'];
    protected $hidden   = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'is_active'   => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function isMaster(): bool
    {
        return $this->role === 'master';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'master';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isMaster()) {
            return true;
        }

        if ($this->role !== 'admin') {
            return false;
        }

        // Existing admins with no permissions set get full access (backward compat)
        if ($this->permissions === null) {
            return true;
        }

        return in_array($permission, $this->permissions, true);
    }

    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class);
    }
}
