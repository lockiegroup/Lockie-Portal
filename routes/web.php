<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ChurchEnvelopeController;
use App\Http\Controllers\LetterFilterController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\EnvelopeSettingsController;
use App\Http\Controllers\Admin\PrintScheduleSettingController;
use App\Http\Controllers\PrintScheduleController;
use App\Http\Controllers\PrintJobArchiveController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\Admin\PolicyController as AdminPolicyController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\StockWatchlistController;
use App\Http\Controllers\AmazonController;
use App\Http\Controllers\ImportsController;
use App\Http\Controllers\KeyAccountController;
use App\Http\Controllers\Admin\KeyAccountAdminController;
use App\Http\Controllers\AbTestingController;
use App\Http\Controllers\KeyActionController;
use App\Http\Controllers\RemindersController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\ActionPlanController;
use App\Http\Controllers\TabletController;
use App\Http\Controllers\RackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

// Tablet — PIN-based, no standard auth required
Route::prefix('tablet')->name('tablet.')->group(function () {
    Route::get('/{machine}',                          [TabletController::class, 'show'])->name('show');
    Route::get('/{machine}/jobs-hash',                [TabletController::class, 'jobsHash'])->name('jobs.hash');
    Route::get('/{machine}/stats',                    [TabletController::class, 'stats'])->name('stats');
    Route::post('/{machine}/login',                   [TabletController::class, 'pinLogin'])->name('login');
    Route::post('/{machine}/logout',                  [TabletController::class, 'logout'])->name('logout');
    Route::post('/{machine}/jobs/{job}/progress',     [TabletController::class, 'updateProgress'])->name('jobs.progress');
    Route::post('/{machine}/jobs/{job}/start',        [TabletController::class, 'startJob'])->name('jobs.start');
    Route::post('/{machine}/jobs/{job}/pause',        [TabletController::class, 'pauseJob'])->name('jobs.pause');
    Route::post('/{machine}/jobs/{job}/resume',       [TabletController::class, 'resumeJob'])->name('jobs.resume');
    Route::post('/{machine}/jobs/{job}/end',          [TabletController::class, 'endJob'])->name('jobs.end');
    Route::post('/{machine}/jobs/{job}/handover',     [TabletController::class, 'handoverJob'])->name('jobs.handover');
    Route::post('/{machine}/jobs/{job}/correct-packs', [TabletController::class, 'correctPacks'])->name('jobs.correct-packs');
    // Redirect stale GET requests (e.g. browser navigating directly to a POST action URL) back to the tablet
    Route::get('/{machine}/jobs/{job}/{action}', fn(string $machine) => redirect()->route('tablet.show', $machine))
        ->where('action', 'start|pause|resume|end|handover|progress');
});

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
    // Racking
    Route::prefix('racking')->name('racking.')->group(function () {
        Route::get('/',                                   [RackingController::class, 'index'])->name('index');
        Route::post('/',                                  [RackingController::class, 'store'])->name('store');
        Route::put('/{rackingItem}',                      [RackingController::class, 'update'])->name('update');
        Route::delete('/{rackingItem}',                   [RackingController::class, 'destroy'])->name('destroy');
        Route::post('/settings',                          [RackingController::class, 'updateSettings'])->name('settings');
        Route::post('/import',                            [RackingController::class, 'import'])->name('import');
        Route::get('/outside-storage',                    [RackingController::class, 'outside'])->name('outside');
        Route::post('/outside-storage',                   [RackingController::class, 'storeOutside'])->name('outside.store');
        Route::put('/outside-storage/{outsideStorageItem}', [RackingController::class, 'updateOutside'])->name('outside.update');
        Route::delete('/outside-storage/{outsideStorageItem}', [RackingController::class, 'destroyOutside'])->name('outside.destroy');
        Route::get('/movements',                          [RackingController::class, 'movements'])->name('movements');
        Route::post('/movements',                         [RackingController::class, 'storeMovement'])->name('movements.store');
        Route::delete('/movements/{stockMovement}',       [RackingController::class, 'destroyMovement'])->name('movements.destroy');
    });

    Route::get('/church-envelopes', [ChurchEnvelopeController::class, 'index'])->name('church-envelopes.index');
    Route::post('/church-envelopes/parse', [ChurchEnvelopeController::class, 'parse'])->name('church-envelopes.parse');
    Route::post('/church-envelopes/generate', [ChurchEnvelopeController::class, 'generate'])->name('church-envelopes.generate');
    Route::get('/church-envelopes/designer', [ChurchEnvelopeController::class, 'designer'])->name('church-envelopes.designer');

    // Letter Filter
    Route::get('/letter-filter', [LetterFilterController::class, 'index'])->name('letter-filter.index');
    Route::post('/letter-filter/process', [LetterFilterController::class, 'process'])->name('letter-filter.process');
    Route::get('/letter-filter/download/{key}/{file}', [LetterFilterController::class, 'download'])->name('letter-filter.download');

    // Print Schedule
    Route::prefix('print-schedule')->name('print.')->group(function () {
        Route::get('/', [PrintScheduleController::class, 'index'])->name('index');
        Route::get('/archive', [PrintJobArchiveController::class, 'index'])->name('archive');
        Route::get('/overview', [PrintScheduleController::class, 'overview'])->name('overview');
        Route::get('/production', [PrintScheduleController::class, 'production'])->name('production');
        Route::get('/production/status', [PrintScheduleController::class, 'productionStatus'])->name('production.status');
        Route::get('/machine-log', [PrintScheduleController::class, 'machineLog'])->name('machine-log');
        Route::get('/machine-log/export', [PrintScheduleController::class, 'machineLogExport'])->name('machine-log.export');
        Route::get('/analytics', [PrintScheduleController::class, 'analytics'])->name('analytics');
        Route::patch('/runs/{run}/packs', [PrintScheduleController::class, 'updateRunPacks'])->name('runs.packs.update');
        Route::post('/sync', [PrintScheduleController::class, 'sync'])->name('sync');
        Route::get('/sync/status', [PrintScheduleController::class, 'syncStatus'])->name('sync.status');
        Route::post('/jobs/{job}/unarchive', [PrintScheduleController::class, 'unarchive'])->name('jobs.unarchive');
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
        Route::get('/jobs/{job}/labels', [PrintScheduleController::class, 'downloadLabels'])->name('jobs.labels');
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

    // Stock Watchlist
    Route::middleware('can:stock_ordering')->prefix('stock-watchlist')->name('stock-watchlist.')->group(function () {
        Route::get('/', [StockWatchlistController::class, 'index'])->name('index');
        Route::post('/sync', [StockWatchlistController::class, 'sync'])->name('sync');
        Route::post('/sync-products', [StockWatchlistController::class, 'syncProducts'])->name('sync-products');
        Route::post('/sales/filter', [StockWatchlistController::class, 'setDateFilter'])->name('sales.filter');
        Route::get('/categories/{category}', [StockWatchlistController::class, 'showCategory'])->name('categories.show');
        Route::post('/categories', [StockWatchlistController::class, 'storeCategory'])->name('categories.store');
        Route::patch('/categories/{category}', [StockWatchlistController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [StockWatchlistController::class, 'destroyCategory'])->name('categories.destroy');
        Route::post('/categories/{category}/items', [StockWatchlistController::class, 'storeItem'])->name('items.store');
        Route::get('/categories/{category}/items/download', [StockWatchlistController::class, 'downloadItems'])->name('items.download');
        Route::post('/categories/{category}/items/import', [StockWatchlistController::class, 'importItems'])->name('items.import');
        Route::post('/categories/{category}/upload-shopify', [StockWatchlistController::class, 'uploadShopify'])->name('categories.upload-shopify');
        Route::post('/categories/{category}/upload-amazon', [StockWatchlistController::class, 'uploadAmazon'])->name('categories.upload-amazon');
        Route::post('/items/reorder', [StockWatchlistController::class, 'reorderItems'])->name('items.reorder');
        Route::post('/categories/reorder', [StockWatchlistController::class, 'reorderCategories'])->name('categories.reorder');
        Route::post('/items/clear-orders', [StockWatchlistController::class, 'clearOrders'])->name('items.clear-orders');
        Route::post('/substitutions', [StockWatchlistController::class, 'storeSubstitution'])->name('substitutions.store');
        Route::delete('/substitutions/{substitution}', [StockWatchlistController::class, 'destroySubstitution'])->name('substitutions.destroy');
        Route::patch('/items/{item}', [StockWatchlistController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{item}', [StockWatchlistController::class, 'destroyItem'])->name('items.destroy');
    });

    // Amazon & Xero Reconciliation
    Route::prefix('amazon')->name('amazon.')->middleware('module:amazon')->group(function () {
        Route::get('/',                         [AmazonController::class, 'index'])->name('index');
        Route::post('/sync',                    [AmazonController::class, 'sync'])->name('sync');
        Route::get('/settlements',              [AmazonController::class, 'settlements'])->name('settlements');
        Route::get('/settlements/{settlement}', [AmazonController::class, 'settlementDetail'])->name('settlement.detail');
        Route::get('/settlements/{settlement}/csv',                   [AmazonController::class, 'settlementCsv'])->name('settlement.csv');
        Route::get('/settlements/{settlement}/view',                  [AmazonController::class, 'settlementView'])->name('settlement.view');
        Route::post('/settlements/{settlement}/reprocess',           [AmazonController::class, 'reprocessSettlement'])->name('settlement.reprocess');
        Route::post('/settlements/{settlement}/lookup-unleashed',  [AmazonController::class, 'lookupUnleashedOrders'])->name('settlement.lookup-unleashed');
        Route::post('/settlements/{settlement}/set-so',           [AmazonController::class, 'setOrderSo'])->name('settlement.set-so');
        Route::get('/profit',                   [AmazonController::class, 'profitReport'])->name('profit');
        Route::get('/xero/connect',             [AmazonController::class, 'xeroConnect'])->name('xero.connect');
        Route::get('/xero/callback',            [AmazonController::class, 'xeroCallback'])->name('xero.callback');
        Route::post('/xero/post/{settlement}',  [AmazonController::class, 'xeroPost'])->name('xero.post');
    });

    // Shared imports
    Route::get('/imports', [ImportsController::class, 'index'])->name('imports.index');
    Route::post('/imports/sales', [ImportsController::class, 'storeSales'])->name('imports.sales');
    Route::post('/imports/credits', [ImportsController::class, 'storeCredits'])->name('imports.credits');
    Route::post('/imports/substitutions', [ImportsController::class, 'storeSubstitution'])->name('imports.substitutions.store');
    Route::delete('/imports/substitutions/{substitution}', [ImportsController::class, 'destroySubstitution'])->name('imports.substitutions.destroy');

    // Key Accounts (admin management)
    Route::middleware('can:key_accounts_admin')->prefix('admin/key-accounts')->name('admin.key-accounts.')->group(function () {
        Route::get('/', [KeyAccountAdminController::class, 'index'])->name('index');
        Route::post('/', [KeyAccountAdminController::class, 'store'])->name('store');
        Route::post('/reorder', [KeyAccountAdminController::class, 'reorder'])->name('reorder');
        Route::put('/{keyAccount}', [KeyAccountAdminController::class, 'update'])->name('update');
        Route::delete('/{keyAccount}', [KeyAccountAdminController::class, 'destroy'])->name('destroy');
    });

    // CRM
    Route::prefix('crm')->name('crm.')->middleware('module:crm')->group(function () {
        Route::get('/',                                          [CrmController::class, 'index'])->name('index');
        Route::get('/export',                                    [CrmController::class, 'export'])->name('export');
        Route::post('/bulk-contacts/preview',                    [CrmController::class, 'previewBulkContacts'])->name('bulk-contacts.preview');
        Route::post('/bulk-contacts',                            [CrmController::class, 'bulkStoreContacts'])->name('bulk-contacts.store');
        Route::get('/{customerCode}',                            [CrmController::class, 'show'])->name('show');
        Route::patch('/{customerCode}/notes',                    [CrmController::class, 'updateNotes'])->name('notes.update');
        Route::post('/{customerCode}/contacts',                  [CrmController::class, 'storeContact'])->name('contacts.store');
        Route::delete('/{customerCode}/contacts/{contact}',      [CrmController::class, 'destroyContact'])->name('contacts.destroy');
    });

    // Key Accounts (salesperson views)
    Route::prefix('key-accounts')->name('key-accounts.')->middleware('module:key_accounts')->group(function () {
        Route::get('/', [KeyAccountController::class, 'index'])->name('index');
        Route::post('/sales/filter', [KeyAccountController::class, 'setDateFilter'])->name('sales.filter');
        Route::post('/gifts/import', [KeyAccountController::class, 'importGifts'])->name('gifts.import');
        Route::get('/gifts/export', [KeyAccountController::class, 'exportGifts'])->name('gifts.export');
        Route::get('/{keyAccount}', [KeyAccountController::class, 'show'])->name('show');
        Route::post('/{keyAccount}/contacts', [KeyAccountController::class, 'storeContact'])->name('contacts.store');
        Route::delete('/{keyAccount}/contacts/{contact}', [KeyAccountController::class, 'destroyContact'])->name('contacts.destroy');
        Route::patch('/{keyAccount}/notes', [KeyAccountController::class, 'updateNotes'])->name('notes.update');
    });

    // Reminders
    Route::middleware('can:reminders')->prefix('reminders')->name('reminders.')->group(function () {
        Route::get('/',                          [RemindersController::class, 'index'])->name('index');
        Route::get('/overview',                  [RemindersController::class, 'overview'])->name('overview');
        Route::post('/clear-month',              [RemindersController::class, 'clearMonth'])->name('clear-month');
        Route::post('/import-entries',           [RemindersController::class, 'importEntries'])->name('import-entries');
        Route::post('/import-phones',            [RemindersController::class, 'importPhones'])->name('import-phones');
        Route::post('/import-orders',            [RemindersController::class, 'importOrders'])->name('import-orders');
        Route::patch('/entries/{entry}',         [RemindersController::class, 'update'])->name('update');
        Route::post('/entries/{entry}/move',     [RemindersController::class, 'moveEntry'])->name('move');
        Route::get('/poll',                      [RemindersController::class, 'poll'])->name('poll');
        Route::post('/export',                   [RemindersController::class, 'export'])->name('export');
    });

    // Factory Training
    // Read-only: full-access OR view-only permission
    Route::middleware('can:factory_training_view')->prefix('training')->name('training.')->group(function () {
        Route::get('/',                                          [TrainingController::class, 'index'])->name('index');
        Route::get('/records/{record}/pdf',                      [TrainingController::class, 'downloadPdf'])->name('records.pdf');
    });
    // Edit: full-access permission only
    Route::middleware('can:factory_training')->prefix('training')->name('training.')->group(function () {
        Route::post('/machines',                                 [TrainingController::class, 'storeMachine'])->name('machines.store');
        Route::post('/machines/reorder',                         [TrainingController::class, 'reorderMachines'])->name('machines.reorder');
        Route::put('/machines/{machine}',                        [TrainingController::class, 'updateMachine'])->name('machines.update');
        Route::delete('/machines/{machine}',                     [TrainingController::class, 'destroyMachine'])->name('machines.destroy');
        Route::post('/operators',                                [TrainingController::class, 'storeOperator'])->name('operators.store');
        Route::put('/operators/{operator}',                      [TrainingController::class, 'updateOperator'])->name('operators.update');
        Route::delete('/operators/{operator}',                   [TrainingController::class, 'destroyOperator'])->name('operators.destroy');
        Route::post('/records',                                  [TrainingController::class, 'storeRecord'])->name('records.store');
        Route::delete('/records/{record}',                       [TrainingController::class, 'destroyRecord'])->name('records.destroy');
        Route::post('/planned',                                  [TrainingController::class, 'storePlanned'])->name('planned.store');
        Route::delete('/planned/{planned}',                      [TrainingController::class, 'destroyPlanned'])->name('planned.destroy');
        Route::patch('/planned/{planned}/complete',              [TrainingController::class, 'completePlanned'])->name('planned.complete');
        Route::post('/departments',                              [TrainingController::class, 'storeDepartment'])->name('departments.store');
        Route::put('/departments/{department}',                  [TrainingController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}',               [TrainingController::class, 'destroyDepartment'])->name('departments.destroy');
    });

    // A/B Testing
    Route::prefix('ab-testing')->name('ab-testing.')->group(function () {
        Route::get('/',                          [AbTestingController::class, 'index'])->name('index');
        Route::post('/tests',                    [AbTestingController::class, 'storeTest'])->name('tests.store');
        Route::put('/tests/{test}',              [AbTestingController::class, 'updateTest'])->name('tests.update');
        Route::delete('/tests/{test}',           [AbTestingController::class, 'destroyTest'])->name('tests.destroy');
        Route::post('/rules',                    [AbTestingController::class, 'storeRule'])->name('rules.store');
        Route::put('/rules/{rule}',              [AbTestingController::class, 'updateRule'])->name('rules.update');
        Route::delete('/rules/{rule}',           [AbTestingController::class, 'destroyRule'])->name('rules.destroy');
        Route::post('/rules/reorder',            [AbTestingController::class, 'reorderRules'])->name('rules.reorder');
        Route::post('/divisions',                [AbTestingController::class, 'storeDivision'])->name('divisions.store');
        Route::delete('/divisions/{division}',   [AbTestingController::class, 'destroyDivision'])->name('divisions.destroy');
    });

    // Key Actions
    Route::prefix('key-actions')->name('key-actions.')->group(function () {
        Route::get('/',                                                      [KeyActionController::class, 'index'])->name('index');
        Route::post('/',                                                     [KeyActionController::class, 'store'])->name('store');
        Route::get('/{group}',                                               [KeyActionController::class, 'show'])->name('show');
        Route::get('/{group}/hash',                                          [KeyActionController::class, 'hash'])->name('hash');
        Route::patch('/{group}',                                             [KeyActionController::class, 'update'])->name('update');
        Route::delete('/{group}',                                            [KeyActionController::class, 'destroy'])->name('destroy');
        Route::get('/{group}/agenda',                                        [KeyActionController::class, 'downloadAgenda'])->name('agenda.download');
        Route::post('/{group}/agenda',                                       [KeyActionController::class, 'uploadAgenda'])->name('agenda.upload');
        Route::delete('/{group}/agenda',                                     [KeyActionController::class, 'deleteAgenda'])->name('agenda.delete');
        Route::post('/{group}/columns/reorder',                              [KeyActionController::class, 'reorderColumns'])->name('columns.reorder');
        Route::post('/{group}/buckets',                                      [KeyActionController::class, 'storeBucket'])->name('buckets.store');
        Route::patch('/{group}/buckets/{bucket}',                            [KeyActionController::class, 'updateBucket'])->name('buckets.update');
        Route::delete('/{group}/buckets/{bucket}',                           [KeyActionController::class, 'destroyBucket'])->name('buckets.destroy');
        Route::post('/{group}/members',                                      [KeyActionController::class, 'addMember'])->name('members.add');
        Route::patch('/{group}/members/{member}',                            [KeyActionController::class, 'updateMember'])->name('members.update');
        Route::delete('/{group}/members/{member}',                           [KeyActionController::class, 'removeMember'])->name('members.remove');
        Route::post('/{group}/tasks',                                        [KeyActionController::class, 'storeTask'])->name('tasks.store');
        Route::get('/{group}/tasks/{task}',                                  [KeyActionController::class, 'showTask'])->name('tasks.show');
        Route::patch('/{group}/tasks/{task}',                                [KeyActionController::class, 'updateTask'])->name('tasks.update');
        Route::patch('/{group}/tasks/{task}/complete',                       [KeyActionController::class, 'completeTask'])->name('tasks.complete');
        Route::delete('/{group}/tasks/{task}',                               [KeyActionController::class, 'destroyTask'])->name('tasks.destroy');
        Route::post('/{group}/tasks/reorder',                                [KeyActionController::class, 'reorderTasks'])->name('tasks.reorder');
        Route::post('/{group}/tasks/{task}/comments',                        [KeyActionController::class, 'storeComment'])->name('tasks.comments.store');
        Route::delete('/{group}/tasks/{task}/comments/{comment}',            [KeyActionController::class, 'destroyComment'])->name('tasks.comments.destroy');
        Route::get('/comment-image/{filename}',                              [KeyActionController::class, 'serveCommentImage'])->name('comment-image');
    });

    // Impersonation (stop must be before {user} to avoid route conflict)
    Route::post('/impersonate/stop',   [ImpersonateController::class, 'stop'])->name('impersonate.stop');
    Route::post('/impersonate/{user}', [ImpersonateController::class, 'start'])->name('impersonate.start');

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

    // Action Plans
    Route::prefix('action-plans')->name('action-plans.')->group(function () {
        Route::get('/',                                          [ActionPlanController::class, 'index'])->name('index');
        Route::get('/{plan}',                                    [ActionPlanController::class, 'show'])->name('show');
        Route::post('/{plan}/items',                             [ActionPlanController::class, 'storeItem'])->name('items.store');
        Route::put('/{plan}/items/{item}',                       [ActionPlanController::class, 'updateItem'])->name('items.update');
        Route::delete('/{plan}/items/{item}',                    [ActionPlanController::class, 'destroyItem'])->name('items.destroy');
        Route::post('/{plan}/copy',                              [ActionPlanController::class, 'copyItems'])->name('items.copy');
        Route::middleware('can:action_plans_admin')->group(function () {
            Route::post('/reorder',                              [ActionPlanController::class, 'reorder'])->name('reorder');
            Route::post('/',                                     [ActionPlanController::class, 'store'])->name('store');
            Route::put('/{plan}',                                [ActionPlanController::class, 'update'])->name('update');
            Route::delete('/{plan}',                             [ActionPlanController::class, 'destroy'])->name('destroy');
            Route::post('/{plan}/archive',                       [ActionPlanController::class, 'archive'])->name('archive');
            Route::post('/{plan}/unarchive',                     [ActionPlanController::class, 'unarchive'])->name('unarchive');
            Route::post('/{plan}/duplicate',                     [ActionPlanController::class, 'duplicate'])->name('duplicate');
            Route::post('/{plan}/members',                       [ActionPlanController::class, 'addMember'])->name('members.add');
            Route::delete('/{plan}/members/{user}',              [ActionPlanController::class, 'removeMember'])->name('members.remove');
            Route::post('/{plan}/import',                        [ActionPlanController::class, 'import'])->name('import');
        });
    });
});
