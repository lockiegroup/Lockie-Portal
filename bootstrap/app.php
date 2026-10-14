<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'otp'    => \App\Http\Middleware\OtpVerified::class,
            'module' => \App\Http\Middleware\ModuleAccess::class,
        ]);
        // Device token is hashed in the DB — no value in encrypting the cookie itself
        $middleware->encryptCookies(except: ['clocking_device']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
