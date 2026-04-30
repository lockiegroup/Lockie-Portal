<?php

namespace App\Services;

use App\Models\StockWatchlistItem;
use App\Models\StockWatchlistStock;

class StockWatchlistSyncService
{
    public function run(): array
    {
        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        $productCodes = StockWatchlistItem::pluck('product_code')->unique()->values()->all();

        if (empty($productCodes)) {
            return ['products' => 0];
        }

        // Find JW Products warehouse code
        $whData = $unleashed->get('Warehouses', ['pageSize' => 200, 'pageNumber' => 1]);
        $jwCode = null;
        foreach ($whData['Items'] ?? [] as $wh) {
            if (str_contains(strtolower($wh['WarehouseName'] ?? ''), 'jw products')) {
                $jwCode = $wh['WarehouseCode'];
                break;
            }
        }

        if (!$jwCode) {
            throw new \RuntimeException('JW Products warehouse not found in Unleashed');
        }

        $stockMap = $this->syncStock($unleashed, $productCodes, $jwCode);

        return ['products' => count($productCodes), 'stock' => $stockMap];
    }

    private function syncStock(UnleashedService $unleashed, array $productCodes, string $jwCode): array
    {
        // JW Products is a separate Unleashed sub-account — the unfiltered
        // StockOnHand endpoint doesn't include it. Fetch each warehouse explicitly
        // and sum quantities so we capture stock from all locations.
        $whData = $unleashed->get('Warehouses', ['pageSize' => 200, 'pageNumber' => 1]);
        $warehouseCodes = array_column($whData['Items'] ?? [], 'WarehouseCode');

        $stockMap = []; // [code => ['name'=>, 'on_hand'=>, 'allocated'=>]]

        foreach ($warehouseCodes as $wh) {
            $page = 1;
            do {
                $data     = $unleashed->get('StockOnHand', ['warehouseCode' => $wh, 'pageSize' => 500, 'pageNumber' => $page]);
                $items    = $data['Items'] ?? [];
                $maxPages = $data['Pagination']['NumberOfPages'] ?? 1;

                foreach ($items as $item) {
                    $code = $item['ProductCode'] ?? null;
                    if (!$code || !in_array($code, $productCodes)) continue;

                    if (!isset($stockMap[$code])) {
                        $stockMap[$code] = ['name' => $item['ProductDescription'] ?? null, 'on_hand' => 0.0, 'allocated' => 0.0];
                    }
                    $stockMap[$code]['on_hand']   += (float) ($item['QtyOnHand']         ?? 0);
                    $stockMap[$code]['allocated'] += (float) ($item['AllocatedQuantity']  ?? 0);
                }

                $page++;
            } while ($page <= $maxPages);
        }

        // For any product not found across warehouses, or found with 0 on-hand,
        // also try a direct productCode lookup — this catches stock that is recorded
        // without a warehouse assignment (WarehouseCode empty in Unleashed).
        foreach ($productCodes as $code) {
            if (isset($stockMap[$code]) && $stockMap[$code]['on_hand'] > 0) continue;

            $data = $unleashed->get('StockOnHand', ['productCode' => $code, 'pageSize' => 10]);
            $item = $data['Items'][0] ?? null;
            if ($item && (float)($item['QtyOnHand'] ?? 0) > 0) {
                $stockMap[$code] = [
                    'name'      => $item['ProductDescription'] ?? null,
                    'on_hand'   => (float)($item['QtyOnHand']    ?? 0),
                    'allocated' => (float)($item['AllocatedQty'] ?? $item['AllocatedQuantity'] ?? 0),
                ];
            }
        }

        // Fetch open PO data for watchlist products
        $poMap = $this->fetchPoData($unleashed, $productCodes);

        $now = now();
        foreach ($productCodes as $code) {
            $s  = $stockMap[$code] ?? null;
            $po = $poMap[$code]    ?? null;

            StockWatchlistStock::updateOrCreate(
                ['product_code' => $code],
                [
                    'product_name'     => $s  ? $s['name']      : null,
                    'qty_on_hand'      => $s  ? $s['on_hand']   : 0,
                    'qty_allocated'    => $s  ? $s['allocated']  : 0,
                    'qty_on_order'     => $po ? $po['qty']       : 0,
                    'po_expected_date' => $po ? $po['date']      : null,
                    'synced_at'        => $now,
                ]
            );
        }

        return ['found' => $stockMap, 'warehouses_checked' => $warehouseCodes];
    }

    private function fetchPoData(UnleashedService $unleashed, array $productCodes): array
    {
        $results = [];

        // Reuse the existing parallelPaginate for the three open PO statuses
        $raw = $unleashed->parallelPaginate([
            'placed'    => ['PurchaseOrders', ['orderStatus' => 'Placed']],
            'receiving' => ['PurchaseOrders', ['orderStatus' => 'Receiving']],
            'parked'    => ['PurchaseOrders', ['orderStatus' => 'Parked']],
        ], 200);

        $allPos = array_merge($raw['placed'], $raw['receiving'], $raw['parked']);

        foreach ($allPos as $po) {
            $dueDate = $unleashed->parseDate($po['RequiredDate'] ?? null)
                    ?? $unleashed->parseDate($po['DeliveryDate'] ?? null);

            foreach ($po['PurchaseOrderLines'] ?? [] as $line) {
                $code      = $line['Product']['ProductCode'] ?? null;
                if (!$code || !in_array($code, $productCodes)) continue;

                $remaining = ((float) ($line['OrderQuantity'] ?? 0)) - ((float) ($line['ReceivedQuantity'] ?? 0));
                if ($remaining <= 0) continue;

                if (!isset($results[$code])) {
                    $results[$code] = ['qty' => 0.0, 'date' => null];
                }
                $results[$code]['qty'] += $remaining;
                if ($dueDate && ($results[$code]['date'] === null || $dueDate < $results[$code]['date'])) {
                    $results[$code]['date'] = $dueDate;
                }
            }
        }

        return $results;
    }

}
