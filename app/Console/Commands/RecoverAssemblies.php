<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Console\Command;

class RecoverAssemblies extends Command
{
    protected $signature   = 'print:recover-assemblies
                                {--dry-run : Show what would be recovered without saving}';
    protected $description = 'Backfill completed assembly jobs from Unleashed into the print archive';

    public function handle(): int
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        $dry = $this->option('dry-run');

        if ($dry) {
            $this->warn('DRY RUN — nothing will be saved.');
        }

        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        $this->info('Fetching all assemblies from Unleashed...');

        $all = $unleashed->paginate('Assemblies', [], 200);

        $recovered = 0;
        $skipped   = 0;

        foreach ($all as $assembly) {
            $guid   = $assembly['Guid'] ?? null;
            $status = strtolower($assembly['AssemblyStatus'] ?? '');

            if (!$guid) continue;
            if ($status !== 'completed') continue;

            $assemblyNumber = $assembly['AssemblyNumber'] ?? '';
            $productCode    = $assembly['Product']['ProductCode'] ?? null;
            if (!$productCode) continue;
            if (str_starts_with(strtoupper($productCode), 'H-')) continue;

            $exists = PrintJob::where('unleashed_guid', $guid)
                ->where('line_number', 1)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $this->line("Recovering {$assemblyNumber} ({$guid}) → {$status}");

            if (!$dry) {
                $assemblyDate = $unleashed->parseDate($assembly['AssemblyDate'] ?? null);
                $assembleBy   = $unleashed->parseDate($assembly['AssembleBy'] ?? null);
                $soNumber     = $assembly['SalesOrderNumber'] ?? null;

                PrintJob::create([
                    'unleashed_guid'         => $guid,
                    'line_number'            => 1,
                    'order_number'           => $assemblyNumber,
                    'order_date'             => $assemblyDate,
                    'customer_name'          => $soNumber ?? '',
                    'customer_ref'           => null,
                    'product_code'           => $productCode,
                    'product_description'    => $assembly['Product']['ProductDescription'] ?? null,
                    'line_comment'           => $assembly['Comments'] ?? null,
                    'order_total'            => 0,
                    'line_total'             => 0,
                    'order_quantity'         => (int) ($assembly['Quantity'] ?? 0),
                    'quantity_completed'     => 0,
                    'required_date'          => $assembleBy,
                    'original_required_date' => $assembleBy,
                    'board'                  => 'unplanned',
                    'position'               => 0,
                    'unleashed_status'       => 'Completed',
                    'synced_at'              => now(),
                    'archived_at'            => now(),
                    'archive_reason'         => 'completed',
                ]);
            }

            $recovered++;
        }

        $this->newLine();
        $action = $dry ? 'Would recover' : 'Recovered';
        $this->info("{$action}: {$recovered} assembly job(s). Already in DB (skipped): {$skipped}.");

        return self::SUCCESS;
    }
}
