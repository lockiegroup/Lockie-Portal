<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ChurchEnvelopeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\EnvelopeSettingsController;
use App\Http\Controllers\Admin\PrintScheduleSettingController;
use App\Http\Controllers\HealthSafety\ActionController as HsActionController;
use App\Http\Controllers\HealthSafety\SettingsController as HsSettingsController;
use App\Http\Controllers\PrintScheduleController;
use App\Http\Controllers\PrintJobArchiveController;
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
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/data', [StockController::class, 'data'])->name('stock.data');
    Route::post('/logout', LogoutController::class)->name('logout');

    // Church Envelope Generator
    Route::get('/church-envelopes', [ChurchEnvelopeController::class, 'index'])->name('church-envelopes.index');
    Route::post('/church-envelopes/parse', [ChurchEnvelopeController::class, 'parse'])->name('church-envelopes.parse');
    Route::post('/church-envelopes/generate', [ChurchEnvelopeController::class, 'generate'])->name('church-envelopes.generate');

    // Health & Safety
    Route::prefix('health-safety')->name('hs.')->group(function () {
        Route::get('actions', [HsActionController::class, 'index'])->name('actions.index');
        Route::get('actions/create', [HsActionController::class, 'create'])->name('actions.create');
        Route::post('actions', [HsActionController::class, 'store'])->name('actions.store');
        Route::get('actions/{action}/edit', [HsActionController::class, 'edit'])->name('actions.edit');
        Route::put('actions/{action}', [HsActionController::class, 'update'])->name('actions.update');
        Route::delete('actions/{action}', [HsActionController::class, 'destroy'])->name('actions.destroy');
        Route::patch('actions/{action}/complete', [HsActionController::class, 'complete'])->name('actions.complete');

        Route::middleware('can:admin')->group(function () {
            Route::get('settings', [HsSettingsController::class, 'index'])->name('settings.index');
            Route::post('settings', [HsSettingsController::class, 'update'])->name('settings.update');
        });
    });

    // Print Schedule
    Route::prefix('print-schedule')->name('print.')->group(function () {
        Route::get('/', [PrintScheduleController::class, 'index'])->name('index');
        Route::get('/archive', [PrintJobArchiveController::class, 'index'])->name('archive');
        Route::get('/overview', [PrintScheduleController::class, 'overview'])->name('overview');
        Route::post('/sync', [PrintScheduleController::class, 'sync'])->name('sync');
        Route::post('/jobs/{job}/board', [PrintScheduleController::class, 'moveBoard'])->name('jobs.board');
        Route::post('/jobs/reorder', [PrintScheduleController::class, 'reorder'])->name('jobs.reorder');
        Route::post('/jobs/{job}/complete', [PrintScheduleController::class, 'partComplete'])->name('jobs.complete');
        Route::post('/jobs/{job}/material', [PrintScheduleController::class, 'toggleMaterial'])->name('jobs.material');
        Route::post('/jobs/{job}/date', [PrintScheduleController::class, 'updateDate'])->name('jobs.date');
        Route::post('/jobs/{job}/notes', [PrintScheduleController::class, 'storeNote'])->name('jobs.notes.store');
        Route::delete('/jobs/{job}/notes/{note}', [PrintScheduleController::class, 'destroyNote'])->name('jobs.notes.destroy');
    });

    // Admin — manage users
    Route::middleware('can:manage_users')->group(function () {
        Route::resource('admin/users', UserController::class)->names('admin.users');
    });

    // Admin — print schedule settings
    Route::middleware('can:print_settings')->prefix('admin/print-schedule-settings')->name('admin.print-settings.')->group(function () {
        Route::get('/', [PrintScheduleSettingController::class, 'index'])->name('index');
        Route::post('/', [PrintScheduleSettingController::class, 'update'])->name('update');
    });

    // Admin — envelope settings
    Route::middleware('can:envelope_settings')->prefix('admin/envelope-settings')->name('admin.envelope-settings.')->group(function () {
        Route::get('/', [EnvelopeSettingsController::class, 'index'])->name('index');
        Route::post('/verses', [EnvelopeSettingsController::class, 'storeVerse'])->name('verses.store');
        Route::put('/verses/{verse}', [EnvelopeSettingsController::class, 'updateVerse'])->name('verses.update');
        Route::delete('/verses/{verse}', [EnvelopeSettingsController::class, 'destroyVerse'])->name('verses.destroy');
        Route::post('/verses/reorder', [EnvelopeSettingsController::class, 'reorderVerses'])->name('verses.reorder');
        Route::post('/spiral-path', [EnvelopeSettingsController::class, 'updateSpiralPath'])->name('spiral-path.update');
        Route::post('/designs', [EnvelopeSettingsController::class, 'storeDesign'])->name('designs.store');
        Route::put('/designs/{design}', [EnvelopeSettingsController::class, 'updateDesign'])->name('designs.update');
        Route::delete('/designs/{design}', [EnvelopeSettingsController::class, 'destroyDesign'])->name('designs.destroy');
        Route::post('/designs/reorder', [EnvelopeSettingsController::class, 'reorderDesigns'])->name('designs.reorder');
    });
});
