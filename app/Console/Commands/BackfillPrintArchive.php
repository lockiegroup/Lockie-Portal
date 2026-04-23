<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Console\Command;

class BackfillPrintArchive extends Command
{
    protected $signature   = 'print:backfill-archive {--dry-run : Show what would be imported without saving}';
    protected $description = 'Import all historical Completed and Deleted A1 orders from Unleashed into the print archive';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        $service = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key')
        );

        // Find A1 Printing warehouse code
        $whData = $service->get('Warehouses', ['pageSize' => 200, 'pageNumber' => 1]);
        $a1Code = null;
        foreach ($whData['Items'] ?? [] as $wh) {
            if (str_contains(strtolower($wh['WarehouseName'] ?? ''), 'a1')) {
                $a1Code = $wh['WarehouseCode'];
                break;
            }
        }

        if (!$a1Code) {
            $this->error('A1 Printing warehouse not found in Unleashed.');
            return self::FAILURE;
        }

        $this->info("A1 warehouse code: {$a1Code}");
        if ($dry) {
            $this->warn('DRY RUN — nothing will be saved.');
        }

        $totalSaved   = 0;
        $totalSkipped = 0;
        $totalErrors  = 0;

        foreach (['Completed' => 'completed', 'Deleted' => 'deleted'] as $unleashedStatus => $reason) {
            $this->newLine();
            $this->info("Fetching {$unleashedStatus} orders from Unleashed…");

            try {
                $orders = $service->paginate('SalesOrders', [
                    'warehouseCode' => $a1Code,
                    'orderStatus'   => $unleashedStatus,
                ], 500);
            } catch (\Throwable $e) {
                $this->error("Failed to fetch {$unleashedStatus} orders: " . $e->getMessage());
                $totalErrors++;
                continue;
            }

            $this->info(count($orders) . " {$unleashedStatus} orders found.");

            if (empty($orders)) {
                continue;
            }

            $bar = $this->output->createProgressBar(count($orders));
            $bar->start();

            foreach ($orders as $order) {
                $guid = $order['Guid'] ?? null;
                if (!$guid) {
                    $bar->advance();
                    continue;
                }

                $orderNumber  = $order['OrderNumber'] ?? '';
                $orderDate    = $service->parseDate($order['OrderDate'] ?? null);
                $requiredDate = $service->parseDate($order['RequiredDate'] ?? null);
                $customerName = $order['Customer']['CustomerName'] ?? '';
                $customerRef  = trim($order['CustomerRef'] ?? $order['CustomerOrderNo'] ?? '');
                $despatchedAt = $unleashedStatus === 'Completed'
                    ? $service->parseDate($order['CompletedDate'] ?? null)
                    : null;
                $archivedAt   = $despatchedAt ?? now()->toDateString();

                // Get lines — the list endpoint may omit them; fall back to detail fetch
                $lines = $order['SalesOrderLines'] ?? [];
                if (empty($lines)) {
                    try {
                        $details = $service->fetchSalesOrderDetails([$guid]);
                        $lines   = ($details[$guid] ?? [])['SalesOrderLines'] ?? [];
                    } catch (\Throwable $e) {
                        $this->newLine();
                        $this->warn("  Could not fetch lines for {$orderNumber}: " . $e->getMessage());
                        $totalErrors++;
                        $bar->advance();
                        continue;
                    }
                }

                foreach ($lines as $lineIndex => $line) {
                    $productCode = $line['Product']['ProductCode'] ?? null;
                    if (empty($productCode)) continue;
                    if (str_contains(strtolower($productCode), 'a1-carriage')) continue;

                    $lineNumber = (int) ($line['LineNumber'] ?? ($lineIndex + 1));

                    // Skip if already in the archive for this guid+line
                    $exists = PrintJob::where('unleashed_guid', $guid)
                        ->where('line_number', $lineNumber)
                        ->whereNotNull('archived_at')
                        ->exists();

                    if ($exists) {
                        $totalSkipped++;
                        continue;
                    }

                    if (!$dry) {
                        PrintJob::create([
                            'unleashed_guid'         => $guid,
                            'line_number'            => $lineNumber,
                            'order_number'           => $orderNumber,
                            'order_date'             => $orderDate,
                            'customer_name'          => $customerName,
                            'customer_ref'           => $customerRef ?: null,
                            'product_code'           => $productCode,
                            'product_description'    => $line['Product']['ProductDescription'] ?? null,
                            'line_comment'           => $line['Comments'] ?? $line['LineComment'] ?? null,
                            'order_quantity'         => (int) ($line['OrderQuantity'] ?? 0),
                            'quantity_completed'     => 0,
                            'required_date'          => $requiredDate,
                            'original_required_date' => $requiredDate,
                            'board'                  => 'unplanned',
                            'position'               => 0,
                            'unleashed_status'       => $unleashedStatus,
                            'synced_at'              => now(),
                            'archived_at'            => $archivedAt,
                            'archive_reason'         => $reason,
                            'despatched_at'          => $despatchedAt,
                        ]);
                    }

                    $totalSaved++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $action = $dry ? 'Would import' : 'Imported';
        $this->info("{$action}: {$totalSaved} | Already existed (skipped): {$totalSkipped} | Errors: {$totalErrors}");

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
