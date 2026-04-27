<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Console\Command;

class FixArchiveLabels extends Command
{
    protected $signature   = 'print:fix-archive-labels';
    protected $description = 'Fix archived assembly jobs that are missing a deletion/completion label';

    public function handle(): int
    {
        $jobs = PrintJob::whereNotNull('archived_at')
            ->whereNull('archive_reason')
            ->where('order_number', 'like', 'ASM-%')
            ->where('is_manual', false)
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No unlabelled archived assemblies found.');
            return 0;
        }

        $this->info("Found {$jobs->count()} unlabelled archived assembly job(s).");

        if (!$this->confirm('Look up each one in Unleashed and set the correct label?')) {
            return 0;
        }

        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        $fixed = 0;
        foreach ($jobs as $job) {
            $assembly = $unleashed->fetchAssemblyByGuid($job->unleashed_guid);
            $status   = $assembly !== null ? strtolower($assembly['AssemblyStatus'] ?? '') : 'deleted';
            $reason   = $status === 'completed' ? 'completed' : 'deleted';
            $job->update(['archive_reason' => $reason]);
            $this->line("  {$job->order_number} ({$job->unleashed_guid}) → {$reason}");
            $fixed++;
        }

        $this->info("Done. {$fixed} job(s) updated.");
        return 0;
    }
}
