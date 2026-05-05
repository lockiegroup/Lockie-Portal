<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use Illuminate\Console\Command;

class PurgeHProducts extends Command
{
    protected $signature   = 'print:purge-h-products
                                {--dry-run : Show what would be deleted without removing anything}';
    protected $description = 'Delete all archived print jobs whose product code starts with H-';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        if ($dry) {
            $this->warn('DRY RUN — nothing will be deleted.');
        }

        $jobs = PrintJob::whereNotNull('archived_at')
            ->where('product_code', 'like', 'H-%')
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No H- product jobs found in the archive.');
            return self::SUCCESS;
        }

        $this->info("Found {$jobs->count()} archived job(s) with H- product codes.");

        foreach ($jobs as $job) {
            $this->line("  Deleting {$job->order_number} — {$job->product_code} ({$job->unleashed_guid})");
            if (!$dry) {
                $job->delete();
            }
        }

        $this->newLine();
        $action = $dry ? 'Would delete' : 'Deleted';
        $this->info("{$action}: {$jobs->count()} job(s).");

        return self::SUCCESS;
    }
}
