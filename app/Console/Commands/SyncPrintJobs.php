<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\PrintJob;
use App\Services\UnleashedService;
use Illuminate\Console\Command;

class SyncPrintJobs extends Command
{
    protected $signature   = 'print:sync';
    protected $description = 'Sync open A1 printing Sales Orders from Unleashed into the print schedule';

    public function handle(): int
    {
        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key')
        );

        $orders   = $unleashed->fetchA1PrintingOrders();
        $seenKeys = [];
        $created  = 0;
        $updated  = 0;

        foreach ($orders as $order) {
            $guid         = $order['Guid'] ?? null;
            if (!$guid) continue;

            $orderNumber  = $order['OrderNumber'] ?? '';
            $orderDate    = $unleashed->parseDate($order['OrderDate'] ?? null);
            $customerName = $order['Customer']['CustomerName'] ?? '';
            $customerRef  = trim($order['CustomerRef'] ?? $order['CustomerOrderNo'] ?? '');
            $orderTotal   = (float) ($order['SubTotal'] ?? 0);
            $orderStatus  = $order['OrderStatus'] ?? 'Open';
            $requiredDate = $unleashed->parseDate($order['RequiredDate'] ?? null);

            if (in_array($orderStatus, ['Completed', 'Deleted'], true)) {
                $completedDate = $unleashed->parseDate($order['CompletedDate'] ?? null);
                foreach ($order['SalesOrderLines'] ?? [] as $lineIndex => $line) {
                    $productCode = $line['Product']['ProductCode'] ?? null;
                    if (empty($productCode)) continue;
                    if (str_contains(strtolower($productCode), 'a1-carriage')) continue;
                    if (str_starts_with(strtoupper($productCode), 'H-')) continue;
                    $lineNumber = (int) ($line['LineNumber'] ?? ($lineIndex + 1));
                    PrintJob::active()
                        ->where('unleashed_guid', $guid)
                        ->where('line_number', $lineNumber)
                        ->update([
                            'archived_at'    => now(),
                            'archive_reason' => $orderStatus === 'Deleted' ? 'deleted' : 'completed',
                            'despatched_at'  => $orderStatus === 'Completed' ? $completedDate : null,
                        ]);
                }
                continue;
            }

            foreach ($order['SalesOrderLines'] ?? [] as $lineIndex => $line) {
                $productCode = $line['Product']['ProductCode'] ?? null;
                if (empty($productCode)) continue;
                if (str_contains(strtolower($productCode), 'a1-carriage')) continue;
                if (str_starts_with(strtoupper($productCode), 'H-')) continue;

                $lineNumber     = (int) ($line['LineNumber'] ?? ($lineIndex + 1));
                $key            = $guid . ':' . $lineNumber;
                $seenKeys[$key] = true;

                $existing = PrintJob::active()->where('unleashed_guid', $guid)->where('line_number', $lineNumber)->first();

                if (!$existing) {
                    $swept = PrintJob::where('unleashed_guid', $guid)
                        ->where('line_number', $lineNumber)
                        ->whereNotNull('archived_at')
                        ->whereNull('archive_reason')
                        ->first();
                    if ($swept) {
                        $swept->update(['archived_at' => null]);
                        $existing = $swept->fresh();
                    }
                }

                if ($existing) {
                    $update = [
                        'order_number'        => $orderNumber,
                        'order_date'          => $orderDate,
                        'customer_name'       => $customerName,
                        'customer_ref'        => $customerRef ?: null,
                        'product_code'        => $productCode,
                        'product_description' => $line['Product']['ProductDescription'] ?? null,
                        'line_comment'        => $line['Comments'] ?? $line['LineComment'] ?? null,
                        'order_total'         => $orderTotal,
                        'line_total'          => (float) ($line['LineTotal'] ?? 0),
                        'order_quantity'      => (int) ($line['OrderQuantity'] ?? 0),
                        'unleashed_status'    => $orderStatus,
                        'synced_at'           => now(),
                    ];
                    if (!$existing->date_changed && $requiredDate) {
                        $update['required_date']          = $requiredDate;
                        $update['original_required_date'] = $requiredDate;
                    }
                    $existing->update($update);
                    $updated++;
                } else {
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
                        'order_total'            => $orderTotal,
                        'line_total'             => (float) ($line['LineTotal'] ?? 0),
                        'order_quantity'         => (int) ($line['OrderQuantity'] ?? 0),
                        'quantity_completed'     => 0,
                        'required_date'          => $requiredDate,
                        'original_required_date' => $requiredDate,
                        'board'                  => 'unplanned',
                        'position'               => PrintJob::where('board', 'unplanned')->max('position') + 1,
                        'unleashed_status'       => $orderStatus,
                        'synced_at'              => now(),
                    ]);
                    $created++;
                }
            }
        }

        if (!empty($seenKeys)) {
            PrintJob::active()->where('is_manual', false)->get()->each(function ($job) use ($seenKeys) {
                if (!isset($seenKeys[$job->unleashed_guid . ':' . $job->line_number])) {
                    $job->update(['archived_at' => now()]);
                }
            });
        }

        ActivityLog::record('print.sync', "Scheduled sync: {$created} created, {$updated} updated");
        $this->info("Synced: {$created} created, {$updated} updated.");

        return self::SUCCESS;
    }
}
