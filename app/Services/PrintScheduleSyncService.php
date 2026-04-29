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
        $orders = $unleashed->fetchA1PrintingOrders();

        // Pre-fetch all active SO jobs into memory — avoids one DB query per order line
        $activeJobs  = PrintJob::active()
            ->where('is_manual', false)
            ->where('order_number', 'not like', 'ASM-%')
            ->whereNotNull('unleashed_guid')
            ->get()
            ->keyBy(fn($j) => $j->unleashed_guid . ':' . $j->line_number);

        $maxPosition = PrintJob::where('board', 'unplanned')->max('position') ?? 0;

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

            $deliveryName     = trim((string)($order['DeliveryName'] ?? '')) ?: null;
            $deliveryCity     = trim((string)($order['DeliveryCity'] ?? '')) ?: null;
            $deliveryPostcode = trim((string)($order['DeliveryPostCode'] ?? '')) ?: null;
            $addrParts = array_filter([
                $deliveryName,
                trim((string)($order['DeliveryStreetAddress']  ?? '')),
                trim((string)($order['DeliveryStreetAddress2'] ?? '')),
                trim((string)($order['DeliverySuburb']         ?? '')),
                $deliveryCity,
                trim((string)($order['DeliveryRegion']         ?? '')),
                $deliveryPostcode,
                is_array($order['DeliveryCountry'] ?? null)
                    ? trim((string)($order['DeliveryCountry']['Name'] ?? ''))
                    : trim((string)($order['DeliveryCountry'] ?? '')),
            ]);
            $deliveryAddress = $addrParts ? implode(', ', $addrParts) : null;

            if (in_array($orderStatus, ['Completed', 'Deleted'], true)) {
                $completedDate = $unleashed->parseDate($order['CompletedDate'] ?? null);
                foreach ($order['SalesOrderLines'] ?? [] as $lineIndex => $line) {
                    $productCode = $line['Product']['ProductCode'] ?? null;
                    if (empty($productCode)) continue;
                    if (str_contains(strtolower($productCode), 'carriage')) continue;
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
                if (str_contains(strtolower($productCode), 'carriage')) continue;
                if (str_starts_with(strtoupper($productCode), 'H-')) continue;

                $lineNumber = (int) ($line['LineNumber'] ?? ($lineIndex + 1));
                $existing   = $activeJobs->get($guid . ':' . $lineNumber);

                if (!$existing) {
                    $swept = PrintJob::where('unleashed_guid', $guid)
                        ->where('line_number', $lineNumber)
                        ->whereNotNull('archived_at')
                        ->whereNull('archive_reason')
                        ->first();
                    if ($swept) {
                        $swept->update(['archived_at' => null]);
                        $existing = $swept->fresh();
                        $activeJobs->put($guid . ':' . $lineNumber, $existing);
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
                        'delivery_name'       => $deliveryName,
                        'delivery_city'       => $deliveryCity,
                        'delivery_postcode'   => $deliveryPostcode,
                        'delivery_address'    => $deliveryAddress,
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
                        'delivery_name'          => $deliveryName,
                        'delivery_city'          => $deliveryCity,
                        'delivery_postcode'      => $deliveryPostcode,
                        'delivery_address'       => $deliveryAddress,
                        'order_total'            => $orderTotal,
                        'line_total'             => (float) ($line['LineTotal'] ?? 0),
                        'order_quantity'         => (int) ($line['OrderQuantity'] ?? 0),
                        'quantity_completed'     => 0,
                        'required_date'          => $requiredDate,
                        'original_required_date' => $requiredDate,
                        'board'                  => 'unplanned',
                        'position'               => ++$maxPosition,
                        'unleashed_status'       => $orderStatus,
                        'synced_at'              => now(),
                    ]);
                    $created++;
                }
            }
        }
    }

    private function syncAssemblies(UnleashedService $unleashed, int &$created, int &$updated): void
    {
        // assemblyStatus=Parked returns all open assemblies including custom statuses.
        // Assemblies that vanish from this list are archived as 'completed'; the daily
        // print:fix-archive-labels --include-completed cron corrects any that were deleted.
        $all = $unleashed->paginateFast('Assemblies', ['assemblyStatus' => 'Parked'], 200);

        // Only process assemblies for A1 Printing, JW Products, or Hammond & Harper warehouses.
        $allowedCodes = $unleashed->fetchPrintWarehouseCodes();
        if (!empty($allowedCodes)) {
            $all = array_values(array_filter($all, function ($a) use ($allowedCodes) {
                $src  = $a['SourceWarehouse']['WarehouseCode'] ?? null;
                $dest = $a['DestinationWarehouse']['WarehouseCode'] ?? null;
                return in_array($src, $allowedCodes, true) || in_array($dest, $allowedCodes, true);
            }));
        }

        // Pre-fetch all active assembly jobs into memory — avoids one DB query per assembly
        $activeJobs = PrintJob::active()
            ->where('is_manual', false)
            ->where('order_number', 'like', 'ASM-%')
            ->whereNotNull('unleashed_guid')
            ->get()
            ->keyBy('unleashed_guid');

        $maxPosition = PrintJob::where('board', 'unplanned')->max('position') ?? 0;

        // Batch-fetch SO data for all active assemblies
        $soNumbers = array_values(array_unique(array_filter(
            array_column(array_map(fn($a) => ['SalesOrderNumber' => $a['SalesOrderNumber'] ?? null], $all), 'SalesOrderNumber')
        )));
        $soData = !empty($soNumbers) ? $unleashed->fetchSalesOrderData($soNumbers) : [];

        $seenGuids = [];

        foreach ($all as $assembly) {
            $guid = $assembly['Guid'] ?? null;
            if (!$guid) continue;

            $assemblyNumber = $assembly['AssemblyNumber'] ?? '';
            $assemblyStatus = $assembly['AssemblyStatus'] ?? 'Open';
            $productCode    = $assembly['Product']['ProductCode'] ?? null;
            if (!$productCode) continue;

            $seenGuids[$guid] = true;
            $existing         = $activeJobs->get($guid);

            $productDescription = $assembly['Product']['ProductDescription'] ?? null;
            $assembledQty       = (int) ($assembly['Quantity'] ?? 0);
            $assembleBy         = $unleashed->parseDate($assembly['AssembleBy'] ?? null);
            $assemblyDate       = $unleashed->parseDate($assembly['AssemblyDate'] ?? null);
            $comments           = $assembly['Comments'] ?? null;
            $soNumber           = $assembly['SalesOrderNumber'] ?? null;
            $soTotal            = 0.0;
            $soRequiredDate     = null;
            $customerName       = $soNumber ?? '';
            if ($soNumber && isset($soData[$soNumber])) {
                $matchedLine = null;
                foreach ($soData[$soNumber]['lines'] as $line) {
                    if (($line['Product']['ProductCode'] ?? null) === $productCode) {
                        if ($matchedLine === null) {
                            $matchedLine = $line;
                        }
                        if ((int)($line['OrderQuantity'] ?? 0) === $assembledQty) {
                            $matchedLine = $line;
                            break;
                        }
                    }
                }
                if ($matchedLine) {
                    $soTotal = (float) ($matchedLine['LineTotal'] ?? 0);
                }
                $soRequiredDate = $unleashed->parseDate($soData[$soNumber]['requiredDate'] ?? null);
                $customerName   = $soData[$soNumber]['customerName'] ?? $soNumber;
            }

            $requiredDate = $soRequiredDate ?? $assembleBy;

            if ($existing) {
                $update = [
                    'order_number'        => $assemblyNumber,
                    'order_date'          => $assemblyDate,
                    'customer_name'       => $customerName,
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
                    'position'               => ++$maxPosition,
                    'unleashed_status'       => $assemblyStatus,
                    'synced_at'              => now(),
                ]);
                $created++;
            }
        }

        // Sweep using the pre-fetched collection — no extra DB query needed.
        // Anything active in our DB that wasn't in the active Unleashed list → archive as
        // 'completed' for now; the daily fix-archive-labels cron will relabel deleted ones.
        foreach ($activeJobs as $jobGuid => $job) {
            if (isset($seenGuids[$jobGuid])) continue;
            $job->update(['archived_at' => now(), 'archive_reason' => 'completed']);
        }
    }
}
