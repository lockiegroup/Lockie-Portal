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

    private function fetchAllPages(string $endpoint, array $params = [], int $pageSize = 200): array
    {
        $items = [];
        $page  = 1;
        do {
            $data     = $this->unleashed->get($endpoint, array_merge($params, ['pageSize' => $pageSize, 'pageNumber' => $page]));
            $fetched  = $data['Items'] ?? [];
            $items    = array_merge($items, $fetched);
            $maxPages = (int) ($data['Pagination']['NumberOfPages'] ?? 1);
            $this->line("    page {$page}/{$maxPages} (" . count($items) . " total)");
            $page++;
        } while ($page <= $maxPages);
        return $items;
    }

    private function syncSales(string $from, string $to, array $substitutions): void
    {
        // Customer type lookup
        $this->info('Fetching customers…');
        $customers   = $this->fetchAllPages('Customers', ['includeObsolete' => 'true'], 200);
        $ctypeByCode = [];
        $ctypeByGuid = [];
        foreach ($customers as $c) {
            $code = $c['CustomerCode'] ?? null;
            $guid = $c['Guid'] ?? null;
            $type = $c['CustomerType'] ?? '';
            if ($code) $ctypeByCode[$code] = $type;
            if ($guid) $ctypeByGuid[$guid] = $type;
        }

        // Product group lookup
        $this->info('Fetching products…');
        $products = $this->fetchAllPages('Products', ['includeObsolete' => 'true'], 200);
        $pgroup   = [];
        foreach ($products as $p) {
            $code = $p['ProductCode'] ?? null;
            if ($code) $pgroup[$code] = ($p['ProductGroup']['GroupName'] ?? '');
        }

        $this->info('Fetching and inserting sales orders year by year…');
        $startYear = (int) Carbon::parse($from)->format('Y');
        $endYear   = (int) Carbon::parse($to)->format('Y');
        $now       = now()->toDateTimeString();
        $total     = 0;
        $seenGuids = [];

        // Truncate once up front, then process one year at a time to keep memory low
        DB::statement('TRUNCATE TABLE sales_lines');

        for ($y = $startYear; $y <= $endYear; $y++) {
            $yFrom  = max($from, "{$y}-01-01");
            $yTo    = min($to, "{$y}-12-31");
            $orders = $this->unleashed->fetchByDateRange('SalesOrders', [], $yFrom, $yTo);
            $rows   = [];

            foreach ($orders as $o) {
                $guid = $o['Guid'] ?? null;
                if ($guid && isset($seenGuids[$guid])) continue;
                if ($guid) $seenGuids[$guid] = true;

                if (strtolower($o['OrderStatus'] ?? '') === 'deleted') continue;

                $orderDate = $this->unleashed->parseDate($o['OrderDate'] ?? null);
                if (!$orderDate) continue;

                $cust        = $o['Customer'] ?? [];
                $code        = $cust['CustomerCode'] ?? '';
                $typ         = $ctypeByCode[$code] ?? ($ctypeByGuid[$cust['Guid'] ?? ''] ?? '');
                $wh          = ($o['Warehouse'] ?? [])['WarehouseName'] ?? '';
                $orderStatus = $o['CustomOrderStatus'] ?: ($o['OrderStatus'] ?? '');

                foreach ($o['SalesOrderLines'] ?? [] as $ln) {
                    $rawPc = strtoupper(trim(($ln['Product'] ?? [])['ProductCode'] ?? ''));
                    $pg    = $pgroup[$rawPc] ?? '';
                    $pc    = $rawPc;
                    foreach ($substitutions as $sub) {
                        if ($pc && str_contains($pc, $sub['find'])) {
                            $pc = str_replace($sub['find'], $sub['replace'], $pc);
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
                        'customer_type'  => substr(trim($typ), 0, 100) ?: null,
                        'product_code'   => substr($pc, 0, 100) ?: null,
                        'product_group'  => substr($pg, 0, 100) ?: null,
                        'status'         => substr(strtolower(trim($orderStatus)), 0, 50) ?: null,
                        'quantity'       => max(0, (float)($ln['OrderQuantity'] ?? 0)),
                        'sub_total'      => max(0, (float)($ln['LineTotal'] ?? 0)),
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
            }
            unset($orders);

            foreach (array_chunk($rows, 1000) as $chunk) {
                DB::table('sales_lines')->insert($chunk);
            }
            $total += count($rows);
            unset($rows);
            $this->line("  {$y}: inserted (running total: {$total} rows)");
        }
        unset($seenGuids);

        ActivityLog::record('imports.sales', "Auto-synced {$total} sales line(s) from Unleashed API");
        $this->info("Sales sync complete: {$total} rows.");
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

                $rawPc = strtoupper(trim(($ln['Product'] ?? [])['ProductCode'] ?? ''));
                $pc    = $rawPc;
                foreach ($substitutions as $sub) {
                    if ($pc && str_contains($pc, $sub['find'])) {
                        $pc = str_replace($sub['find'], $sub['replace'], $pc);
                    }
                }

                $insertRows[] = [
                    'credit_no'      => substr(trim($c['CreditNoteNumber'] ?? $c['CreditNumber'] ?? ''), 0, 50) ?: null,
                    'credit_date'    => $creditDate,
                    'customer_code'  => substr(trim($code), 0, 100) ?: null,
                    'product_code'   => substr($pc, 0, 100) ?: null,
                    'quantity'       => max(0, $qty),
                    'warehouse'      => substr(trim($wh), 0, 100) ?: null,
                    'sub_total'      => max(0, $total),
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
