<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Console\Command;

class RecoverMissingAssemblies extends Command
{
    protected $signature   = 'print:recover-assemblies
                                {--dry-run : Show what would be imported without saving}';
    protected $description = 'Re-import completed/deleted assemblies from Unleashed that were hard-deleted instead of archived';

    public function handle(): int
    {
        set_time_limit(0);

        $dry = $this->option('dry-run');

        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        if ($dry) {
            $this->warn('DRY RUN — nothing will be saved.');
        }

        $this->info('Fetching all assemblies from Unleashed (this may take a moment)…');

        $assemblies = $unleashed->fetchAllAssemblies();

        $this->info('Total assemblies returned: ' . count($assemblies));

        $recovered = 0;
        $skipped   = 0;

        foreach ($assemblies as $assembly) {
            $guid   = $assembly['Guid'] ?? null;
            $status = strtolower($assembly['AssemblyStatus'] ?? '');

            if (!$guid) continue;
            if (!in_array($status, ['completed', 'deleted'], true)) continue;

            $exists = PrintJob::where('unleashed_guid', $guid)->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $assemblyNumber     = $assembly['AssemblyNumber'] ?? '';
            $productCode        = $assembly['Product']['ProductCode'] ?? null;
            if (!$productCode) continue;

            $productDescription = $assembly['Product']['ProductDescription'] ?? null;
            $assembledQty       = (int) ($assembly['Quantity'] ?? 0);
            $assemblyDate       = $unleashed->parseDate($assembly['AssemblyDate'] ?? null);
            $soNumber           = $assembly['SalesOrderNumber'] ?? null;
            $reason             = $status === 'completed' ? 'completed' : 'deleted';

            $this->line("  Recovering {$assemblyNumber} ({$guid}) → {$reason}");

            if (!$dry) {
                PrintJob::create([
                    'unleashed_guid'         => $guid,
                    'line_number'            => 1,
                    'order_number'           => $assemblyNumber,
                    'order_date'             => $assemblyDate,
                    'customer_name'          => $soNumber ?? '',
                    'customer_ref'           => null,
                    'product_code'           => $productCode,
                    'product_description'    => $productDescription,
                    'line_comment'           => $assembly['Comments'] ?? null,
                    'order_total'            => 0,
                    'line_total'             => 0,
                    'order_quantity'         => $assembledQty,
                    'quantity_completed'     => 0,
                    'required_date'          => null,
                    'original_required_date' => null,
                    'board'                  => 'unplanned',
                    'position'               => 0,
                    'unleashed_status'       => $assembly['AssemblyStatus'] ?? $status,
                    'synced_at'              => now(),
                    'is_manual'              => false,
                    'archived_at'            => now(),
                    'archive_reason'         => $reason,
                    'despatched_at'          => null,
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
