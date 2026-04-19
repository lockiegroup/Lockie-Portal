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
        $cacheKey = 'unleashed_stock_on_hand_v4';

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        try {
            $stockByWarehouse = Cache::remember($cacheKey, 1800, function () {
                return $this->unleashed->fetchStockByWarehouse();
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
