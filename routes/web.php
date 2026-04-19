<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/verify', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/verify', [OtpController::class, 'verify'])->name('otp.verify');
});

// Authenticated + OTP verified routes
Route::middleware(['auth', 'otp'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/sales', [SalesController::class, 'index'])->name('sales');
    Route::get('/sales/data', [SalesController::class, 'data'])->name('sales.data');
    Route::get('/stock/data', [StockController::class, 'data'])->name('stock.data');
    Route::post('/logout', LogoutController::class)->name('logout');

    // Admin only
    Route::middleware('can:admin')->group(function () {
        Route::resource('admin/users', UserController::class)->names('admin.users');
    });
});
