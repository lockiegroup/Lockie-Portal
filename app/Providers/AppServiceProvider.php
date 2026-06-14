<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('admin', fn($user) => $user->isMaster() || !empty($user->permissions));

        Gate::define('manage_users', fn($user) => $user->hasPermission('manage_users'));
        Gate::define('print_settings', fn($user) => $user->hasPermission('print_settings'));
        Gate::define('envelope_settings', fn($user) => $user->hasPermission('envelope_settings'));
        Gate::define('policy_settings', fn($user) => $user->hasPermission('policy_settings'));
        Gate::define('stock_ordering', fn($user) => $user->isMaster() || $user->hasModule('stock_ordering'));
        Gate::define('amazon-admin',        fn($user) => $user->isMaster());
        Gate::define('key_accounts_admin', fn($user) => $user->hasPermission('key_accounts_admin'));
        Gate::define('reminders',          fn($user) => $user->hasModule('reminders'));
        Gate::define('factory_training',      fn($user) => $user->hasPermission('factory_training'));
        Gate::define('factory_training_view', fn($user) => $user->hasPermission('factory_training') || $user->hasModule('factory_training'));
        Gate::define('imports',               fn($user) => $user->isMaster() || $user->hasPermission('imports'));
        Gate::define('action_plans_admin',    fn($user) => $user->isMaster() || $user->hasPermission('action_plans_admin'));
        Gate::define('key_actions_admin',     fn($user) => $user->isMaster() || $user->hasPermission('key_actions_admin'));
    }
}
