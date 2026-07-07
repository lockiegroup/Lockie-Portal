<?php

namespace App\Console\Commands;

use App\Models\PrintJobRun;
use Illuminate\Console\Command;

class CloseStaleRuns extends Command
{
    protected $signature   = 'print:close-stale-runs {--dry-run}';
    protected $description = 'Auto-close open runs whose print job has been archived';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        // Find open runs whose job is already archived
        $stale = PrintJobRun::whereNull('ended_at')
            ->whereHas('printJob', fn ($q) => $q->whereNotNull('archived_at'))
            ->with('printJob:id,order_number,product_description')
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No stale runs found.');
            return 0;
        }

        foreach ($stale as $run) {
            $job = $run->printJob;
            $this->line(sprintf(
                '%s run #%d on %s for %s (%s)',
                $dry ? '[dry-run] Would close' : 'Closing',
                $run->id,
                $run->machine,
                $job?->order_number ?? '?',
                $job?->product_description ?? '?'
            ));

            if (!$dry) {
                $run->update(['ended_at' => now(), 'end_reason' => 'auto_closed']);
            }
        }

        $this->info(($dry ? '[dry-run] ' : '') . "Done — {$stale->count()} run(s) processed.");

        return 0;
    }
}
