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

    public function get(string $endpoint, array $params = []): array
    {
        $queryString = http_build_query($params);
        $url = self::BASE_URL . '/' . $endpoint . ($queryString ? "?{$queryString}" : '');

        $response = Http::timeout(30)
            ->withHeaders($this->headers($queryString))
            ->get($url);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Unleashed API error ({$response->status()}): " . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    public function paginate(string $endpoint, array $params = []): array
    {
        $items = [];
        $page  = 1;

        do {
            $data  = $this->get($endpoint, array_merge($params, [
                'pageSize'   => 200,
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
    public function parallelPaginate(array $requests): array
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
                    'pageSize'   => 200,
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
     * Fetch stock cost per warehouse using AllWarehouses endpoint.
     * AllWarehouses only returns AvailableQty (no TotalCost), so cost is
     * computed as unitCost × warehouseAvailableQty where unitCost comes
     * from the global unfiltered StockOnHand data passed in $unitCostMap.
     *
     * @param array $unitCostMap  ['productGuid' => unitCostFloat, ...]
     * Returns ['Warehouse Name' => ['totalCost' => float, 'qty' => float], ...]
     */
    public function fetchStockAllWarehouses(array $unitCostMap, int $batchSize = 50): array
    {
        $grouped = [];

        foreach (array_chunk(array_keys($unitCostMap), $batchSize) as $batch) {
            $responses = Http::pool(function ($pool) use ($batch) {
                $calls = [];
                foreach ($batch as $guid) {
                    $calls[] = $pool->as($guid)
                        ->timeout(30)
                        ->withHeaders($this->headers(''))
                        ->get(self::BASE_URL . '/StockOnHand/' . $guid . '/AllWarehouses');
                }
                return $calls;
            });

            foreach ($batch as $guid) {
                $res = $responses[$guid] ?? null;
                if (!$res || $res instanceof \Throwable || $res->failed()) continue;

                $unitCost = $unitCostMap[$guid] ?? 0.0;

                foreach ($res->json()['Items'] ?? [] as $item) {
                    $wh  = $item['Warehouse'] ?? 'Unknown';
                    $qty = max(0.0, (float) ($item['AvailableQty'] ?? 0));
                    if ($qty <= 0) continue;
                    if (!isset($grouped[$wh])) {
                        $grouped[$wh] = ['totalCost' => 0.0, 'qty' => 0.0];
                    }
                    $grouped[$wh]['totalCost'] += $qty * $unitCost;
                    $grouped[$wh]['qty']       += $qty;
                }
            }
        }

        uasort($grouped, fn($a, $b) => $b['totalCost'] <=> $a['totalCost']);

        return $grouped;
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
}
