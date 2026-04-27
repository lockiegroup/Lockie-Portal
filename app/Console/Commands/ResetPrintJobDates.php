<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetPrintJobDates extends Command
{
    protected $signature   = 'print:reset-dates';
    protected $description = 'Reset all required_date back to original_required_date';

    public function handle(): void
    {
        $count = DB::table('print_jobs')
            ->whereRaw('required_date != original_required_date OR (required_date IS NOT NULL AND original_required_date IS NULL)')
            ->count();

        if ($count === 0) {
            $this->info('No dates to reset.');
            return;
        }

        if (!$this->confirm("Reset {$count} job(s) back to their original delivery dates?")) {
            $this->info('Cancelled.');
            return;
        }

        DB::table('print_jobs')->update(['required_date' => DB::raw('original_required_date')]);
        $this->info("Reset {$count} job(s).");
    }
}
