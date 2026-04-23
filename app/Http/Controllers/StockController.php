<?php

namespace App\Http\Controllers;

use App\Models\StockSnapshot;
use App\Services\UnleashedService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

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

    public function index(): View
    {
        // Build rolling 12-month chart data (one point per month, last snapshot of each month)
        $cutoff = now()->startOfMonth()->subMonths(11);

        $snapshots = StockSnapshot::where('snapshot_date', '>=', $cutoff)
            ->orderBy('snapshot_date')
            ->get();

        // Group by year-month, take last entry per month
        $byMonth = [];
        foreach ($snapshots as $snap) {
            $key = $snap->snapshot_date->format('Y-m');
            $byMonth[$key] = $snap->total_value;
        }

        // Build ordered labels + values for the last 12 months
        $chartLabels = [];
        $chartValues = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $key   = $month->format('Y-m');
            $chartLabels[] = $month->format('M Y');
            $chartValues[] = $byMonth[$key] ?? null;
        }

        return view('stock.index', compact('chartLabels', 'chartValues'));
    }

    public function data(Request $request): JsonResponse
    {
        $cacheKey = 'unleashed_stock_on_hand_v5';

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        try {
            $fresh = false;
            $stockByWarehouse = Cache::remember($cacheKey, 1800, function () use (&$fresh) {
                $fresh = true;
                return $this->unleashed->fetchStockByWarehouse();
            });

            // Save a snapshot whenever fresh data is fetched (once per day)
            if ($fresh) {
                $total = collect($stockByWarehouse)->sum('totalCost');
                StockSnapshot::updateOrCreate(
                    ['snapshot_date' => now()->toDateString()],
                    ['total_value' => $total, 'warehouse_data' => $stockByWarehouse]
                );
            }

            return response()->json(['success' => true, 'stockByWarehouse' => $stockByWarehouse]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => get_class($e) . ': ' . $e->getMessage(),
            ], 500);
        }
    }
}
