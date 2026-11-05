<?php

namespace App\Http\Controllers;

use App\Models\OrderBoardNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderBoardController extends Controller
{
    const STATUSES = [
        'proforma'    => ['label' => 'Proforma',         'color' => '#a78bfa'],
        'awaiting'    => ['label' => 'Awaiting Info',    'color' => '#fb923c'],
        'in_production' => ['label' => 'In Production',  'color' => '#38bdf8'],
        'ready'       => ['label' => 'Ready to Dispatch','color' => '#34d399'],
        'on_hold'     => ['label' => 'On Hold',          'color' => '#f87171'],
    ];

    public function index()
    {
        return view('order-board.index', ['statuses' => self::STATUSES]);
    }

    public function data(Request $request): JsonResponse
    {
        $warehouse    = $request->input('warehouse', 'JW Products');
        $filterStatus = $request->input('status', '');

        // Aggregate open orders from sales_lines
        $rows = DB::table('sales_lines')
            ->select(
                'order_no',
                DB::raw('MIN(order_date) as order_date'),
                DB::raw('MIN(required_date) as required_date'),
                DB::raw('MIN(customer_code) as customer_code'),
                DB::raw('MIN(customer) as customer'),
                DB::raw('MIN(status) as unleashed_status'),
                DB::raw('SUM(sub_total) as total_value'),
                DB::raw('COUNT(*) as line_count')
            )
            ->where('warehouse', $warehouse)
            ->whereNotIn('status', ['deleted', 'completed'])
            ->whereNotNull('order_no')
            ->groupBy('order_no')
            ->orderBy(DB::raw('MIN(required_date)'))
            ->get();

        // Load all board notes for these order numbers
        $orderNos = $rows->pluck('order_no')->filter()->values();
        $notes    = OrderBoardNote::whereIn('order_no', $orderNos)
            ->get()
            ->keyBy('order_no');

        $orders = $rows->map(function ($row) use ($notes) {
            $note = $notes->get($row->order_no);
            return [
                'order_no'        => $row->order_no,
                'customer'        => $row->customer,
                'customer_code'   => $row->customer_code,
                'order_date'      => $row->order_date,
                'required_date'   => $row->required_date,
                'unleashed_status'=> $row->unleashed_status,
                'total_value'     => round((float) $row->total_value, 2),
                'line_count'      => (int) $row->line_count,
                'board_status'    => $note?->board_status ?? null,
                'notes'           => $note?->notes ?? '',
                'updated_by'      => $note?->updatedBy?->name ?? null,
                'updated_at'      => $note?->updated_at?->diffForHumans() ?? null,
            ];
        });

        // Filter by board status if requested
        if ($filterStatus) {
            $orders = $orders->filter(fn($o) => $o['board_status'] === $filterStatus)->values();
        }

        return response()->json([
            'ok'       => true,
            'orders'   => $orders->values(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, string $orderNo): JsonResponse
    {
        $request->validate([
            'board_status' => 'nullable|string|max:50',
            'notes'        => 'nullable|string|max:2000',
        ]);

        OrderBoardNote::updateOrCreate(
            ['order_no' => $orderNo],
            [
                'board_status' => $request->input('board_status') ?: null,
                'notes'        => $request->input('notes') ?: null,
                'updated_by'   => auth()->id(),
            ]
        );

        return response()->json(['ok' => true]);
    }
}
