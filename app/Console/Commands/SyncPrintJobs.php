<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Console\Command;

class SyncPrintJobs extends Command
{
    protected $signature   = 'print:sync';
    protected $description = 'Sync open A1 printing Sales Orders from Unleashed into the print schedule';

    public function handle(UnleashedService $unleashed): int
    {
        $orders  = $unleashed->fetchA1PrintingOrders();
        $seenKeys = [];
        $created  = 0;
        $updated  = 0;

        foreach ($orders as $order) {
            $guid         = $order['Guid'] ?? null;
            $orderNumber  = $order['OrderNumber'] ?? '';
            $customerName = $order['Customer']['CustomerName'] ?? '';
            $orderTotal   = $order['Total'] ?? $order['SubTotal'] ?? 0;
            $orderStatus  = $order['OrderStatus'] ?? 'Open';
            $requiredDate = $unleashed->parseDate($order['RequiredDate'] ?? null);
            $lines        = $order['SalesOrderLines'] ?? [];

            if (!$guid) {
                continue;
            }

            foreach ($lines as $lineIndex => $line) {
                $productCode = $line['Product']['ProductCode'] ?? null;

                // Skip comment/service lines with no product code
                if (empty($productCode)) {
                    continue;
                }

                $lineNumber         = (int) ($line['LineNumber'] ?? ($lineIndex + 1));
                $productDescription = $line['Product']['ProductDescription'] ?? null;
                $lineComment        = $line['LineComment'] ?? null;
                $lineTotal          = (float) ($line['LineTotal'] ?? 0);
                $orderQuantity      = (int) ($line['OrderQuantity'] ?? 0);
                $key                = $guid . ':' . $lineNumber;
                $seenKeys[$key]     = true;

                $existing = PrintJob::where('unleashed_guid', $guid)
                    ->where('line_number', $lineNumber)
                    ->first();

                if ($existing) {
                    // Update only Unleashed-owned fields; preserve board, position, quantity_completed, required_date
                    $existing->update([
                        'order_number'       => $orderNumber,
                        'customer_name'      => $customerName,
                        'product_code'       => $productCode,
                        'product_description'=> $productDescription,
                        'line_comment'       => $lineComment,
                        'order_total'        => $orderTotal,
                        'line_total'         => $lineTotal,
                        'order_quantity'     => $orderQuantity,
                        'unleashed_status'   => $orderStatus,
                        'synced_at'          => now(),
                    ]);
                    $updated++;
                } else {
                    // Get max position for unplanned board
                    $maxPosition = PrintJob::where('board', 'unplanned')->max('position') ?? 0;

                    PrintJob::create([
                        'unleashed_guid'       => $guid,
                        'line_number'          => $lineNumber,
                        'order_number'         => $orderNumber,
                        'customer_name'        => $customerName,
                        'product_code'         => $productCode,
                        'product_description'  => $productDescription,
                        'line_comment'         => $lineComment,
                        'order_total'          => $orderTotal,
                        'line_total'           => $lineTotal,
                        'order_quantity'       => $orderQuantity,
                        'quantity_completed'   => 0,
                        'required_date'        => $requiredDate,
                        'original_required_date' => $requiredDate,
                        'board'                => 'unplanned',
                        'position'             => $maxPosition + 9999,
                        'unleashed_status'     => $orderStatus,
                        'synced_at'            => now(),
                    ]);
                    $created++;
                }
            }
        }

        // Remove jobs that were not seen in this sync (completed/deleted in Unleashed)
        if (!empty($seenKeys)) {
            $allJobs = PrintJob::all();
            $deleted = 0;
            foreach ($allJobs as $job) {
                $key = $job->unleashed_guid . ':' . $job->line_number;
                if (!isset($seenKeys[$key])) {
                    $job->delete();
                    $deleted++;
                }
            }
            $this->info("Sync complete: {$created} created, {$updated} updated, {$deleted} removed.");
        } else {
            $this->info("Sync complete: {$created} created, {$updated} updated. (No A1 orders found — no deletions performed.)");
        }

        return self::SUCCESS;
    }
}
