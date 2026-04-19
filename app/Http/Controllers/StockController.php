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
                // Fetch all stock without filters so pagination is accurate
                // (filtered queries trigger the Unleashed NumberOfPages bug).
                $items = $this->unleashed->paginate('StockOnHand');

                $grouped = [];
                foreach ($items as $item) {
                    $name = $item['Warehouse'] ?? 'Unknown';
                    if (!isset($grouped[$name])) {
                        $grouped[$name] = ['totalCost' => 0.0, 'qty' => 0.0];
                    }
                    $grouped[$name]['totalCost'] += (float) ($item['TotalCost'] ?? 0);
                    $grouped[$name]['qty']       += (float) ($item['QtyOnHand'] ?? 0);
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
