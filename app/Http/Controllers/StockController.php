<?php

namespace App\Http\Controllers;

use App\Services\UnleashedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StockController extends Controller
{
    private UnleashedService $unleashed;

    public function __construct()
    {
        $this->unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key')
        );
    }

    public function data(Request $request): JsonResponse
    {
        $cacheKey = 'unleashed_stock_on_hand';

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        try {
            $stockByWarehouse = Cache::remember($cacheKey, 1800, function () {
                // Unfiltered /StockOnHand returns aggregated "Global" data with empty
                // warehouse fields. Fetch warehouses first, then query per warehouse.
                // parallelPaginate's Guid-dedup handles the Unleashed pagination bug
                // that affects any filtered query.
                $whItems = ($this->unleashed->get('Warehouses', [
                    'pageSize' => 200, 'pageNumber' => 1,
                ]))['Items'] ?? [];

                $nameMap  = [];
                $requests = [];
                foreach ($whItems as $wh) {
                    $code = $wh['WarehouseCode'] ?? '';
                    if (!$code) continue;
                    $nameMap[$code]  = $wh['WarehouseName'] ?? $code;
                    $requests[$code] = ['StockOnHand', ['warehouseCode' => $code]];
                }

                $fetched = $this->unleashed->parallelPaginate($requests);

                $grouped = [];
                foreach ($fetched as $code => $items) {
                    $totalCost = 0.0;
                    $totalQty  = 0.0;
                    foreach ($items as $item) {
                        $totalCost += (float) ($item['TotalCost'] ?? 0);
                        $totalQty  += (float) ($item['QtyOnHand'] ?? 0);
                    }
                    $name = $nameMap[$code] ?? $code;
                    $grouped[$name] = ['totalCost' => $totalCost, 'qty' => $totalQty];
                }

                uasort($grouped, fn($a, $b) => $b['totalCost'] <=> $a['totalCost']);

                return $grouped;
            });

            return response()->json(['success' => true, 'stockByWarehouse' => $stockByWarehouse]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => get_class($e) . ': ' . $e->getMessage(),
            ], 500);
        }
    }
}
