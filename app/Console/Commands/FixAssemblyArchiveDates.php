<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Console\Command;

class FixAssemblyArchiveDates extends Command
{
    protected $signature   = 'print:fix-assembly-dates
                                {--dry-run : Show what would change without updating}';
    protected $description = 'Fix assembly archive dates incorrectly set to today during sync';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        if ($dry) {
            $this->warn('DRY RUN — nothing will be updated.');
        }

        // Target assemblies archived today as 'completed' — these were swept by the sync
        // and got archived_at = now() instead of their real completion date.
        $jobs = PrintJob::whereNotNull('archived_at')
            ->where('archive_reason', 'completed')
            ->where('order_number', 'like', 'ASM-%')
            ->where('is_manual', false)
            ->whereDate('archived_at', today())
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No assemblies archived today found.');
            return self::SUCCESS;
        }

        $this->info("Found {$jobs->count()} assembly job(s) archived today.");
        $this->info('Fetching full assembly list from Unleashed...');

        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        // Full list includes completed assemblies with their actual AssemblyDate
        $all     = $unleashed->paginateFast('Assemblies', [], 200);
        $dateMap = [];
        foreach ($all as $assembly) {
            $guid = $assembly['Guid'] ?? null;
            if (!$guid) continue;
            $date = $unleashed->parseDate($assembly['AssemblyDate'] ?? null);
            if ($date) {
                $dateMap[$guid] = $date;
            }
        }

        $fixed   = 0;
        $skipped = 0;
        foreach ($jobs as $job) {
            $correctDate = $dateMap[$job->unleashed_guid] ?? null;
            if (!$correctDate) {
                $this->line("  SKIP {$job->order_number} — no assembly date in Unleashed");
                $skipped++;
                continue;
            }
            $this->line("  {$job->order_number} — {$job->archived_at->toDateString()} → {$correctDate}");
            if (!$dry) {
                $job->update(['archived_at' => $correctDate]);
            }
            $fixed++;
        }

        $this->newLine();
        $action = $dry ? 'Would fix' : 'Fixed';
        $this->info("{$action}: {$fixed} job(s). Skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
