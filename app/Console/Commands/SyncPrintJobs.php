<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\PrintScheduleSyncService;
use Illuminate\Console\Command;

class SyncPrintJobs extends Command
{
    protected $signature   = 'print:sync';
    protected $description = 'Sync A1 print schedule and assemblies from Unleashed';

    public function handle(): int
    {
        ['created' => $created, 'updated' => $updated] = (new PrintScheduleSyncService)->run();
        ActivityLog::record('print.sync', "Scheduled sync: {$created} created, {$updated} updated");
        $this->info("Synced: {$created} created, {$updated} updated.");
        return self::SUCCESS;
    }
}
