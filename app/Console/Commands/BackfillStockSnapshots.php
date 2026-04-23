<?php

namespace App\Console\Commands;

use App\Models\StockSnapshot;
use App\Services\UnleashedService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillStockSnapshots extends Command
{
    protected $signature   = 'stock:backfill {--months=12 : How many months back to fill}';
    protected $description = 'Fetch first-of-month stock values from Unleashed and store as snapshots';

    public function handle(): int
    {
        $months  = (int) $this->option('months');
        $service = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key')
        );

        $this->info("Backfilling {$months} months of stock snapshots…");
        $bar = $this->output->createProgressBar($months);
        $bar->start();

        $skipped = 0;
        $saved   = 0;
        $errors  = 0;

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->startOfMonth()->subMonths($i)->toDateString();

            // Skip if we already have this date
            if (StockSnapshot::where('snapshot_date', $date)->exists()) {
                $bar->advance();
                $skipped++;
                continue;
            }

            try {
                $stockByWarehouse = $service->fetchStockByWarehouse($date);
                $total = collect($stockByWarehouse)->sum('totalCost');

                StockSnapshot::create([
                    'snapshot_date'  => $date,
                    'total_value'    => $total,
                    'warehouse_data' => $stockByWarehouse,
                ]);

                $saved++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("  Failed for {$date}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();

            // Brief pause to avoid hammering the API
            if ($i > 0) usleep(300_000);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Saved: {$saved} | Skipped (already existed): {$skipped} | Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
