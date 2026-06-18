<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\PrintJobRun;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const PERMISSIONS = [
        'manage_users'       => 'Manage Users',
        'print_settings'     => 'Print Settings',
        'envelope_settings'  => 'Envelope Settings',
        'policy_settings'    => 'Policy Settings',
        'key_accounts_admin' => 'Key Accounts Admin',
        'key_actions_admin'  => 'Key Actions Admin',
        'action_plans_admin' => 'Action Plans Admin',
        'factory_training'   => 'Factory Training',
        'imports'            => 'Imports',
    ];

    const MODULES = [
        'sales'            => 'Sales',
        'stock'            => 'Stock Overview',
        'stock_ordering'   => 'Stock Watchlist',
        'envelopes'        => 'Church Envelopes',
        'policies'         => 'Policies',
        'print_schedule'   => 'Print Schedule',
        'amazon'           => 'Amazon & Xero',
        'key_accounts'     => 'Key Accounts',
        'crm'              => 'Customer Insights (CRM)',
        'factory_training' => 'Factory Training',
        'reminders'        => 'Order Reminders',
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'permissions', 'modules', 'last_login_at', 'operator_pin'];
    protected $hidden   = ['password', 'remember_token', 'operator_pin'];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'operator_pin'  => 'hashed',
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
        return $this->role === 'master';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isMaster()) {
            return true;
        }

        return in_array($permission, $this->permissions ?? [], true);
    }

    public function hasModule(string $module): bool
    {
        if ($this->isMaster()) {
            return true;
        }

        // null means all modules visible (default for existing/new staff)
        if ($this->modules === null) {
            return true;
        }

        return in_array($module, $this->modules, true);
    }

    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class);
    }

    public function runs()
    {
        return $this->hasMany(PrintJobRun::class);
    }
}
