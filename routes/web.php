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
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\Admin\PolicyController as AdminPolicyController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\SupplierSettingsController;
use App\Http\Controllers\StockForecastController;
use App\Http\Controllers\StockWatchlistController;
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
        Route::post('/manual', [PrintScheduleController::class, 'storeManual'])->name('jobs.manual.store');
        Route::put('/jobs/{job}/manual-update', [PrintScheduleController::class, 'updateManual'])->name('jobs.manual.update');
        Route::delete('/jobs/{job}/manual-delete', [PrintScheduleController::class, 'deleteManual'])->name('jobs.manual.delete');
        Route::post('/jobs/{job}/manual-complete', [PrintScheduleController::class, 'completeManual'])->name('jobs.manual.complete');
        Route::post('/jobs/{job}/manual-archive', [PrintScheduleController::class, 'archiveManual'])->name('jobs.manual.archive');
    });

    // Cash Flow
    Route::middleware('can:cash_flow')->prefix('cash-flow')->name('cash-flow.')->group(function () {
        Route::get('/', [CashFlowController::class, 'index'])->name('index');
        Route::post('/entries', [CashFlowController::class, 'store'])->name('store');
        Route::put('/entries/{entry}', [CashFlowController::class, 'update'])->name('update');
        Route::delete('/entries/{entry}', [CashFlowController::class, 'destroy'])->name('destroy');
        Route::post('/opening-balance', [CashFlowController::class, 'updateOpeningBalance'])->name('opening-balance');
        Route::post('/categories', [CashFlowController::class, 'storeCategory'])->name('categories.store');
        Route::delete('/categories/{category}', [CashFlowController::class, 'destroyCategory'])->name('categories.destroy');
    });

    // Company Policies — all authenticated staff can view
    Route::get('/policies', [PolicyController::class, 'index'])->name('policies.index');
    Route::get('/policies/{policy}/download', [PolicyController::class, 'download'])->name('policies.download');

    // Admin — policy settings
    Route::middleware('can:policy_settings')->prefix('admin/policies')->name('admin.policies.')->group(function () {
        Route::get('/', [AdminPolicyController::class, 'index'])->name('index');
        Route::post('/', [AdminPolicyController::class, 'store'])->name('store');
        Route::put('/{policy}', [AdminPolicyController::class, 'update'])->name('update');
        Route::delete('/{policy}', [AdminPolicyController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [AdminPolicyController::class, 'reorder'])->name('reorder');
        Route::post('/categories', [AdminPolicyController::class, 'storeCategory'])->name('categories.store');
        Route::delete('/categories/{category}', [AdminPolicyController::class, 'destroyCategory'])->name('categories.destroy');
    });

    // Stock Forecast
    Route::middleware('can:stock_forecast')->group(function () {
        Route::get('/stock-forecast', [StockForecastController::class, 'index'])->name('stock-forecast.index');
        Route::post('/stock-forecast/sync', [StockForecastController::class, 'sync'])->name('stock-forecast.sync');
        Route::patch('/stock-forecast/lines/{line}/lead-time', [StockForecastController::class, 'updateLeadTime'])->name('stock-forecast.lead-time');
    });

    // Stock Watchlist
    Route::middleware('can:stock_ordering')->prefix('stock-watchlist')->name('stock-watchlist.')->group(function () {
        Route::get('/', [StockWatchlistController::class, 'index'])->name('index');
        Route::post('/sync', [StockWatchlistController::class, 'sync'])->name('sync');
        Route::post('/categories', [StockWatchlistController::class, 'storeCategory'])->name('categories.store');
        Route::patch('/categories/{category}', [StockWatchlistController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [StockWatchlistController::class, 'destroyCategory'])->name('categories.destroy');
        Route::post('/categories/{category}/items', [StockWatchlistController::class, 'storeItem'])->name('items.store');
        Route::patch('/items/{item}', [StockWatchlistController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{item}', [StockWatchlistController::class, 'destroyItem'])->name('items.destroy');
    });

    // Admin — supplier settings
    Route::middleware('can:supplier_settings')->group(function () {
        Route::get('/admin/supplier-settings', [SupplierSettingsController::class, 'index'])->name('admin.supplier-settings.index');
        Route::post('/admin/supplier-settings', [SupplierSettingsController::class, 'update'])->name('admin.supplier-settings.update');
    });

    // Admin — manage users + activity log
    Route::middleware('can:manage_users')->group(function () {
        Route::resource('admin/users', UserController::class)->names('admin.users');
        Route::get('admin/activity-log', ActivityLogController::class)->name('admin.activity-log');
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
