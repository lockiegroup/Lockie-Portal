<?php

namespace App\Http\Controllers;

use App\Models\CashFlowEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashFlowController extends Controller
{
    public function index(Request $request): View
    {
        $horizon  = (int) $request->input('horizon', session('cash_flow_horizon', 12));
        $viewMode = $request->input('view', session('cash_flow_view', 'monthly'));
        $horizon  = max(1, min(36, $horizon));

        session(['cash_flow_horizon' => $horizon, 'cash_flow_view' => $viewMode]);

        $from = now()->startOfMonth();
        $to   = now()->addMonths($horizon - 1)->endOfMonth();

        $entries = CashFlowEntry::whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        // Monthly summary
        $months = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key   = $cursor->format('Y-m');
            $slice = $entries->filter(fn($e) => $e->entry_date->format('Y-m') === $key);

            $actualIn    = (float) $slice->where('type', 'income')->where('status', 'actual')->sum('amount');
            $forecastIn  = (float) $slice->where('type', 'income')->where('status', 'forecast')->sum('amount');
            $actualOut   = (float) $slice->where('type', 'expense')->where('status', 'actual')->sum('amount');
            $forecastOut = (float) $slice->where('type', 'expense')->where('status', 'forecast')->sum('amount');

            $months[$key] = [
                'label'       => $cursor->format('M Y'),
                'actual_in'   => $actualIn,
                'forecast_in' => $forecastIn,
                'income'      => $actualIn + $forecastIn,
                'actual_out'  => $actualOut,
                'forecast_out'=> $forecastOut,
                'expense'     => $actualOut + $forecastOut,
                'net'         => ($actualIn + $forecastIn) - ($actualOut + $forecastOut),
            ];
            $cursor->addMonth();
        }

        // Daily running balance (all entries in horizon, chronological)
        $daily   = [];
        $balance = 0.0;
        foreach ($entries as $entry) {
            $signed  = $entry->type === 'income' ? (float) $entry->amount : -(float) $entry->amount;
            $balance += $signed;
            $daily[] = [
                'date'        => $entry->entry_date->format('Y-m-d'),
                'label'       => $entry->entry_date->format('d M Y'),
                'description' => $entry->description,
                'category'    => $entry->category,
                'type'        => $entry->type,
                'status'      => $entry->status,
                'amount'      => (float) $entry->amount,
                'balance'     => $balance,
                'entry_id'    => $entry->id,
            ];
        }

        $categories = CashFlowEntry::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('cash-flow.index', compact('entries', 'months', 'daily', 'horizon', 'viewMode', 'categories'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entry_date'  => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:income,expense'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'status'      => ['required', 'in:forecast,actual'],
            'category'    => ['nullable', 'string', 'max:100'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $entry = CashFlowEntry::create($data);

        return response()->json(['success' => true, 'id' => $entry->id]);
    }

    public function update(Request $request, CashFlowEntry $entry): JsonResponse
    {
        $data = $request->validate([
            'entry_date'  => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:income,expense'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'status'      => ['required', 'in:forecast,actual'],
            'category'    => ['nullable', 'string', 'max:100'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $entry->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy(CashFlowEntry $entry): JsonResponse
    {
        $entry->delete();

        return response()->json(['success' => true]);
    }
}
