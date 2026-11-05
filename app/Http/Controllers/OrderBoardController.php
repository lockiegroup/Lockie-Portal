<?php

namespace App\Http\Controllers;

use App\Models\OrderBoardNote;
use App\Models\OrderBoardStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderBoardController extends Controller
{
    private function statuses(): \Illuminate\Database\Eloquent\Collection
    {
        return OrderBoardStatus::orderBy('sort_order')->orderBy('id')->get();
    }

    private function statusesMap(): array
    {
        return $this->statuses()->keyBy('key')->map(fn($s) => [
            'label' => $s->label,
            'color' => $s->color,
        ])->toArray();
    }

    public function index()
    {
        return view('order-board.index', ['statuses' => $this->statusesMap()]);
    }

    public function data(Request $request): JsonResponse
    {
        $warehouse    = $request->input('warehouse', 'JW Products');
        $filterStatus = $request->input('status', '');
        $statuses     = $this->statusesMap();

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

        $orderNos = $rows->pluck('order_no')->filter()->values();
        $notes    = OrderBoardNote::whereIn('order_no', $orderNos)->get()->keyBy('order_no');

        $orders = $rows->map(function ($row) use ($notes) {
            $note = $notes->get($row->order_no);
            return [
                'order_no'         => $row->order_no,
                'customer'         => $row->customer,
                'customer_code'    => $row->customer_code,
                'order_date'       => $row->order_date,
                'required_date'    => $row->required_date,
                'unleashed_status' => $row->unleashed_status,
                'total_value'      => round((float) $row->total_value, 2),
                'line_count'       => (int) $row->line_count,
                'board_status'     => $note?->board_status ?? null,
                'notes'            => $note?->notes ?? '',
                'updated_by'       => $note?->updatedBy?->name ?? null,
                'updated_at'       => $note?->updated_at?->diffForHumans() ?? null,
            ];
        });

        if ($filterStatus) {
            $orders = $orders->filter(fn($o) => $o['board_status'] === $filterStatus)->values();
        }

        return response()->json([
            'ok'       => true,
            'orders'   => $orders->values(),
            'statuses' => $statuses,
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

    // ── Settings ──────────────────────────────────────────────────────────────

    public function settings()
    {
        return view('order-board.settings', ['statuses' => $this->statuses()]);
    }

    public function statusStore(Request $request): JsonResponse
    {
        $request->validate([
            'label' => 'required|string|max:100',
            'color' => 'required|string|max:20',
        ]);

        $key = Str::slug($request->label, '_');
        // Ensure uniqueness
        $base = $key;
        $i    = 2;
        while (OrderBoardStatus::where('key', $key)->exists()) {
            $key = $base . '_' . $i++;
        }

        $maxOrder = OrderBoardStatus::max('sort_order') ?? 0;

        $status = OrderBoardStatus::create([
            'key'        => $key,
            'label'      => $request->label,
            'color'      => $request->color,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json(['ok' => true, 'status' => $status]);
    }

    public function statusUpdate(Request $request, OrderBoardStatus $status): JsonResponse
    {
        $request->validate([
            'label' => 'required|string|max:100',
            'color' => 'required|string|max:20',
        ]);

        $status->update([
            'label' => $request->label,
            'color' => $request->color,
        ]);

        return response()->json(['ok' => true]);
    }

    public function statusDestroy(OrderBoardStatus $status): JsonResponse
    {
        // Null out any notes using this status
        OrderBoardNote::where('board_status', $status->key)->update(['board_status' => null]);
        $status->delete();

        return response()->json(['ok' => true]);
    }

    public function statusReorder(Request $request): JsonResponse
    {
        $request->validate(['order' => 'required|array']);

        foreach ($request->order as $i => $id) {
            OrderBoardStatus::where('id', $id)->update(['sort_order' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }
}
