<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use Illuminate\Console\Command;

class CleanupDuplicatePrintJobs extends Command
{
    protected $signature   = 'print:cleanup-duplicates
                                {--dry-run : Show what would be removed without deleting anything}';
    protected $description = 'Remove stale archived records where an active copy of the same job exists';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        if ($dry) {
            $this->warn('DRY RUN — nothing will be deleted.');
        }

        // Find every unleashed_guid+line_number that has more than one row
        $dupes = PrintJob::select('unleashed_guid', 'line_number')
            ->where('is_manual', false)
            ->groupBy('unleashed_guid', 'line_number')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($dupes->isEmpty()) {
            $this->info('No duplicate records found.');
            return self::SUCCESS;
        }

        $this->info("Found {$dupes->count()} GUID+line combination(s) with duplicates.");
        $this->newLine();

        $removed = 0;

        foreach ($dupes as $dupe) {
            $jobs     = PrintJob::where('unleashed_guid', $dupe->unleashed_guid)
                ->where('line_number', $dupe->line_number)
                ->get();

            $active   = $jobs->whereNull('archived_at');
            $archived = $jobs->whereNotNull('archived_at');

            if ($active->count() > 0) {
                // Active record exists — the archived copies are stale, remove them
                foreach ($archived as $job) {
                    $label = $job->archive_reason ?? 'no reason';
                    $this->line("  Removing stale archived #{$job->id} — {$job->order_number} L{$job->line_number} [{$label}]");
                    if (!$dry) {
                        $job->delete();
                    }
                    $removed++;
                }
            } else {
                // All copies are archived — keep the one with an archive_reason, drop the rest
                $keep = $archived->firstWhere('archive_reason', '!=', null) ?? $archived->first();
                foreach ($archived as $job) {
                    if ($job->id === $keep->id) continue;
                    $label = $job->archive_reason ?? 'no reason';
                    $this->line("  Removing duplicate archived #{$job->id} — {$job->order_number} L{$job->line_number} [{$label}] (keeping #{$keep->id})");
                    if (!$dry) {
                        $job->delete();
                    }
                    $removed++;
                }
            }
        }

        $this->newLine();
        $action = $dry ? 'Would remove' : 'Removed';
        $this->info("{$action}: {$removed} duplicate record(s).");

        return self::SUCCESS;
    }
}
