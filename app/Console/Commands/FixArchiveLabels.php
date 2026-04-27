<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Console\Command;

class FixArchiveLabels extends Command
{
    protected $signature   = 'print:fix-archive-labels
                                {--include-completed : Also check assemblies already labelled completed — relabels to deleted if gone from Unleashed}';
    protected $description = 'Fix archived assembly jobs that are missing or have an incorrect deletion/completion label';

    public function handle(): int
    {
        $includeCompleted = $this->option('include-completed');

        $query = PrintJob::whereNotNull('archived_at')
            ->where('order_number', 'like', 'ASM-%')
            ->where('is_manual', false);

        if ($includeCompleted) {
            $query->where(function ($q) {
                $q->whereNull('archive_reason')
                  ->orWhere('archive_reason', 'completed');
            });
        } else {
            $query->whereNull('archive_reason');
        }

        $jobs = $query->get();

        if ($jobs->isEmpty()) {
            $this->info('No assemblies to check.');
            return self::SUCCESS;
        }

        $label = $includeCompleted ? 'unlabelled or completed' : 'unlabelled';
        $this->info("Found {$jobs->count()} {$label} archived assembly job(s).");

        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        $fixed = 0;
        foreach ($jobs as $job) {
            $assembly  = $unleashed->fetchAssemblyByGuid($job->unleashed_guid);
            $status    = $assembly !== null ? strtolower($assembly['AssemblyStatus'] ?? '') : 'deleted';
            $newReason = $status === 'completed' ? 'completed' : 'deleted';

            if ($job->archive_reason === $newReason) {
                continue; // already correct
            }

            $job->update(['archive_reason' => $newReason]);
            $this->line("  {$job->order_number} ({$job->unleashed_guid}) → {$newReason}");
            $fixed++;
        }

        $this->info("Done. {$fixed} job(s) updated.");
        return self::SUCCESS;
    }
}
