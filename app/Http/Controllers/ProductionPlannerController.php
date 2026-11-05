<?php

namespace App\Http\Controllers;

use App\Models\ProductionDivision;
use App\Models\ProductionMachine;
use App\Models\ProductionOperator;
use App\Models\ProductionPlan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
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

    public function summary(Request $request): JsonResponse
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : now()->endOfMonth();

        // Collect all Monday week keys that fall within the range
        $weekKeys = [];
        $cursor   = $from->copy()->startOfWeek(Carbon::MONDAY);
        while ($cursor->lte($to)) {
            $weekKeys[] = $cursor->format('Y-m-d');
            $cursor->addWeek();
        }

        $plans     = ProductionPlan::whereIn('week_key', $weekKeys)->get()->keyBy('week_key');
        $operators = ProductionOperator::where('is_active', true)->orderBy('sort_order')->get();
        $machines  = ProductionMachine::where('is_active', true)->orderBy('sort_order')->get()->keyBy('key');
        $divisions = ProductionDivision::orderBy('sort_order')->orderBy('id')->get();

        $days   = ['mon', 'tue', 'wed', 'thu', 'fri'];
        $shifts = ['am', 'pm'];

        // totals[operator_id][division_name] = hours
        $totals = [];
        foreach ($operators as $op) {
            $totals[$op->id] = [];
            foreach ($divisions as $div) {
                $totals[$op->id][$div->name] = 0;
            }
        }

        foreach ($weekKeys as $wk) {
            $plan        = $plans->get($wk);
            $assignments = $plan ? ($plan->data ?? []) : [];

            foreach ($operators as $op) {
                foreach ($days as $day) {
                    foreach ($shifts as $shift) {
                        $cell = $assignments[$op->id][$day][$shift] ?? [];
                        foreach ([['m', 'h'], ['m2', 'h2']] as [$mk, $hk]) {
                            $machineKey = $cell[$mk] ?? null;
                            $hrs        = (float) ($cell[$hk] ?? 0);
                            if (!$machineKey || $hrs <= 0) continue;
                            $machine = $machines->get($machineKey);
                            if ($machine && isset($totals[$op->id][$machine->division])) {
                                $totals[$op->id][$machine->division] += $hrs;
                            }
                        }
                    }
                }
            }
        }

        return response()->json([
            'ok'        => true,
            'operators' => $operators->values(),
            'divisions' => $divisions->values(),
            'totals'    => $totals,
            'weeks'     => count($weekKeys),
        ]);
    }
}
