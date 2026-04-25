<?php

namespace App\Services;

use App\Models\StockWatchlistItem;
use App\Models\StockWatchlistSale;
use App\Models\StockWatchlistStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        $this->syncStock($unleashed, $productCodes, $jwCode);
        $this->syncSalesHistory($unleashed, $productCodes);
        $this->syncProductNames();

        return ['products' => count($productCodes)];
    }

    private function syncStock(UnleashedService $unleashed, array $productCodes, string $jwCode): void
    {
        // Fetch all JW Products stock in one paginated pass, filter to watchlist in PHP
        $stockMap = [];
        $page     = 1;
        $seen     = [];

        do {
            $data     = $unleashed->get('StockOnHand', ['warehouseCode' => $jwCode, 'pageSize' => 500, 'pageNumber' => $page]);
            $items    = $data['Items'] ?? [];
            $maxPages = $data['Pagination']['NumberOfPages'] ?? 1;

            foreach ($items as $item) {
                $guid = $item['Guid'] ?? null;
                if ($guid !== null && isset($seen[$guid])) continue;
                if ($guid !== null) $seen[$guid] = true;

                $code = $item['ProductCode'] ?? null;
                if ($code && in_array($code, $productCodes)) {
                    $stockMap[$code] = $item;
                }
            }

            $page++;
        } while ($page <= $maxPages);

        // Fetch open PO data for watchlist products
        $poMap = $this->fetchPoData($unleashed, $productCodes);

        $now = now();
        foreach ($productCodes as $code) {
            $item = $stockMap[$code] ?? null;
            $po   = $poMap[$code]   ?? null;

            StockWatchlistStock::updateOrCreate(
                ['product_code' => $code],
                [
                    'product_name'    => $item['ProductDescription'] ?? null,
                    'qty_on_hand'     => (float) ($item['QtyOnHand']        ?? 0),
                    'qty_allocated'   => (float) ($item['AllocatedQuantity'] ?? 0),
                    'qty_on_order'    => $po ? $po['qty']  : 0,
                    'po_expected_date' => $po ? $po['date'] : null,
                    'synced_at'       => $now,
                ]
            );
        }
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

    private function syncSalesHistory(UnleashedService $unleashed, array $productCodes): void
    {
        // Pull 25 months to ensure we always have full previous 24 calendar months
        $startDate = now()->subMonths(25)->startOfMonth()->format('Y-m-d');

        $monthly = [];
        $page    = 1;
        $seen    = [];

        do {
            $data     = $unleashed->get('SalesInvoices', [
                'invoiceStatus' => 'Complete',
                'startDate'     => $startDate,
                'pageSize'      => 500,
                'pageNumber'    => $page,
            ]);
            $items    = $data['Items'] ?? [];
            $maxPages = $data['Pagination']['NumberOfPages'] ?? 1;

            foreach ($items as $invoice) {
                $guid = $invoice['Guid'] ?? null;
                if ($guid !== null && isset($seen[$guid])) continue;
                if ($guid !== null) $seen[$guid] = true;

                $rawDate = $invoice['InvoiceDate'] ?? $invoice['CreatedOn'] ?? null;
                $parsed  = $unleashed->parseDate($rawDate);
                if (!$parsed) continue;

                $dt    = Carbon::parse($parsed);
                $year  = $dt->year;
                $month = $dt->month;

                foreach ($invoice['InvoiceLines'] ?? [] as $line) {
                    $code = $line['Product']['ProductCode'] ?? null;
                    if (!$code || !in_array($code, $productCodes)) continue;

                    $qty = (float) ($line['InvoiceQuantity'] ?? 0);
                    if ($qty <= 0) continue;

                    $monthly[$code][$year][$month] = ($monthly[$code][$year][$month] ?? 0) + $qty;
                }
            }

            $page++;
        } while ($page <= $maxPages);

        // Flatten and bulk-upsert
        $rows = [];
        foreach ($monthly as $code => $years) {
            foreach ($years as $year => $months) {
                foreach ($months as $month => $qty) {
                    $rows[] = ['product_code' => $code, 'year' => $year, 'month' => $month, 'qty_sold' => $qty];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('stock_watchlist_sales')->upsert($chunk, ['product_code', 'year', 'month'], ['qty_sold']);
        }
    }

    private function syncProductNames(): void
    {
        // Back-fill product_name on items where the stock sync found a name
        $names = StockWatchlistStock::whereNotNull('product_name')
            ->pluck('product_name', 'product_code');

        foreach ($names as $code => $name) {
            StockWatchlistItem::where('product_code', $code)
                ->whereNull('product_name')
                ->update(['product_name' => $name]);
        }
    }
}
