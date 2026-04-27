<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\PrintScheduleSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncPrintJobs extends Command
{
    protected $signature   = 'print:sync';
    protected $description = 'Sync A1 print schedule and assemblies from Unleashed';

    public function handle(): int
    {
        Cache::put('print_sync_status', ['status' => 'running', 'at' => now()->toIso8601String()], 600);

        try {
            ['created' => $created, 'updated' => $updated] = (new PrintScheduleSyncService)->run();
            ActivityLog::record('print.sync', "Scheduled sync: {$created} created, {$updated} updated");
            Cache::put('print_sync_status', [
                'status'  => 'done',
                'created' => $created,
                'updated' => $updated,
                'at'      => now()->toIso8601String(),
            ], 600);
            $this->info("Synced: {$created} created, {$updated} updated.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Cache::put('print_sync_status', [
                'status' => 'failed',
                'error'  => $e->getMessage(),
                'at'     => now()->toIso8601String(),
            ], 600);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
