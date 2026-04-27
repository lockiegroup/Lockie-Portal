<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\PrintJob;

class PrintScheduleSyncService
{
    public function run(): array
    {
        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        $created  = 0;
        $updated  = 0;
        $warnings = [];

        $this->syncSalesOrders($unleashed, $created, $updated);

        try {
            $this->syncAssemblies($unleashed, $created, $updated);
        } catch (\RuntimeException $e) {
            $warnings[] = 'Assemblies skipped: ' . $e->getMessage();
        }

        return ['created' => $created, 'updated' => $updated, 'warnings' => $warnings];
    }

    private function syncSalesOrders(UnleashedService $unleashed, int &$created, int &$updated): void
    {
        $orders   = $unleashed->fetchA1PrintingOrders();
        $seenKeys = [];

        foreach ($orders as $order) {
            $guid = $order['Guid'] ?? null;
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
            PrintJob::active()
                ->where('is_manual', false)
                ->where('order_number', 'not like', 'ASM-%')
                ->get()
                ->each(function ($job) use ($seenKeys) {
                    if (!isset($seenKeys[$job->unleashed_guid . ':' . $job->line_number])) {
                        $job->update(['archived_at' => now()]);
                    }
                });
        }
    }

    private function syncAssemblies(UnleashedService $unleashed, int &$created, int &$updated): void
    {
        $assemblies = $unleashed->fetchAssemblies();
        $seenKeys   = [];

        // Batch-fetch SO totals for all linked sales orders in one parallel round-trip
        $soNumbers = array_values(array_unique(array_filter(
            array_column(array_map(fn($a) => ['SalesOrderNumber' => $a['SalesOrderNumber'] ?? null], $assemblies), 'SalesOrderNumber')
        )));
        $soData = !empty($soNumbers) ? $unleashed->fetchSalesOrderData($soNumbers) : [];

        foreach ($assemblies as $assembly) {
            $guid = $assembly['Guid'] ?? null;
            if (!$guid) continue;

            $assemblyNumber = $assembly['AssemblyNumber'] ?? '';
            $assemblyStatus = $assembly['AssemblyStatus'] ?? 'Open';
            $productCode    = $assembly['Product']['ProductCode'] ?? null;
            if (!$productCode) continue;

            $existing = PrintJob::active()
                ->where('unleashed_guid', $guid)
                ->where('line_number', 1)
                ->first();

            // Deleted → hard delete and move on
            if (strtolower($assemblyStatus) === 'deleted') {
                $existing?->delete();
                continue;
            }

            // Completed → archive and move on
            if (strtolower($assemblyStatus) === 'completed') {
                $existing?->update(['archived_at' => now(), 'archive_reason' => 'completed']);
                continue;
            }

            $productDescription = $assembly['Product']['ProductDescription'] ?? null;
            $assembledQty       = (int) ($assembly['Quantity'] ?? 0);
            $assembleBy         = $unleashed->parseDate($assembly['AssembleBy'] ?? null);
            $assemblyDate       = $unleashed->parseDate($assembly['AssemblyDate'] ?? null);
            $comments           = $assembly['Comments'] ?? null;
            $soNumber           = $assembly['SalesOrderNumber'] ?? null;
            $soTotal            = 0.0;
            $soRequiredDate     = null;
            if ($soNumber && isset($soData[$soNumber])) {
                foreach ($soData[$soNumber]['lines'] as $line) {
                    if (($line['Product']['ProductCode'] ?? null) === $productCode) {
                        $soTotal = (float) ($line['LineTotal'] ?? 0);
                        break;
                    }
                }
                $soRequiredDate = $unleashed->parseDate($soData[$soNumber]['requiredDate'] ?? null);
            }

            $requiredDate   = $soRequiredDate ?? $assembleBy;
            $seenKeys[$guid . ':1'] = true;

            if ($existing) {
                $update = [
                    'order_number'        => $assemblyNumber,
                    'order_date'          => $assemblyDate,
                    'customer_name'       => $soNumber ?? '',
                    'product_code'        => $productCode,
                    'product_description' => $productDescription,
                    'line_comment'        => $comments,
                    'order_quantity'      => $assembledQty,
                    'order_total'         => $soTotal,
                    'unleashed_status'    => $assemblyStatus,
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
                    'line_number'            => 1,
                    'order_number'           => $assemblyNumber,
                    'order_date'             => $assemblyDate,
                    'customer_name'          => $soNumber ?? '',
                    'customer_ref'           => null,
                    'product_code'           => $productCode,
                    'product_description'    => $productDescription,
                    'line_comment'           => $comments,
                    'order_total'            => $soTotal,
                    'line_total'             => 0,
                    'order_quantity'         => $assembledQty,
                    'quantity_completed'     => 0,
                    'required_date'          => $requiredDate,
                    'original_required_date' => $requiredDate,
                    'board'                  => 'unplanned',
                    'position'               => PrintJob::where('board', 'unplanned')->max('position') + 1,
                    'unleashed_status'       => $assemblyStatus,
                    'synced_at'              => now(),
                ]);
                $created++;
            }
        }
    }
}
