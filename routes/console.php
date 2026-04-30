<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mark overdue H&S actions and send reminder emails every morning at 8am
Schedule::command('hs:send-reminders')->dailyAt('08:00');

// Sync A1 print jobs from Unleashed every 30 minutes
Schedule::command('print:sync')->everyThirtyMinutes();

// Nightly reconciliation: correct assembly archive labels (completed vs deleted)
Schedule::command('print:fix-archive-labels', ['--include-completed'])->dailyAt('02:00');

// Pre-fetch Key Account quarterly sales from Unleashed nightly
Schedule::command('key-accounts:fetch-sales')->dailyAt('02:30');

// Sync Stock Watchlist stock levels and PO data nightly
Schedule::command('stock-watchlist:sync')->dailyAt('03:00');
