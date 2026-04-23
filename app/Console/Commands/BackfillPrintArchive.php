<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillPrintArchive extends Command
{
    protected $signature   = 'print:backfill-archive
                                {--dry-run : Show what would be imported without saving}
                                {--start=2015-01-01 : Earliest order date to fetch (YYYY-MM-DD)}';
    protected $description = 'Import all historical Completed and Deleted A1 orders from Unleashed into the print archive';

    public function handle(): int
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        $dry   = $this->option('dry-run');
        $start = Carbon::parse($this->option('start'))->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

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
        $this->info("Date range: {$start->format('Y-m-d')} → {$end->format('Y-m-d')} (month by month)");
        if ($dry) {
            $this->warn('DRY RUN — nothing will be saved.');
        }

        $totalSaved   = 0;
        $totalSkipped = 0;
        $totalErrors  = 0;
        $allSeenGuids = []; // global dedup across all months

        $current = $start->copy();

        while ($current->lte($end)) {
            $monthStart = $current->format('Y-m-d');
            $monthEnd   = $current->copy()->endOfMonth()->format('Y-m-d');

            $this->newLine();
            $this->line("<info>{$current->format('M Y')}</info> ({$monthStart} → {$monthEnd})");

            $page      = 1;
            $pageSize  = 200;
            $maxPages  = 1;
            $monthSeen = [];

            do {
                $data    = null;
                $attempt = 0;
                while ($attempt < 3) {
                    try {
                        $data = $service->get('SalesOrders', [
                            'warehouseCode' => $a1Code,
                            'startDate'     => $monthStart,
                            'endDate'       => $monthEnd,
                            'pageSize'      => $pageSize,
                            'pageNumber'    => $page,
                        ], 90);
                        break;
                    } catch (\Throwable $e) {
                        $attempt++;
                        if ($attempt >= 3) {
                            $this->error("  API error on page {$page}: " . $e->getMessage());
                            $totalErrors++;
                            break 2;
                        }
                        $wait = $attempt * 10;
                        $this->warn("  Timeout, retrying in {$wait}s…");
                        sleep($wait);
                    }
                }

                if ($data === null) break;

                $maxPages = $data['Pagination']['NumberOfPages'] ?? 1;
                $orders   = $data['Items'] ?? [];

                $newOnPage = 0;
                foreach ($orders as $o) {
                    $g = $o['Guid'] ?? '';
                    if ($g && !isset($monthSeen[$g]) && !isset($allSeenGuids[$g])) {
                        $newOnPage++;
                    }
                }

                if ($newOnPage === 0 && $page > 1) {
                    $this->line("  Page {$page}/{$maxPages} — repeated, stopping month.");
                    break;
                }

                $this->line("  Page {$page}/{$maxPages} — " . count($orders) . " orders, {$newOnPage} new");

                foreach ($orders as $order) {
                    $guid   = $order['Guid'] ?? null;
                    $status = $order['OrderStatus'] ?? '';

                    if (!$guid) continue;
                    if (isset($monthSeen[$guid]) || isset($allSeenGuids[$guid])) continue;
                    $monthSeen[$guid]    = true;
                    $allSeenGuids[$guid] = true;

                    if (!in_array($status, ['Completed', 'Deleted'], true)) continue;

                    $reason       = $status === 'Deleted' ? 'deleted' : 'completed';
                    $orderNumber  = $order['OrderNumber'] ?? '';
                    $orderDate    = $service->parseDate($order['OrderDate'] ?? null);
                    $requiredDate = $service->parseDate($order['RequiredDate'] ?? null);
                    $customerName = $order['Customer']['CustomerName'] ?? '';
                    $customerRef  = trim($order['CustomerRef'] ?? $order['CustomerOrderNo'] ?? '');
                    $despatchedAt = $status === 'Completed'
                        ? $service->parseDate($order['CompletedDate'] ?? null)
                        : null;
                    $archivedAt   = $despatchedAt ?? now()->toDateString();

                    $lines = $order['SalesOrderLines'] ?? [];
                    if (empty($lines)) {
                        try {
                            $details = $service->fetchSalesOrderDetails([$guid]);
                            $lines   = ($details[$guid] ?? [])['SalesOrderLines'] ?? [];
                        } catch (\Throwable $e) {
                            $this->warn("  Could not fetch lines for {$orderNumber}: " . $e->getMessage());
                            $totalErrors++;
                            continue;
                        }
                    }

                    foreach ($lines as $lineIndex => $line) {
                        $productCode = $line['Product']['ProductCode'] ?? null;
                        if (empty($productCode)) continue;
                        if (str_contains(strtolower($productCode), 'a1-carriage')) continue;

                        $lineNumber = (int) ($line['LineNumber'] ?? ($lineIndex + 1));

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
                                'unleashed_status'       => $status,
                                'synced_at'              => now(),
                                'archived_at'            => $archivedAt,
                                'archive_reason'         => $reason,
                                'despatched_at'          => $despatchedAt,
                            ]);
                        }

                        $totalSaved++;
                    }

                    unset($order, $lines);
                }

                unset($orders, $data);
                gc_collect_cycles();

                $page++;

            } while ($page <= $maxPages);

            $current->addMonth();
        }

        $this->newLine();
        $action = $dry ? 'Would import' : 'Imported';
        $this->info("{$action}: {$totalSaved} | Already existed (skipped): {$totalSkipped} | Errors: {$totalErrors}");
        $this->info("Total unique orders seen across all months: " . count($allSeenGuids));

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
