<?php

namespace App\Http\Controllers;

use App\Models\CashFlowEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashFlowController extends Controller
{
    public function index(): View
    {
        $horizon = (int) session('cash_flow_horizon', 12);
        $from    = now()->startOfMonth();
        $to      = now()->addMonths($horizon - 1)->endOfMonth();

        $entries = CashFlowEntry::whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $months = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key   = $cursor->format('Y-m');
            $slice = $entries->filter(fn($e) => $e->entry_date->format('Y-m') === $key);

            $income  = (float) $slice->where('type', 'income')->sum('amount');
            $expense = (float) $slice->where('type', 'expense')->sum('amount');

            $months[$key] = [
                'label'   => $cursor->format('M Y'),
                'income'  => $income,
                'expense' => $expense,
                'net'     => $income - $expense,
            ];
            $cursor->addMonth();
        }

        $categories = CashFlowEntry::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('cash-flow.index', compact('entries', 'months', 'horizon', 'categories'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entry_date'  => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:income,expense'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
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

    public function horizon(Request $request): RedirectResponse
    {
        $months = (int) $request->input('horizon', 12);
        session(['cash_flow_horizon' => max(1, min(36, $months))]);

        return redirect()->route('cash-flow.index');
    }
}
