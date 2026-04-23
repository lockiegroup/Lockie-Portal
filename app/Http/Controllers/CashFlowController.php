<?php

namespace App\Http\Controllers;

use App\Models\CashFlowCategory;
use App\Models\CashFlowSetting;
use App\Models\CashFlowWeekly;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashFlowController extends Controller
{
    public function index(Request $request): View
    {
        $horizon = (int) $request->input('horizon', session('cf_horizon_weeks', 13));
        $horizon = max(4, min(52, $horizon));
        session(['cf_horizon_weeks' => $horizon]);

        // Build week columns (always start from this Monday)
        $weeks  = [];
        $cursor = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        for ($i = 0; $i < $horizon; $i++) {
            $weeks[] = $cursor->copy();
            $cursor->addWeek();
        }

        $weekKeys = array_map(fn($w) => $w->toDateString(), $weeks);

        $categories = CashFlowCategory::orderBy('type')->orderBy('sort_order')->orderBy('id')->get();

        // Load all weekly entries for this period keyed by [category_id][week_start]
        $raw = CashFlowWeekly::whereBetween('week_start', [$weekKeys[0], $weekKeys[count($weekKeys) - 1]])
            ->get();

        $matrix = [];
        foreach ($raw as $entry) {
            $matrix[$entry->category_id][$entry->week_start->toDateString()] = $entry;
        }

        $openingBalance   = (float) CashFlowSetting::getValue('opening_balance', '0');
        $incomeCategories = $categories->where('type', 'income')->values();
        $expenseCategories = $categories->where('type', 'expense')->values();

        // Weekly totals and cascading balances
        $weeklyCalc    = [];
        $runningBalance = $openingBalance;
        foreach ($weekKeys as $wd) {
            $incomeTotal  = 0.0;
            $expenseTotal = 0.0;
            foreach ($incomeCategories as $cat) {
                $incomeTotal += isset($matrix[$cat->id][$wd]) ? (float) $matrix[$cat->id][$wd]->amount : 0.0;
            }
            foreach ($expenseCategories as $cat) {
                $expenseTotal += isset($matrix[$cat->id][$wd]) ? (float) $matrix[$cat->id][$wd]->amount : 0.0;
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

        return view('cash-flow.index', compact(
            'weeks', 'weekKeys', 'categories',
            'incomeCategories', 'expenseCategories',
            'matrix', 'weeklyCalc', 'openingBalance', 'horizon'
        ));
    }

    public function updateCell(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:cash_flow_categories,id'],
            'week_start'  => ['required', 'date'],
            'amount'      => ['nullable', 'numeric', 'min:0'],
            'status'      => ['required', 'in:forecast,actual'],
        ]);

        $amount = isset($data['amount']) && $data['amount'] !== '' ? (float) $data['amount'] : null;

        if ($amount === null || $amount == 0) {
            CashFlowWeekly::where('category_id', $data['category_id'])
                ->where('week_start', $data['week_start'])
                ->delete();
            return response()->json(['success' => true, 'amount' => null]);
        }

        CashFlowWeekly::updateOrCreate(
            ['category_id' => $data['category_id'], 'week_start' => $data['week_start']],
            ['amount' => $amount, 'status' => $data['status']]
        );

        return response()->json(['success' => true, 'amount' => $amount, 'status' => $data['status']]);
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
