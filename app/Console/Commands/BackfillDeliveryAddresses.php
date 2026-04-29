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

        // Only SO jobs (not ASM-) that are missing delivery data
        $jobs = PrintJob::whereNotNull('archived_at')
            ->whereNull('delivery_name')
            ->where('order_number', 'not like', 'ASM-%')
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No jobs to backfill.');
            return;
        }

        $orderNumbers = $jobs->pluck('order_number')->unique()->values()->all();
        $this->info('Fetching ' . count($orderNumbers) . ' unique orders from Unleashed…');

        $updated = 0;

        foreach (array_chunk($orderNumbers, 50) as $batch) {
            $soData = $unleashed->fetchSalesOrderData($batch);

            foreach ($batch as $orderNumber) {
                $sd = $soData[$orderNumber] ?? null;
                if (!$sd) continue;

                $addrParts = array_filter([
                    $sd['deliveryName']    ?? null,
                    $sd['deliveryStreet1'] ?? null,
                    $sd['deliveryStreet2'] ?? null,
                    $sd['deliverySuburb']  ?? null,
                    $sd['deliveryCity']    ?? null,
                    $sd['deliveryRegion']  ?? null,
                    $sd['deliveryPostCode'] ?? null,
                    $sd['deliveryCountry'] ?? null,
                ]);

                PrintJob::whereNotNull('archived_at')
                    ->where('order_number', $orderNumber)
                    ->whereNull('delivery_name')
                    ->update([
                        'delivery_name'     => $sd['deliveryName']    ?? null,
                        'delivery_city'     => $sd['deliveryCity']    ?? null,
                        'delivery_postcode' => $sd['deliveryPostCode'] ?? null,
                        'delivery_address'  => $addrParts ? implode(', ', $addrParts) : null,
                    ]);

                $count = $jobs->where('order_number', $orderNumber)->count();
                $updated += $count;
                $this->line("  ✓ {$orderNumber} — " . ($sd['deliveryName'] ?? '?'));
            }
        }

        $this->info("Done. Updated {$updated} job(s).");
    }
}
