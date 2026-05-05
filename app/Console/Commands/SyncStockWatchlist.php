<?php

namespace App\Console\Commands;

use App\Services\StockWatchlistSyncService;
use Illuminate\Console\Command;

class SyncStockWatchlist extends Command
{
    protected $signature   = 'stock-watchlist:sync';
    protected $description = 'Sync stock levels and PO data for all Stock Watchlist products from Unleashed';

    public function handle(): void
    {
        $this->info('Syncing Stock Watchlist…');

        try {
            $result = (new StockWatchlistSyncService())->run();
            $this->info("Done. Synced {$result['products']} products.");
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
        }
    }
}
