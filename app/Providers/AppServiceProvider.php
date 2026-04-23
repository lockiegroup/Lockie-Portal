<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('admin', fn($user) => $user->isAdmin());

        Gate::define('manage_users', fn($user) => $user->hasPermission('manage_users'));
        Gate::define('print_settings', fn($user) => $user->hasPermission('print_settings'));
        Gate::define('envelope_settings', fn($user) => $user->hasPermission('envelope_settings'));
    }
}
