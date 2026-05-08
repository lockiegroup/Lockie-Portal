<?php

namespace App\Services;

use App\Models\StockWatchlistCategory;
use App\Models\StockWatchlistItem;
use App\Models\StockWatchlistStock;
use App\Models\UnleashedProduct;
use Illuminate\Support\Facades\DB;

class StockWatchlistSyncService
{
    public function run(?StockWatchlistCategory $category = null): array
    {
        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        $query = StockWatchlistItem::query();
        if ($category) {
            $query->where('category_id', $category->id);
        }
        $productCodes = $query->pluck('product_code')->unique()->values()->all();

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

    public function syncAllProducts(?UnleashedService $unleashed = null): array
    {
        $unleashed ??= new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key'),
        );

        $products = $unleashed->fetchProducts();
        $fetched  = count($products);
        $now      = now();

        $rows = array_map(fn($p) => [
            'product_code' => $p['ProductCode'],
            'product_name' => $p['ProductDescription'] ?? $p['ProductCode'],
            'synced_at'    => $now,
        ], $products);

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('unleashed_products')->upsert($chunk, ['product_code'], ['product_name', 'synced_at']);
        }

        $dbCount = DB::table('unleashed_products')->count();

        \Log::info('syncAllProducts', ['fetched' => $fetched, 'db_count' => $dbCount]);

        return ['fetched' => $fetched, 'db_count' => $dbCount];
    }
}
