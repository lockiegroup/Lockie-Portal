<?php

namespace App\Http\Controllers;

use App\Models\CashFlowCategory;
use App\Models\CashFlowEntry;
use App\Models\CashFlowSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashFlowController extends Controller
{
    public function index(Request $request): View
    {
        $horizon      = (int) $request->input('horizon', session('cf_horizon_weeks', 13));
        $horizon      = max(4, min(52, $horizon));
        $search       = trim($request->input('search', ''));
        $statusFilter = $request->input('status', 'all');
        session(['cf_horizon_weeks' => $horizon]);

        // Build week columns (starting this Monday)
        $weeks    = [];
        $cursor   = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        for ($i = 0; $i < $horizon; $i++) {
            $weeks[]  = $cursor->copy();
            $cursor->addWeek();
        }
        $weekKeys = array_map(fn($w) => $w->toDateString(), $weeks);
        $from     = $weekKeys[0];
        $to       = Carbon::parse($weekKeys[count($weekKeys) - 1])->endOfWeek()->toDateString();

        $categories        = CashFlowCategory::orderBy('type')->orderBy('sort_order')->orderBy('id')->get();
        $incomeCategories  = $categories->where('type', 'income')->values();
        $expenseCategories = $categories->where('type', 'expense')->values();

        // All entries in period — used for both spreadsheet aggregation and the entries list
        $allEntries = CashFlowEntry::with('category')
            ->whereBetween('entry_date', [$from, $to])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        // Build spreadsheet matrix: [category_id][week_start] => [amount, has_forecast, has_actual]
        $matrix = [];
        foreach ($allEntries as $entry) {
            if (!$entry->category_id) continue;
            $wk = Carbon::parse($entry->entry_date)->startOfWeek(Carbon::MONDAY)->toDateString();
            if (!in_array($wk, $weekKeys)) continue;
            if (!isset($matrix[$entry->category_id][$wk])) {
                $matrix[$entry->category_id][$wk] = ['amount' => 0.0, 'has_forecast' => false, 'has_actual' => false];
            }
            $matrix[$entry->category_id][$wk]['amount'] += (float) $entry->amount;
            if ($entry->status === 'forecast') $matrix[$entry->category_id][$wk]['has_forecast'] = true;
            if ($entry->status === 'actual')   $matrix[$entry->category_id][$wk]['has_actual']   = true;
        }

        // Cascading weekly totals and balances
        $openingBalance = (float) CashFlowSetting::getValue('opening_balance', '0');
        $weeklyCalc     = [];
        $runningBalance = $openingBalance;
        foreach ($weekKeys as $wd) {
            $incomeTotal  = 0.0;
            $expenseTotal = 0.0;
            foreach ($incomeCategories as $cat) {
                $incomeTotal  += $matrix[$cat->id][$wd]['amount'] ?? 0.0;
            }
            foreach ($expenseCategories as $cat) {
                $expenseTotal += $matrix[$cat->id][$wd]['amount'] ?? 0.0;
            }
            $closing = $runningBalance + $incomeTotal - $expenseTotal;
            $weeklyCalc[$wd] = [
                'opening'  => $runningBalance,
                'income'   => $incomeTotal,
                'expenses' => $expenseTotal,
                'net'      => $incomeTotal - $expenseTotal,
                'closing'  => $closing,
            ];
            $runningBalance = $closing;
        }

        // Filtered entries for the detail section
        $filteredEntries = $allEntries
            ->when($search !== '', function ($c) use ($search) {
                $lower = strtolower($search);
                return $c->filter(fn($e) =>
                    str_contains(strtolower($e->description), $lower) ||
                    str_contains(strtolower($e->category?->name ?? ''), $lower) ||
                    str_contains(strtolower($e->notes ?? ''), $lower)
                );
            })
            ->when($statusFilter !== 'all', fn($c) => $c->where('status', $statusFilter));

        return view('cash-flow.index', compact(
            'weeks', 'weekKeys', 'categories',
            'incomeCategories', 'expenseCategories',
            'matrix', 'weeklyCalc', 'openingBalance', 'horizon',
            'allEntries', 'filteredEntries', 'search', 'statusFilter'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:cash_flow_categories,id'],
            'entry_date'  => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:income,expense'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'status'      => ['required', 'in:forecast,actual'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        // Auto-set type from category if provided
        if (!empty($data['category_id'])) {
            $cat = CashFlowCategory::find($data['category_id']);
            if ($cat) $data['type'] = $cat->type;
        }

        $entry = CashFlowEntry::create($data);

        \App\Models\ActivityLog::record('cashflow.entry_add', "Added cash flow entry: {$entry->description} (£" . number_format((float) $entry->amount, 2) . ')');

        return response()->json(['success' => true, 'id' => $entry->id]);
    }

    public function update(Request $request, CashFlowEntry $entry): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:cash_flow_categories,id'],
            'entry_date'  => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:income,expense'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'status'      => ['required', 'in:forecast,actual'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        if (!empty($data['category_id'])) {
            $cat = CashFlowCategory::find($data['category_id']);
            if ($cat) $data['type'] = $cat->type;
        }

        $entry->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy(CashFlowEntry $entry): JsonResponse
    {
        \App\Models\ActivityLog::record('cashflow.entry_delete', "Deleted cash flow entry: {$entry->description}");

        $entry->delete();
        return response()->json(['success' => true]);
    }

    public function updateOpeningBalance(Request $request): JsonResponse
    {
        $request->validate(['opening_balance' => ['required', 'numeric', 'min:0']]);
        CashFlowSetting::setValue('opening_balance', $request->input('opening_balance'));
        return response()->json(['success' => true]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:income,expense'],
        ]);
        $data['sort_order'] = CashFlowCategory::where('type', $data['type'])->max('sort_order') + 1;
        $cat = CashFlowCategory::create($data);
        return response()->json(['success' => true, 'id' => $cat->id, 'name' => $cat->name, 'type' => $cat->type]);
    }

    public function destroyCategory(CashFlowCategory $category): JsonResponse
    {
        $category->delete();
        return response()->json(['success' => true]);
    }
}
