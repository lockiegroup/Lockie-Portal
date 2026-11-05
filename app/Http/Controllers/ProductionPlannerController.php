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

        // Week keys are stored by the browser as the ISO date of Monday at midnight
        // local time converted to UTC — this can be Sunday in BST. Query a generous
        // window (from -7 days to to +1 day) and include any plan whose week overlaps.
        $plans = ProductionPlan::where('week_key', '>=', $from->copy()->subDays(7)->format('Y-m-d'))
            ->where('week_key', '<=', $to->copy()->addDay()->format('Y-m-d'))
            ->get();

        // Only include weeks where at least Monday–Friday falls within the range.
        // A week key may be Sunday (UTC representation of Monday midnight in BST),
        // so treat key date and key+1 as the possible Monday, and keep the week if
        // that Monday is on or before $to and the Friday (Monday+4) is on or after $from.
        $plans = $plans->filter(function ($plan) use ($from, $to) {
            $keyDate = Carbon::parse($plan->week_key);
            // The actual Monday is either the key date or key+1 (BST offset)
            $monday  = $keyDate->dayOfWeek === Carbon::MONDAY ? $keyDate : $keyDate->copy()->addDay();
            $friday  = $monday->copy()->addDays(4)->endOfDay();
            return $monday->lte($to) && $friday->gte($from);
        });

        $plans     = $plans->keyBy('week_key');
        $operators = ProductionOperator::where('is_active', true)->orderBy('sort_order')->get();
        $machines  = ProductionMachine::where('is_active', true)->orderBy('sort_order')->get()->keyBy('key');
        $divisions = ProductionDivision::orderBy('sort_order')->orderBy('id')->get();

        // Map day keys to Carbon day offsets from Monday (0=Mon … 4=Fri)
        $dayOffsets = ['mon' => 0, 'tue' => 1, 'wed' => 2, 'thu' => 3, 'fri' => 4];
        $shifts     = ['am', 'pm'];

        // totals[operator_id][division_name] = hours
        $totals = [];
        foreach ($operators as $op) {
            $totals[$op->id] = [];
            foreach ($divisions as $div) {
                $totals[$op->id][$div->name] = 0;
            }
        }

        foreach ($plans as $wk => $plan) {
            $assignments = $plan->data ?? [];

            // Resolve the actual Monday for this week key (key may be Sunday in BST)
            $keyDate = Carbon::parse($wk);
            $monday  = $keyDate->dayOfWeek === Carbon::MONDAY ? $keyDate->copy() : $keyDate->copy()->addDay();

            // Build the set of day keys that fall within the requested range
            $validDays = [];
            foreach ($dayOffsets as $dayKey => $offset) {
                $date = $monday->copy()->addDays($offset);
                if ($date->between($from, $to)) {
                    $validDays[] = $dayKey;
                }
            }

            if (empty($validDays)) continue;

            foreach ($operators as $op) {
                foreach ($validDays as $day) {
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
            'weeks'     => $plans->count(),
        ]);
    }
}
