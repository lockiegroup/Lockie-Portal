<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        return view('sales.index', compact('from', 'to'));
    }

    public function data(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        try {
            $salesRows = DB::table('sales_lines')
                ->whereBetween('order_date', [$from, $to])
                ->where('status', '!=', 'deleted')
                ->select('warehouse', DB::raw('COUNT(DISTINCT order_no) as order_count'), DB::raw('SUM(sub_total) as sub_total'))
                ->groupBy('warehouse')
                ->get();

            $salesByWarehouse = [];
            foreach ($salesRows as $row) {
                $name = $row->warehouse ?: 'No Warehouse';
                $salesByWarehouse[$name] = [
                    'count' => (int) $row->order_count,
                    'sub'   => (float) $row->sub_total,
                    'tax'   => 0.0,
                    'total' => (float) $row->sub_total,
                ];
            }
            arsort($salesByWarehouse);

            $creditsRows = DB::table('credits_lines')
                ->whereBetween('credit_date', [$from, $to])
                ->select('warehouse', DB::raw('COUNT(*) as line_count'), DB::raw('SUM(sub_total) as sub_total'))
                ->groupBy('warehouse')
                ->get();

            $creditsByWarehouse = [];
            foreach ($creditsRows as $row) {
                $name = $row->warehouse ?: 'No Warehouse';
                $creditsByWarehouse[$name] = [
                    'count' => (int) $row->line_count,
                    'sub'   => (float) $row->sub_total,
                    'tax'   => 0.0,
                    'total' => (float) $row->sub_total,
                ];
            }
            arsort($creditsByWarehouse);

            $invoiceRows = DB::table('invoice_lines')
                ->whereBetween('invoice_date', [$from, $to])
                ->where('status', '!=', 'deleted')
                ->select('warehouse', DB::raw('COUNT(DISTINCT invoice_no) as invoice_count'), DB::raw('SUM(sub_total) as sub_total'))
                ->groupBy('warehouse')
                ->get();

            $invoicesByWarehouse = [];
            foreach ($invoiceRows as $row) {
                $name = $row->warehouse ?: 'No Warehouse';
                $invoicesByWarehouse[$name] = [
                    'count' => (int) $row->invoice_count,
                    'sub'   => (float) $row->sub_total,
                    'tax'   => 0.0,
                    'total' => (float) $row->sub_total,
                ];
            }
            arsort($invoicesByWarehouse);

            $totalOrders   = DB::table('sales_lines')->whereBetween('order_date', [$from, $to])->where('status', '!=', 'deleted')->distinct()->count('order_no');
            $totalCredits  = DB::table('credits_lines')->whereBetween('credit_date', [$from, $to])->count();
            $totalInvoices = DB::table('invoice_lines')->whereBetween('invoice_date', [$from, $to])->where('status', '!=', 'deleted')->distinct()->count('invoice_no');

            return response()->json([
                'success'              => true,
                'salesByWarehouse'     => $salesByWarehouse,
                'creditsByWarehouse'   => $creditsByWarehouse,
                'invoicesByWarehouse'  => $invoicesByWarehouse,
                'counts'               => [
                    'sales'    => $totalOrders,
                    'credits'  => $totalCredits,
                    'invoices' => $totalInvoices,
                ],
                'debug' => [
                    'source' => 'sales_lines',
                    'from'   => $from,
                    'to'     => $to,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => get_class($e) . ': ' . $e->getMessage(),
            ], 500);
        }
    }
}
