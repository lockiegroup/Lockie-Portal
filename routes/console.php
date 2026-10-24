<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync A1 print jobs from Unleashed every 30 minutes
Schedule::command('print:sync')->everyThirtyMinutes();

// Safety net: close any open runs whose job has already been archived
Schedule::command('print:close-stale-runs')->hourly();

// Nightly reconciliation: correct assembly archive labels (completed vs deleted)
Schedule::command('print:fix-archive-labels', ['--include-completed'])->dailyAt('02:00');

// Pre-fetch Key Account quarterly sales from Unleashed nightly
Schedule::command('key-accounts:fetch-sales')->dailyAt('02:30');

// Sync Amazon settlements and ad spend daily at 6am
Schedule::command('amazon:sync --settlements --ads')->dailyAt('06:00');

// CRM: send follow-up reminders daily at 08:00
Schedule::command('crm:send-followup-reminders')->dailyAt('08:00');

// Clocking: send absence alerts every 15 minutes during working hours
Schedule::command('clocking:send-alerts')->everyFifteenMinutes();

// Clocking: send clockout reminders every 15 minutes (fires once per employee per day via log)
Schedule::command('clocking:send-clockout-reminders')->everyFifteenMinutes();
