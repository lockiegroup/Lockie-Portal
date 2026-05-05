<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Console\Command;

class BackfillDeliveryAddresses extends Command
{
    protected $signature   = 'print:backfill-delivery';
    protected $description = 'Backfill delivery address fields for archived print jobs';

    public function handle(): void
    {
        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        // Jobs missing delivery data — SO jobs use order_number directly;
        // ASM jobs have the SO number embedded in line_comment
        $jobs = PrintJob::whereNotNull('archived_at')
            ->whereNull('delivery_name')
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No jobs to backfill.');
            return;
        }

        // Build a map of job_id => SO number to fetch
        $fetchMap = []; // soNumber => [job ids]
        $asmSkipped = 0;
        foreach ($jobs as $job) {
            if (str_starts_with($job->order_number, 'ASM-')) {
                // Extract SO number from line_comment e.g. "Created for Invoice SO-00026284."
                if (preg_match('/SO-\d+/i', $job->line_comment ?? '', $m)) {
                    $fetchMap[$m[0]][] = $job->id;
                } else {
                    $asmSkipped++;
                }
            } else {
                $fetchMap[$job->order_number][] = $job->id;
            }
        }
        if ($asmSkipped > 0) {
            $this->warn("{$asmSkipped} ASM job(s) skipped — no SO number found in line_comment.");
        }

        $orderNumbers = array_keys($fetchMap);
        $this->info('Fetching ' . count($orderNumbers) . ' unique orders from Unleashed…');

        $updated = 0;

        foreach (array_chunk($orderNumbers, 10) as $batch) {
            $soData = $unleashed->fetchSalesOrderData($batch);

            // Retry any that failed individually
            $failed = array_diff($batch, array_keys($soData));
            foreach ($failed as $num) {
                sleep(1);
                $retry = $unleashed->fetchSalesOrderData([$num]);
                if (isset($retry[$num])) $soData[$num] = $retry[$num];
            }

            foreach ($batch as $orderNumber) {
                $sd = $soData[$orderNumber] ?? null;
                if (!$sd) {
                    $this->line("  — {$orderNumber} not found in Unleashed");
                    continue;
                }

                $addrParts = array_filter([
                    $sd['deliveryName']     ?? null,
                    $sd['deliveryStreet1']  ?? null,
                    $sd['deliveryStreet2']  ?? null,
                    $sd['deliverySuburb']   ?? null,
                    $sd['deliveryCity']     ?? null,
                    $sd['deliveryRegion']   ?? null,
                    $sd['deliveryPostCode'] ?? null,
                    $sd['deliveryCountry']  ?? null,
                ]);

                $ids = $fetchMap[$orderNumber] ?? [];
                PrintJob::whereIn('id', $ids)->update([
                    'delivery_name'     => $sd['deliveryName']     ?? null,
                    'delivery_city'     => $sd['deliveryCity']     ?? null,
                    'delivery_postcode' => $sd['deliveryPostCode'] ?? null,
                    'delivery_address'  => $addrParts ? implode(', ', $addrParts) : null,
                ]);

                $updated += count($ids);
                $this->line('  ✓ ' . $orderNumber . ' — ' . ($sd['deliveryName'] ?? '(no name)'));
            }
        }

        $this->info("Done. Updated {$updated} job(s).");
    }
}
