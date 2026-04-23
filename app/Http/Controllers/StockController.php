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
        $cutoff = now()->startOfMonth()->subMonths(11);

        $snapshots = StockSnapshot::where('snapshot_date', '>=', $cutoff)
            ->orderBy('snapshot_date')
            ->get();

        // Group by year-month — prefer the snapshot closest to the 1st of each month
        $byMonth = [];
        foreach ($snapshots as $snap) {
            $key = $snap->snapshot_date->format('Y-m');
            // Keep the snapshot nearest the start of the month
            if (!isset($byMonth[$key]) || $snap->snapshot_date->day < $byMonth[$key]->snapshot_date->day) {
                $byMonth[$key] = $snap;
            }
        }

        // Collect all warehouse names seen across all snapshots (preserve insertion order)
        $warehouseNames = [];
        foreach ($byMonth as $snap) {
            foreach (array_keys($snap->warehouse_data ?? []) as $name) {
                $warehouseNames[$name] = true;
            }
        }
        $warehouseNames = array_keys($warehouseNames);

        // Build chart labels and per-warehouse value arrays
        $chartLabels = [];
        $warehouseValues = array_fill_keys($warehouseNames, []); // name => [month values...]

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $key   = $month->format('Y-m');
            $chartLabels[] = $month->format('M Y');
            $snap = $byMonth[$key] ?? null;

            foreach ($warehouseNames as $name) {
                $warehouseValues[$name][] = $snap
                    ? (float) ($snap->warehouse_data[$name]['totalCost'] ?? 0)
                    : null;
            }
        }

        return view('stock.index', compact('chartLabels', 'warehouseValues'));
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
