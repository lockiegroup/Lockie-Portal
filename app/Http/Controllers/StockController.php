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
                // Step 1: fetch all stock unfiltered (no pagination bug — correct page count)
                $allItems = $this->unleashed->paginate('StockOnHand');

                // Step 2: collect ProductGuids for items with non-zero TotalCost only
                // (zero-cost items contribute nothing to warehouse values)
                $guids = [];
                foreach ($allItems as $item) {
                    if ((float) ($item['TotalCost'] ?? 0) > 0) {
                        $guid = $item['ProductGuid'] ?? $item['Guid'] ?? null;
                        if ($guid) $guids[] = $guid;
                    }
                }

                // Step 3: call /StockOnHand/{guid}/AllWarehouses for each product
                // in parallel batches — this is the only API endpoint that returns
                // accurate per-warehouse breakdown (per Unleashed support)
                return $this->unleashed->fetchStockAllWarehouses(array_unique($guids));
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
