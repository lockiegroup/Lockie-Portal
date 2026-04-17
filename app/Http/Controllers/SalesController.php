<?php

namespace App\Http\Controllers;

use App\Services\UnleashedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SalesController extends Controller
{
    private UnleashedService $unleashed;

    public function __construct()
    {
        $this->unleashed = new UnleashedService(
            config('services.unleashed.id'),
            config('services.unleashed.key')
        );
    }

    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $error               = null;
        $salesByWarehouse    = [];
        $creditsByWarehouse  = [];
        $invoicesByWarehouse = [];

        try {
            $cacheKey = "unleashed_sales_{$from}_{$to}";

            [$salesByWarehouse, $creditsByWarehouse, $invoicesByWarehouse] = Cache::remember($cacheKey, 600, function () use ($from, $to) {
                $params = ['startDate' => $from, 'endDate' => $to];

                $allOrders   = $this->unleashed->paginate('SalesOrders', $params);
                $creditNotes = $this->unleashed->paginate('CreditNotes', $params);

                $invoiceStatuses = ['Dispatched', 'Complete', 'Invoiced'];

                // Exclude only Cancelled — Unleashed never returns deleted orders via API
                $salesOrders = array_filter($allOrders, fn($o) => ($o['OrderStatus'] ?? '') !== 'Cancelled');
                $invoices    = array_filter($allOrders, fn($o) => in_array($o['OrderStatus'] ?? '', $invoiceStatuses));

                return [
                    $this->groupByWarehouse($salesOrders),
                    $this->groupByWarehouse($creditNotes),
                    $this->groupByWarehouse($invoices),
                ];
            });
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return view('sales.index', compact(
            'from', 'to', 'error',
            'salesByWarehouse', 'creditsByWarehouse', 'invoicesByWarehouse'
        ));
    }

    private function groupByWarehouse(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $name = $item['Warehouse']['WarehouseName'] ?? 'No Warehouse';

            if (!isset($grouped[$name])) {
                $grouped[$name] = ['count' => 0, 'sub' => 0.0, 'tax' => 0.0, 'total' => 0.0];
            }

            $grouped[$name]['count']++;
            $grouped[$name]['sub']   += (float) ($item['SubTotal'] ?? 0);
            $grouped[$name]['tax']   += (float) ($item['TaxTotal'] ?? 0);
            $grouped[$name]['total'] += (float) ($item['Total'] ?? 0);
        }

        uasort($grouped, fn($a, $b) => $b['total'] <=> $a['total']);

        return $grouped;
    }
}
