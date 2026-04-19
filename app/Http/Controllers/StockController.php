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
        $cacheKey = 'unleashed_stock_on_hand_v3';

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        try {
            $stockByWarehouse = Cache::remember($cacheKey, 1800, function () {
                // Step 1: fetch all stock unfiltered — gives global TotalCost per product
                $allItems = $this->unleashed->paginate('StockOnHand');

                // Step 2: build unit cost map (guid → cost per unit)
                // AllWarehouses endpoint only returns AvailableQty, not TotalCost,
                // so we derive cost as unitCost × warehouseQty
                $unitCostMap = [];
                foreach ($allItems as $item) {
                    $guid      = $item['Guid'] ?? null;
                    $totalCost = (float) ($item['TotalCost'] ?? 0);
                    $globalQty = (float) ($item['QtyOnHand'] ?? 0);
                    if (!$guid || $totalCost <= 0) continue;
                    $unitCostMap[$guid] = $globalQty > 0 ? $totalCost / $globalQty : 0.0;
                }

                // Step 3: call /StockOnHand/{guid}/AllWarehouses for each product
                // and distribute cost proportionally by warehouse AvailableQty
                return $this->unleashed->fetchStockAllWarehouses($unitCostMap);
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
