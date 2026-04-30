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

        // Stock levels — per product code in parallel batches
        $stockMap = $unleashed->fetchStockOnHandByCodes($productCodes);

        // Open PO data — placed, receiving, parked
        $poMap = $unleashed->fetchOpenPurchaseOrders();

        $now = now();
        foreach ($productCodes as $code) {
            $s  = $stockMap[$code] ?? null;
            $po = $poMap[$code]    ?? null;

            StockWatchlistStock::updateOrCreate(
                ['product_code' => $code],
                [
                    'qty_on_hand'      => $s  ? $s['on_hand']   : 0,
                    'qty_allocated'    => $s  ? $s['allocated']  : 0,
                    'qty_on_order'     => $po ? $po['qty']       : 0,
                    'po_expected_date' => $po ? $po['date']      : null,
                    'synced_at'        => $now,
                ]
            );
        }

        return ['products' => count($productCodes)];
    }
}
