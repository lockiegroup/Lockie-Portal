<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\StockWatchlistSubstitution;
use App\Services\UnleashedService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUnleashedImports extends Command
{
    protected $signature = 'imports:sync-unleashed
                            {--from= : Start date (Y-m-d). Defaults to 3 years ago. Use 2001-01-01 for full history.}
                            {--sales-only : Only sync sales orders}
                            {--credits-only : Only sync credit notes}';

    protected $description = 'Pull sales orders and credit notes from Unleashed API and sync into sales_lines / credits_lines';

    private UnleashedService $unleashed;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key')
        );
        $this->unleashed = $unleashed;
        $salesOnly   = $this->option('sales-only');
        $creditsOnly = $this->option('credits-only');
        $doSales     = !$creditsOnly;
        $doCredits   = !$salesOnly;

        $fromOption = $this->option('from');
        $from = $fromOption ? Carbon::parse($fromOption)->toDateString() : '2021-01-01';
        $to   = now()->subDay()->toDateString();

        $this->info("Unleashed import sync: {$from} → {$to}");

        $substitutions = StockWatchlistSubstitution::all()->map(fn($s) => [
            'find'    => strtoupper($s->find),
            'replace' => strtoupper($s->replace),
        ])->all();

        try {
            if ($doSales)   $this->syncSales($from, $to, $substitutions);
            if ($doCredits) $this->syncCredits($substitutions);
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            ActivityLog::record('imports.sales.error', 'Auto-sync failed: ' . substr($e->getMessage(), 0, 250));
            return 1;
        }

        return 0;
    }

    // Unleashed uses the page number in the URL path: /Endpoint/1, /Endpoint/2, …
    private function fetchAllPages(string $endpoint, array $params = [], int $pageSize = 200): array
    {
        $items    = [];
        $page     = 1;
        $maxPages = 1;
        do {
            $data     = $this->unleashed->get("{$endpoint}/{$page}", array_merge($params, ['pageSize' => $pageSize]));
            $fetched  = $data['Items'] ?? [];
            $items    = array_merge($items, $fetched);
            // Unleashed can return NumberOfPages=0 for empty results; treat as 1
            $maxPages = max(1, (int) ($data['Pagination']['NumberOfPages'] ?? 1));
            $this->line("    page {$page}/{$maxPages} (" . count($items) . " total)");
            $page++;
        } while ($page <= $maxPages);
        return $items;
    }

    private function syncSales(string $from, string $to, array $substitutions): void
    {
        // Fetch all orders in a single pass — Unleashed ignores date range params on this
        // endpoint (same behaviour as SalesInvoices), so a year-by-year loop would fetch
        // the full history on every iteration and duplicate any orders that lack a Guid.
        $this->info('Fetching all sales orders…');
        $allOrders = $this->fetchAllPages('SalesOrders', [], 1000);
        $this->line('  ' . count($allOrders) . ' orders fetched');

        $now       = now()->toDateTimeString();
        $total     = 0;
        $seenGuids = [];
        $rows      = [];

        DB::statement('TRUNCATE TABLE sales_lines');

        foreach ($allOrders as $o) {
            $guid = $o['Guid'] ?? null;
            if ($guid) {
                if (isset($seenGuids[$guid])) continue;
                $seenGuids[$guid] = true;
            }

            if (strtolower($o['OrderStatus'] ?? '') === 'deleted') continue;

            $orderDate = $this->unleashed->parseDate($o['OrderDate'] ?? null);
            if (!$orderDate || $orderDate < $from || $orderDate > $to) continue;

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
                    'order_no'       => substr(trim($o['OrderNumber'] ?? ''), 0, 50) ?: null,
                    'order_date'     => $orderDate,
                    'required_date'  => $this->unleashed->parseDate($o['RequiredDate'] ?? null),
                    'completed_date' => $this->unleashed->parseDate($o['CompletedDate'] ?? null),
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

                // Flush in chunks to keep memory low
                if (count($rows) >= 4000) {
                    DB::table('sales_lines')->insert($rows);
                    $total += count($rows);
                    $rows   = [];
                }
            }
        }
        unset($allOrders, $seenGuids);

        if (!empty($rows)) {
            DB::table('sales_lines')->insert($rows);
            $total += count($rows);
        }
        unset($rows);

        $orderCount = DB::table('sales_lines')->distinct()->count('order_no');
        ActivityLog::record('imports.sales', "Auto-synced {$orderCount} orders / {$total} lines from Unleashed API");
        $this->info("Sales sync complete: {$orderCount} orders, {$total} lines.");
    }

    private function syncCredits(array $substitutions): void
    {
        $this->info('Fetching credit notes…');
        $credits = $this->fetchAllPages('CreditNotes', [], 200);
        $this->line('  ' . count($credits) . ' credit notes fetched');

        $now        = now()->toDateTimeString();
        $insertRows = [];

        foreach ($credits as $c) {
            $status = strtolower(trim($c['Status'] ?? $c['CreditStatus'] ?? ''));
            if ($status === 'deleted') continue;

            $creditDate = $this->unleashed->parseDate($c['CreditDate'] ?? null);
            if (!$creditDate) continue;

            $code = ($c['Customer'] ?? [])['CustomerCode'] ?? '';
            $wh   = ($c['Warehouse'] ?? [])['WarehouseName'] ?? '';

            foreach ($c['CreditLines'] ?? [] as $ln) {
                $qty   = (float)($ln['CreditQuantity'] ?? 0);
                $total = (float)($ln['LineTotal'] ?? 0);
                if ($qty === 0.0 && $total === 0.0) continue;

                $rawPc = trim(($ln['Product'] ?? [])['ProductCode'] ?? '');
                $pc    = $rawPc;
                foreach ($substitutions as $sub) {
                    if ($pc && str_contains(strtoupper($pc), $sub['find'])) {
                        $pc = str_ireplace($sub['find'], $sub['replace'], $pc);
                    }
                }

                $insertRows[] = [
                    'credit_no'      => substr(trim($c['CreditNoteNumber'] ?? $c['CreditNumber'] ?? ''), 0, 50) ?: null,
                    'credit_date'    => $creditDate,
                    'customer_code'  => substr(trim($code), 0, 100) ?: null,
                    'product_code'   => substr($pc, 0, 100) ?: null,
                    'quantity'       => $qty,
                    'warehouse'      => substr(trim($wh), 0, 100) ?: null,
                    'sub_total'      => $total,
                    'status'         => substr($status, 0, 50) ?: null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        $count = count($insertRows);
        $this->info("Inserting {$count} credit rows…");

        DB::statement('TRUNCATE TABLE credits_lines');
        foreach (array_chunk($insertRows, 4000) as $chunk) {
            DB::table('credits_lines')->insert($chunk);
        }
        unset($insertRows);

        ActivityLog::record('imports.credits', "Auto-synced {$count} credit line(s) from Unleashed API");
        $this->info("Credits sync complete: {$count} rows.");
    }
}
