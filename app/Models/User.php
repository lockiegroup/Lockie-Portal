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
        'cash_flow'         => 'Cash Flow',
        'policy_settings'   => 'Policy Settings',
    ];

    const MODULES = [
        'sales'          => 'Sales',
        'stock'          => 'Stock Overview',
        'health_safety'  => 'Health & Safety',
        'envelopes'      => 'Church Envelopes',
        'policies'       => 'Policies',
        'print_schedule' => 'Print Schedule',
        'cash_flow'      => 'Cash Flow',
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'permissions', 'modules', 'last_login_at'];
    protected $hidden   = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'is_active'     => 'boolean',
            'permissions'   => 'array',
            'modules'       => 'array',
            'last_login_at' => 'datetime',
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

        if ($this->isAdmin()) {
            // Existing admins with no permissions set get full access (backward compat)
            return $this->permissions === null || in_array($permission, $this->permissions, true);
        }

        // Staff can access cash_flow if it's enabled as a module for them
        if ($permission === 'cash_flow') {
            return $this->hasModule('cash_flow');
        }

        return false;
    }

    public function hasModule(string $module): bool
    {
        // Admins and masters always have full module access
        if ($this->isAdmin()) {
            return true;
        }

        // null means all modules visible (default for existing staff)
        if ($this->modules === null) {
            return true;
        }

        return in_array($module, $this->modules, true);
    }

    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class);
    }
}
