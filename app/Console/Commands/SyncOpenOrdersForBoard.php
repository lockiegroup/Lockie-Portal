<?php

namespace App\Console\Commands;

use App\Models\StockWatchlistSubstitution;
use App\Services\UnleashedService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOpenOrdersForBoard extends Command
{
    protected $signature = 'order-board:sync-orders';

    protected $description = 'Lightweight sync: fetch only open Unleashed orders to keep the Order Board current';

    // Unleashed statuses that mean "still open / not yet dispatched"
    private const OPEN_STATUSES = ['Placed', 'Parked', 'Backordered'];

    public function handle(): int
    {
        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key')
        );

        $substitutions = StockWatchlistSubstitution::all()->map(fn($s) => [
            'find'    => strtoupper($s->find),
            'replace' => strtoupper($s->replace),
        ])->all();

        $this->info('Fetching open orders from Unleashed…');

        // Collect all open orders across the three open statuses
        $openOrders = [];
        $seenGuids  = [];

        foreach (self::OPEN_STATUSES as $status) {
            $items = $this->fetchByStatus($unleashed, $status);
            $this->line("  {$status}: " . count($items) . ' orders');

            foreach ($items as $order) {
                $guid = $order['Guid'] ?? null;
                if ($guid && isset($seenGuids[$guid])) continue;
                if ($guid) $seenGuids[$guid] = true;
                $openOrders[] = $order;
            }
        }

        $this->info(count($openOrders) . ' open orders total');

        $now          = now()->toDateTimeString();
        $fetchedNos   = [];
        $rows         = [];

        foreach ($openOrders as $o) {
            $orderNo = substr(trim($o['OrderNumber'] ?? ''), 0, 50) ?: null;
            if (!$orderNo) continue;

            if (strtolower($o['OrderStatus'] ?? '') === 'deleted') continue;

            $fetchedNos[] = $orderNo;

            $orderDate = $unleashed->parseDate($o['OrderDate'] ?? null);
            $cust        = $o['Customer'] ?? [];
            $code        = $cust['CustomerCode'] ?? '';
            $wh          = ($o['Warehouse'] ?? [])['WarehouseName'] ?? '';
            $orderStatus = $o['CustomOrderStatus'] ?: ($o['OrderStatus'] ?? '');

            foreach ($o['SalesOrderLines'] ?? [] as $ln) {
                $rawPc = trim(($ln['Product'] ?? [])['ProductCode'] ?? '');
                $pc    = $rawPc;
                foreach ($substitutions as $sub) {
                    if ($pc && str_contains(strtoupper($pc), $sub['find'])) {
                        $pc = str_ireplace($sub['find'], $sub['replace'], $pc);
                    }
                }
                $rows[] = [
                    'order_no'       => $orderNo,
                    'order_date'     => $orderDate,
                    'required_date'  => $unleashed->parseDate($o['RequiredDate'] ?? null),
                    'completed_date' => $unleashed->parseDate($o['CompletedDate'] ?? null),
                    'warehouse'      => substr(trim($wh), 0, 100) ?: null,
                    'customer_code'  => substr(trim($code), 0, 100) ?: null,
                    'customer'       => substr(trim($cust['CustomerName'] ?? ''), 0, 255) ?: null,
                    'product_code'   => substr($pc, 0, 100) ?: null,
                    'status'         => substr(strtolower(trim($orderStatus)), 0, 50) ?: null,
                    'quantity'       => (float)($ln['OrderQuantity'] ?? 0),
                    'sub_total'      => (float)($ln['LineTotal'] ?? 0),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        // Upsert: delete lines for orders we just fetched, then reinsert fresh data
        if (!empty($fetchedNos)) {
            foreach (array_chunk($fetchedNos, 500) as $chunk) {
                DB::table('sales_lines')->whereIn('order_no', $chunk)->delete();
            }
            foreach (array_chunk($rows, 4000) as $chunk) {
                DB::table('sales_lines')->insert($chunk);
            }
        }

        // Mark orders that were previously open but no longer returned as open
        // (they've been completed or deleted in Unleashed since last sync)
        if (!empty($fetchedNos)) {
            DB::table('sales_lines')
                ->whereNotIn('status', ['completed', 'deleted'])
                ->whereNotNull('order_no')
                ->whereNotIn('order_no', $fetchedNos)
                ->update(['status' => 'completed', 'updated_at' => $now]);
        }

        $openCount = DB::table('sales_lines')
            ->whereNotIn('status', ['completed', 'deleted'])
            ->distinct()
            ->count('order_no');

        $this->info("Done. {$openCount} open orders on the board.");

        return 0;
    }

    private function fetchByStatus(UnleashedService $unleashed, string $status): array
    {
        $items    = [];
        $page     = 1;
        $maxPages = 1;

        do {
            $data     = $unleashed->get("SalesOrders/{$page}", [
                'pageSize'    => 200,
                'orderStatus' => $status,
            ]);
            $items    = array_merge($items, $data['Items'] ?? []);
            $maxPages = max(1, (int)($data['Pagination']['NumberOfPages'] ?? 1));
            $page++;
        } while ($page <= $maxPages);

        return $items;
    }
}
