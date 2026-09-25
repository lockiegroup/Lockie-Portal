<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionDivision;
use App\Models\ProductionMachine;
use App\Models\ProductionOperator;
use Illuminate\Http\Request;

class ProductionSettingsController extends Controller
{
    public function index()
    {
        $operators = ProductionOperator::orderBy('sort_order')->orderBy('id')->get();
        $machines  = ProductionMachine::orderBy('sort_order')->orderBy('id')->get();
        $divisions = ProductionDivision::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.production.index', compact('operators', 'machines', 'divisions'));
    }

    private const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri'];

    private function buildSchedule(Request $request): array
    {
        $schedule = [];
        foreach (self::DAYS as $day) {
            $schedule[$day] = [
                'am' => (float) $request->input("schedule_{$day}_am", 4),
                'pm' => (float) $request->input("schedule_{$day}_pm", 4),
            ];
        }
        return $schedule;
    }

    // ── Operators ────────────────────────────────────────────────────────

    public function storeOperator(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        foreach (self::DAYS as $day) {
            $request->validate([
                "schedule_{$day}_am" => ['required', 'numeric', 'min:0', 'max:12'],
                "schedule_{$day}_pm" => ['required', 'numeric', 'min:0', 'max:12'],
            ]);
        }

        $maxOrder = ProductionOperator::max('sort_order') ?? 0;
        ProductionOperator::create([
            'name'       => $data['name'],
            'schedule'   => $this->buildSchedule($request),
            'sort_order' => $maxOrder + 1,
            'is_active'  => true,
        ]);

        return redirect()->route('admin.production.index')->with('success', 'Operator added.');
    }

    public function updateOperator(Request $request, ProductionOperator $operator)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $operator->update([
            'name'      => $data['name'],
            'schedule'  => $this->buildSchedule($request),
            'is_active' => $request->boolean('is_active', $operator->is_active),
        ]);

        return redirect()->route('admin.production.index')->with('success', 'Operator updated.');
    }

    public function destroyOperator(ProductionOperator $operator)
    {
        $operator->delete();

        return redirect()->route('admin.production.index')->with('success', 'Operator deleted.');
    }

    // ── Machines ─────────────────────────────────────────────────────────

    public function storeMachine(Request $request)
    {
        $data = $request->validate([
            'key'      => ['required', 'string', 'max:50', 'unique:production_machines,key'],
            'name'     => ['required', 'string', 'max:100'],
            'division' => ['required', 'string', 'max:100'],
            'hue'      => ['required', 'integer', 'min:0', 'max:359'],
        ]);

        $maxOrder = ProductionMachine::max('sort_order') ?? 0;
        ProductionMachine::create(array_merge($data, ['sort_order' => $maxOrder + 1, 'is_active' => true]));

        return redirect()->route('admin.production.index')->with('success', 'Machine added.');
    }

    public function updateMachine(Request $request, ProductionMachine $machine)
    {
        $data = $request->validate([
            'key'       => ['required', 'string', 'max:50', 'unique:production_machines,key,' . $machine->id],
            'name'      => ['required', 'string', 'max:100'],
            'division'  => ['required', 'string', 'max:100'],
            'hue'       => ['required', 'integer', 'min:0', 'max:359'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $machine->update($data);

        return redirect()->route('admin.production.index')->with('success', 'Machine updated.');
    }

    public function destroyMachine(ProductionMachine $machine)
    {
        $machine->update(['is_active' => false]);

        return redirect()->route('admin.production.index')->with('success', 'Machine deactivated.');
    }

    // ── Divisions ────────────────────────────────────────────────────────

    public function storeDivision(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:production_divisions,name'],
            'hue'  => ['required', 'integer', 'min:0', 'max:359'],
        ]);

        $maxOrder = ProductionDivision::max('sort_order') ?? 0;
        ProductionDivision::create(array_merge($data, ['sort_order' => $maxOrder + 1]));

        return redirect()->route('admin.production.index')->with('success', 'Division added.');
    }

    public function updateDivision(Request $request, ProductionDivision $division)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:production_divisions,name,' . $division->id],
            'hue'  => ['required', 'integer', 'min:0', 'max:359'],
        ]);

        $division->update($data);

        // Sync hue on all machines in this division
        ProductionMachine::where('division', $division->getOriginal('name'))
            ->update(['division' => $data['name'], 'hue' => $data['hue']]);

        return redirect()->route('admin.production.index')->with('success', 'Division updated.');
    }

    public function destroyDivision(ProductionDivision $division)
    {
        if (ProductionMachine::where('division', $division->name)->exists()) {
            return redirect()->route('admin.production.index')
                ->with('error', 'Cannot delete division — machines are assigned to it.');
        }

        $division->delete();

        return redirect()->route('admin.production.index')->with('success', 'Division deleted.');
    }
}
