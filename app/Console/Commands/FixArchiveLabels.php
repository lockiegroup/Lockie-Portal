<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Console\Command;

class FixArchiveLabels extends Command
{
    protected $signature   = 'print:fix-archive-labels
                                {--include-completed : Also check assemblies labelled completed — relabels to deleted if absent from Unleashed paginated list}';
    protected $description = 'Fix archived assembly jobs that are missing or have an incorrect deletion/completion label';

    public function handle(): int
    {
        $includeCompleted = $this->option('include-completed');

        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        if ($includeCompleted) {
            return $this->fixCompletedViaPagedList($unleashed);
        }

        return $this->fixUnlabelled($unleashed);
    }

    private function fixUnlabelled(UnleashedService $unleashed): int
    {
        $jobs = PrintJob::whereNotNull('archived_at')
            ->whereNull('archive_reason')
            ->where('order_number', 'like', 'ASM-%')
            ->where('is_manual', false)
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No unlabelled archived assemblies found.');
            return self::SUCCESS;
        }

        $this->info("Found {$jobs->count()} unlabelled archived assembly job(s).");

        $fixed = 0;
        foreach ($jobs as $job) {
            $assembly  = $unleashed->fetchAssemblyByGuid($job->unleashed_guid);
            $status    = $assembly !== null ? strtolower($assembly['AssemblyStatus'] ?? '') : 'deleted';
            $newReason = $status === 'completed' ? 'completed' : 'deleted';
            $job->update(['archive_reason' => $newReason]);
            $this->line("  {$job->order_number} ({$job->unleashed_guid}) → {$newReason}");
            $fixed++;
        }

        $this->info("Done. {$fixed} job(s) updated.");
        return self::SUCCESS;
    }

    private function fixCompletedViaPagedList(UnleashedService $unleashed): int
    {
        $jobs = PrintJob::whereNotNull('archived_at')
            ->where('archive_reason', 'completed')
            ->where('order_number', 'like', 'ASM-%')
            ->where('is_manual', false)
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No completed-labelled archived assemblies found.');
            return self::SUCCESS;
        }

        $this->info("Found {$jobs->count()} completed-labelled archived assembly job(s).");
        $this->info('Fetching full assembly list from Unleashed...');

        // Fetch ALL assemblies — completed ones appear here, deleted ones don't
        $all            = $unleashed->paginate('Assemblies', [], 200);
        $unleashedGuids = array_flip(array_filter(array_column($all, 'Guid')));

        $fixed = 0;
        foreach ($jobs as $job) {
            // If the GUID is absent from the full Unleashed list → it was deleted
            if (!isset($unleashedGuids[$job->unleashed_guid])) {
                $job->update(['archive_reason' => 'deleted']);
                $this->line("  {$job->order_number} ({$job->unleashed_guid}) → deleted");
                $fixed++;
            }
        }

        $this->info("Done. {$fixed} job(s) relabelled as deleted.");
        return self::SUCCESS;
    }
}
