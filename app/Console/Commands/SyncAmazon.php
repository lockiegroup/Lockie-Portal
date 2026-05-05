<?php

namespace App\Console\Commands;

use App\Models\AmazonSettlement;
use App\Services\AmazonService;
use App\Services\AmazonSyncService;
use App\Services\UnleashedService;
use App\Services\XeroService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncAmazon extends Command
{
    protected $signature = 'amazon:sync
                                {--settlements : Sync settlement reports only}
                                {--ads         : Sync advertising spend only}
                                {--xero        : Post pending settlements to Xero only}
                                {--all         : Run all sync operations (default)}
                                {--start=      : Start date for ad spend sync (YYYY-MM-DD, default 30 days ago)}
                                {--end=        : End date for ad spend sync (YYYY-MM-DD, default today)}';

    protected $description = 'Sync Amazon settlement reports and advertising data, optionally post to Xero';

    public function handle(): int
    {
        $runAll         = $this->option('all') || (!$this->option('settlements') && !$this->option('ads') && !$this->option('xero'));
        $runSettlements = $runAll || $this->option('settlements');
        $runAds         = $runAll || $this->option('ads');
        $runXero        = $runAll || $this->option('xero');

        $service = new AmazonSyncService(
            new AmazonService(),
            new XeroService(),
            new UnleashedService(
                config('services.unleashed.id'),
                config('services.unleashed.key')
            )
        );

        if ($runSettlements) {
            $this->info('Syncing Amazon settlement reports…');

            try {
                $result = $service->syncSettlements();
                $this->info("  Imported: {$result['imported']} | Skipped: {$result['skipped']} | Errors: {$result['errors']}");

                if ($result['errors'] > 0) {
                    $this->warn("  {$result['errors']} report(s) failed — check logs for details.");
                }
            } catch (\Throwable $e) {
                $this->error('Settlement sync failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        if ($runAds) {
            $start = $this->option('start') ?? Carbon::now()->subDays(30)->toDateString();
            $end   = $this->option('end')   ?? Carbon::now()->toDateString();

            $this->info("Syncing Amazon ad spend ({$start} → {$end})…");

            try {
                $result = $service->syncAdSpend($start, $end);
                $this->info("  Imported {$result['imported']} campaign row(s) into settlement {$result['settlement_id']}.");
            } catch (\Throwable $e) {
                $this->error('Ad spend sync failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        if ($runXero) {
            $pending = AmazonSettlement::where('status', 'pending')->get();
            $this->info("Posting {$pending->count()} pending settlement(s) to Xero…");

            foreach ($pending as $settlement) {
                try {
                    $service->postToXero($settlement);
                    $this->line("  Posted settlement {$settlement->settlement_id}.");
                } catch (\Throwable $e) {
                    $this->warn("  Failed {$settlement->settlement_id}: " . $e->getMessage());
                }
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
