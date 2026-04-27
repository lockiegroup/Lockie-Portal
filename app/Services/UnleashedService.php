<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UnleashedService
{
    private const BASE_URL = 'https://api.unleashedsoftware.com';

    public function __construct(
        private readonly string $apiId,
        private readonly string $apiKey
    ) {}

    private function sign(string $queryString): string
    {
        return base64_encode(hash_hmac('sha256', $queryString, $this->apiKey, true));
    }

    private function headers(string $queryString = ''): array
    {
        return [
            'api-auth-id'        => $this->apiId,
            'api-auth-signature' => $this->sign($queryString),
            'Accept'             => 'application/json',
            'Content-Type'       => 'application/json',
        ];
    }

    public function get(string $endpoint, array $params = [], int $timeout = 60): array
    {
        $queryString = http_build_query($params);
        $url = self::BASE_URL . '/' . $endpoint . ($queryString ? "?{$queryString}" : '');

        $response = Http::timeout($timeout)
            ->withHeaders($this->headers($queryString))
            ->get($url);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Unleashed API error ({$response->status()}): " . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    public function paginate(string $endpoint, array $params = [], int $pageSize = 200): array
    {
        $items = [];
        $page  = 1;

        do {
            $data  = $this->get($endpoint, array_merge(['pageSize' => $pageSize], $params, [
                'pageNumber' => $page,
            ]));
            $items    = array_merge($items, $data['Items'] ?? []);
            $maxPages = $data['Pagination']['NumberOfPages'] ?? 1;
            $page++;
        } while ($page <= $maxPages);

        return $items;
    }

    /**
     * Fetch multiple endpoints concurrently, paginating each in parallel batches.
     * $requests: ['key' => ['Endpoint', ['param' => 'value']], ...]
     */
    public function parallelPaginate(array $requests, int $pageSize = 500): array
    {
        $keys       = array_keys($requests);
        $results    = array_fill_keys($keys, []);
        $seenGuids  = array_fill_keys($keys, []);
        $maxPages   = array_fill_keys($keys, 1);
        $activeKeys = $keys;
        $page       = 1;

        do {
            $batch = [];
            foreach ($activeKeys as $key) {
                [$endpoint, $params] = $requests[$key];
                $qs = http_build_query(array_merge($params, [
                    'pageSize'   => $pageSize,
                    'pageNumber' => $page,
                ]));
                $batch[$key] = [
                    'url'     => self::BASE_URL . '/' . $endpoint . "?{$qs}",
                    'headers' => $this->headers($qs),
                ];
            }

            $responses = Http::pool(function ($pool) use ($batch) {
                $calls = [];
                foreach ($batch as $key => $info) {
                    $calls[] = $pool->as($key)
                        ->timeout(30)
                        ->withHeaders($info['headers'])
                        ->get($info['url']);
                }
                return $calls;
            });

            $nextActive = [];
            foreach ($activeKeys as $key) {
                $res = $responses[$key];
                if ($res instanceof \Throwable) {
                    throw new \RuntimeException('Unleashed API error: ' . $res->getMessage());
                }
                if ($res->failed()) {
                    throw new \RuntimeException(
                        "Unleashed API error ({$res->status()}): " . $res->body()
                    );
                }
                $data     = $res->json() ?? [];
                $items    = $data['Items'] ?? [];
                $newCount = 0;
                foreach ($items as $item) {
                    $guid = $item['Guid'] ?? null;
                    if ($guid === null || !isset($seenGuids[$key][$guid])) {
                        $results[$key][] = $item;
                        if ($guid !== null) {
                            $seenGuids[$key][$guid] = true;
                        }
                        $newCount++;
                    }
                }
                $maxPages[$key] = $data['Pagination']['NumberOfPages'] ?? 1;
                // Stop if no new items were added (Unleashed pagination bug: filtered queries
                // return the same page repeatedly when NumberOfPages > actual filtered pages)
                if ($newCount > 0 && $page < $maxPages[$key]) {
                    $nextActive[] = $key;
                }
            }

            $activeKeys = $nextActive;
            $page++;
        } while (!empty($activeKeys));

        return $results;
    }

    /**
     * Fetch real TotalCost and QtyOnHand per warehouse using warehouseCode filter.
     * Runs warehouses in parallel. pageSize=500 attempts to get all products in
     * one page; Guid dedup stops if Unleashed repeats pages due to its pagination bug.
     * Returns ['Warehouse Name' => ['totalCost' => float, 'qty' => float], ...]
     */
    public function fetchStockByWarehouse(?string $asAt = null): array
    {
        // Get all warehouses
        $whData     = $this->get('Warehouses', ['pageSize' => 200, 'pageNumber' => 1]);
        $warehouses = $whData['Items'] ?? [];
        if (empty($warehouses)) return [];

        $grouped = [];

        // Process in batches of 10 warehouses at a time
        foreach (array_chunk($warehouses, 10) as $batch) {
            $meta     = []; // code → name
            $seen     = []; // code → [guid => true]
            $totals   = []; // code → [totalCost, qty]
            $active   = [];

            foreach ($batch as $wh) {
                $code = $wh['WarehouseCode'] ?? '';
                $name = $wh['WarehouseName'] ?? $code;
                if (!$code) continue;
                $meta[$code]   = $name;
                $seen[$code]   = [];
                $totals[$code] = ['totalCost' => 0.0, 'qty' => 0.0];
                $active[]      = $code;
            }

            $page = 1;
            do {
                $pageRequests = [];
                foreach ($active as $code) {
                    $params = [
                        'warehouseCode' => $code,
                        'pageSize'      => 2000,
                        'pageNumber'    => $page,
                    ];
                    if ($asAt !== null) {
                        $params['asAtDate'] = $asAt;
                    }
                    $qs = http_build_query($params);
                    $pageRequests[$code] = [
                        'url'     => self::BASE_URL . '/StockOnHand?' . $qs,
                        'headers' => $this->headers($qs),
                    ];
                }

                $responses = Http::pool(function ($pool) use ($pageRequests) {
                    $calls = [];
                    foreach ($pageRequests as $code => $info) {
                        $calls[] = $pool->as($code)
                            ->timeout(30)
                            ->withHeaders($info['headers'])
                            ->get($info['url']);
                    }
                    return $calls;
                });

                $nextActive = [];
                foreach ($active as $code) {
                    $res = $responses[$code] ?? null;
                    if (!$res || $res instanceof \Throwable || $res->failed()) continue;

                    $data     = $res->json() ?? [];
                    $items    = $data['Items'] ?? [];
                    $maxPages = $data['Pagination']['NumberOfPages'] ?? 1;
                    $newCount = 0;

                    foreach ($items as $item) {
                        $guid = $item['Guid'] ?? null;
                        if ($guid !== null && isset($seen[$code][$guid])) continue;
                        if ($guid !== null) $seen[$code][$guid] = true;
                        $newCount++;
                        $totals[$code]['totalCost'] += (float) ($item['TotalCost'] ?? 0);
                        $totals[$code]['qty']       += (float) ($item['QtyOnHand'] ?? 0);
                    }

                    // Stop paginating if no new items (Unleashed pagination bug on filtered queries)
                    if ($newCount > 0 && $page < $maxPages) {
                        $nextActive[] = $code;
                    }
                }

                $active = $nextActive;
                $page++;
            } while (!empty($active));

            foreach ($meta as $code => $name) {
                if ($totals[$code]['totalCost'] > 0) {
                    $grouped[$name] = $totals[$code];
                }
            }
        }

        uasort($grouped, fn($a, $b) => $b['totalCost'] <=> $a['totalCost']);
        return $grouped;
    }

    /**
     * Parse a date string from Unleashed API.
     * Handles /Date(ms)/ format and ISO strings, returns Y-m-d or null.
     */
    public function parseDate(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        // Handle /Date(1234567890000)/ or /Date(1234567890000+0000)/ format
        if (preg_match('#/Date\((\d+)([+-]\d+)?\)/#', $date, $matches)) {
            $ms = (int) $matches[1];
            return date('Y-m-d', (int) ($ms / 1000));
        }

        // ISO or any other parseable date string
        try {
            $ts = strtotime($date);
            if ($ts === false) {
                return null;
            }
            return date('Y-m-d', $ts);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Fetch all open Sales Orders from Unleashed where the warehouse name contains "a1".
     * Returns filtered array of orders.
     */
    public function fetchA1PrintingOrders(): array
    {
        // Find A1 Printing warehouse code to filter at API level (avoids fetching all orders)
        $whData = $this->get('Warehouses', ['pageSize' => 200, 'pageNumber' => 1]);
        $a1Code = null;
        foreach ($whData['Items'] ?? [] as $wh) {
            if (str_contains(strtolower($wh['WarehouseName'] ?? ''), 'a1')) {
                $a1Code = $wh['WarehouseCode'];
                break;
            }
        }

        if (!$a1Code) return [];

        $orders = $this->paginate('SalesOrders', ['warehouseCode' => $a1Code], 500);

        // Return all statuses — the sync handles Completed/Deleted archiving explicitly.
        // Backordered (partially shipped) must stay active so remaining packs stay in the schedule.
        return $orders;
    }

    /**
     * Fetch full SalesOrder details (including SalesOrderLines) by GUID in parallel.
     * The paginated list endpoint omits line data; individual GETs include it.
     * Returns ['guid' => $orderArray, ...]
     */
    public function fetchSalesOrderDetails(array $guids, int $batchSize = 50): array
    {
        $results = [];

        foreach (array_chunk($guids, $batchSize) as $batch) {
            $responses = Http::pool(function ($pool) use ($batch) {
                $calls = [];
                foreach ($batch as $guid) {
                    // Use list endpoint with guid filter — same pattern as fetchWarehousesByOrderNumber
                    $qs = http_build_query(['pageSize' => 1, 'pageNumber' => 1, 'guid' => $guid]);
                    $calls[] = $pool->as($guid)
                        ->timeout(30)
                        ->withHeaders($this->headers($qs))
                        ->get(self::BASE_URL . '/SalesOrders?' . $qs);
                }
                return $calls;
            });

            foreach ($batch as $guid) {
                $res = $responses[$guid] ?? null;
                if (!$res || $res instanceof \Throwable || $res->failed()) continue;
                $data  = $res->json() ?? [];
                $order = ($data['Items'] ?? [])[0] ?? null;
                if ($order && !empty($order['Guid'])) {
                    $results[$guid] = $order;
                }
            }
        }

        return $results;
    }

    /**
     * Fetch all non-completed assemblies from Unleashed (all warehouses).
     * Fetches Open, Parked, and In Progress statuses in parallel.
     */
    public function fetchAssemblies(): array
    {
        // Return all statuses so the sync service can distinguish completed (→ archive) from deleted (→ hard delete).
        return $this->paginate('Assemblies', [], 200);
    }

    /**
     * Fetch all products from Unleashed.
     */
    public function fetchProducts(): array
    {
        return $this->paginate('Products', [], 500);
    }

    /**
     * Fetch open purchase orders and aggregate remaining qty + earliest due date per ProductCode.
     * Returns: ['PROD001' => ['qty' => 50.0, 'date' => '2026-05-15'], ...]
     */
    public function fetchOpenPurchaseOrders(): array
    {
        $results = $this->parallelPaginate([
            'placed'     => ['PurchaseOrders', ['orderStatus' => 'Placed']],
            'receiving'  => ['PurchaseOrders', ['orderStatus' => 'Receiving']],
            'parked'     => ['PurchaseOrders', ['orderStatus' => 'Parked']],
        ], 200);

        $all = array_merge($results['placed'], $results['receiving'], $results['parked']);

        $aggregated = [];
        foreach ($all as $po) {
            $dueDate = $this->parseDate($po['RequiredDate'] ?? null);
            foreach ($po['PurchaseOrderLines'] ?? [] as $line) {
                $code      = $line['Product']['ProductCode'] ?? null;
                if (!$code) continue;
                $remaining = ((float) ($line['OrderQuantity'] ?? 0)) - ((float) ($line['ReceivedQuantity'] ?? 0));
                if ($remaining <= 0) continue;
                if (!isset($aggregated[$code])) {
                    $aggregated[$code] = ['qty' => 0.0, 'date' => null];
                }
                $aggregated[$code]['qty'] += $remaining;
                if ($dueDate !== null) {
                    if ($aggregated[$code]['date'] === null || $dueDate < $aggregated[$code]['date']) {
                        $aggregated[$code]['date'] = $dueDate;
                    }
                }
            }
        }

        return $aggregated;
    }

    /**
     * Fetch completed SalesInvoices from $startDate, aggregate qty sold by warehouse+product.
     * The SalesInvoices list endpoint includes InvoiceLines, so one paginated pass is enough.
     * Returns: ['WH01' => ['PROD001' => 150.0, ...], ...]
     */
    public function fetchInvoicedSales(string $startDate): array
    {
        $result = [];

        $invoices = $this->paginate('SalesInvoices', [
            'startDate'     => $startDate,
            'invoiceStatus' => 'Complete',
        ], 500);

        foreach ($invoices as $invoice) {
            $whCode = $invoice['Warehouse']['WarehouseCode'] ?? null;
            if (!$whCode) continue;

            foreach ($invoice['InvoiceLines'] ?? [] as $line) {
                $code = $line['Product']['ProductCode'] ?? null;
                if (!$code) continue;
                $qty = (float) ($line['InvoiceQuantity'] ?? 0);
                if ($qty <= 0) continue;
                $result[$whCode][$code] = ($result[$whCode][$code] ?? 0.0) + $qty;
            }
        }

        return $result;
    }

    /**
     * Fetch warehouse names for specific order numbers in parallel batches.
     * Returns ['SO-00012345' => 'JW Products', ...]
     */
    public function fetchWarehousesByOrderNumber(array $orderNumbers, int $batchSize = 50): array
    {
        $results = [];

        foreach (array_chunk($orderNumbers, $batchSize) as $batch) {
            $responses = Http::pool(function ($pool) use ($batch) {
                $calls = [];
                foreach ($batch as $num) {
                    $qs = http_build_query([
                        'pageSize'    => 1,
                        'pageNumber'  => 1,
                        'orderNumber' => $num,
                    ]);
                    $calls[] = $pool->as($num)
                        ->timeout(30)
                        ->withHeaders($this->headers($qs))
                        ->get(self::BASE_URL . '/SalesOrders?' . $qs);
                }
                return $calls;
            });

            foreach ($batch as $num) {
                $res = $responses[$num] ?? null;
                if (!$res || $res instanceof \Throwable || $res->failed()) continue;
                $order = ($res->json()['Items'] ?? [])[0] ?? null;
                if (!$order) continue;
                $results[$num] = $order['Warehouse']['WarehouseName']
                    ?? $order['Warehouse']['WarehouseCode']
                    ?? 'No Warehouse';
            }
        }

        return $results;
    }

    /**
     * Batch-fetch SO lines and RequiredDate for a list of order numbers in parallel.
     * Returns ['SO-00012345' => ['lines' => [...], 'requiredDate' => '...'], ...]
     */
    public function fetchSalesOrderData(array $orderNumbers, int $batchSize = 50): array
    {
        $results = [];

        foreach (array_chunk($orderNumbers, $batchSize) as $batch) {
            $responses = Http::pool(function ($pool) use ($batch) {
                $calls = [];
                foreach ($batch as $num) {
                    $qs = http_build_query([
                        'pageSize'    => 1,
                        'pageNumber'  => 1,
                        'orderNumber' => $num,
                    ]);
                    $calls[] = $pool->as($num)
                        ->timeout(30)
                        ->withHeaders($this->headers($qs))
                        ->get(self::BASE_URL . '/SalesOrders?' . $qs);
                }
                return $calls;
            });

            foreach ($batch as $num) {
                $res = $responses[$num] ?? null;
                if (!$res || $res instanceof \Throwable || $res->failed()) continue;
                $order = ($res->json()['Items'] ?? [])[0] ?? null;
                if ($order) {
                    $results[$num] = [
                        'lines'        => $order['SalesOrderLines'] ?? [],
                        'requiredDate' => $order['RequiredDate'] ?? null,
                    ];
                }
            }
        }

        return $results;
    }
}
