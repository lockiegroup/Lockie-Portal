<?php

namespace App\Http\Controllers;

use App\Models\ProductionDivision;
use App\Models\ProductionMachine;
use App\Models\ProductionOperator;
use App\Models\ProductionPlan;
use Illuminate\Http\Request;

class ProductionPlannerController extends Controller
{
    public function index()
    {
        $operators = ProductionOperator::where('is_active', true)->orderBy('sort_order')->get();
        $machines  = ProductionMachine::where('is_active', true)->orderBy('sort_order')->get();
        $divisions = ProductionDivision::orderBy('sort_order')->orderBy('id')->get();

        return view('production-planner.index', compact('operators', 'machines', 'divisions'));
    }

    public function loadWeek(string $weekKey)
    {
        $plan = ProductionPlan::firstOrCreate(['week_key' => $weekKey]);

        return response()->json(['assignments' => $plan->data ?? []]);
    }

    public function saveWeek(Request $request, string $weekKey)
    {
        ProductionPlan::updateOrCreate(
            ['week_key' => $weekKey],
            ['data' => $request->input('assignments', [])]
        );

        return response()->json(['ok' => true]);
    }
}
