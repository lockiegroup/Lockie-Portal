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
        $horizon      = (int) $request->input('horizon', session('cash_flow_horizon', 12));
        $viewMode     = $request->input('view', session('cash_flow_view', 'monthly'));
        $search       = trim($request->input('search', ''));
        $statusFilter = $request->input('status', 'all');
        $horizon      = max(1, min(36, $horizon));

        session(['cash_flow_horizon' => $horizon, 'cash_flow_view' => $viewMode]);

        $from = now()->startOfMonth();
        $to   = now()->addMonths($horizon - 1)->endOfMonth();

        // All entries for summary/daily (unfiltered so balance is always correct)
        $entries = CashFlowEntry::whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        // Filtered entries for the entries table only
        $filteredEntries = $entries->when($search !== '', function ($c) use ($search) {
            $lower = strtolower($search);
            return $c->filter(fn($e) =>
                str_contains(strtolower($e->description), $lower) ||
                str_contains(strtolower($e->category ?? ''), $lower) ||
                str_contains(strtolower($e->notes ?? ''), $lower)
            );
        })->when($statusFilter !== 'all', fn($c) => $c->where('status', $statusFilter));

        // Monthly summary (unfiltered)
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
                'label'        => $cursor->format('M Y'),
                'actual_in'    => $actualIn,
                'forecast_in'  => $forecastIn,
                'income'       => $actualIn + $forecastIn,
                'actual_out'   => $actualOut,
                'forecast_out' => $forecastOut,
                'expense'      => $actualOut + $forecastOut,
                'net'          => ($actualIn + $forecastIn) - ($actualOut + $forecastOut),
            ];
            $cursor->addMonth();
        }

        // Daily view: every calendar day in horizon, running balance from all entries
        $daily   = [];
        $balance = 0.0;
        $entryIndex = 0;
        $entryList  = $entries->values();
        $cursor     = $from->copy();

        while ($cursor->lte($to)) {
            $day     = $cursor->format('Y-m-d');
            $dayRows = [];

            while ($entryIndex < $entryList->count() && $entryList[$entryIndex]->entry_date->format('Y-m-d') === $day) {
                $e      = $entryList[$entryIndex];
                $signed = $e->type === 'income' ? (float) $e->amount : -(float) $e->amount;
                $balance += $signed;
                $dayRows[] = [
                    'entry_id'    => $e->id,
                    'description' => $e->description,
                    'category'    => $e->category,
                    'type'        => $e->type,
                    'status'      => $e->status,
                    'amount'      => (float) $e->amount,
                    'balance'     => $balance,
                ];
                $entryIndex++;
            }

            $daily[] = [
                'date'    => $day,
                'label'   => $cursor->format('d M Y'),
                'dow'     => $cursor->format('D'),
                'rows'    => $dayRows,
                'balance' => $balance,
                'empty'   => empty($dayRows),
            ];

            $cursor->addDay();
        }

        $categories = CashFlowEntry::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $queryParams = compact('horizon', 'search', 'statusFilter') + ['view' => $viewMode, 'status' => $statusFilter];

        return view('cash-flow.index', compact(
            'entries', 'filteredEntries', 'months', 'daily',
            'horizon', 'viewMode', 'search', 'statusFilter',
            'categories', 'queryParams'
        ));
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
