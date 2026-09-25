<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionMachine;
use App\Models\ProductionOperator;
use Illuminate\Http\Request;

class ProductionSettingsController extends Controller
{
    public function index()
    {
        $operators = ProductionOperator::orderBy('sort_order')->orderBy('id')->get();
        $machines  = ProductionMachine::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.production.index', compact('operators', 'machines'));
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

    public function storeMachine(Request $request)
    {
        $data = $request->validate([
            'key'      => ['required', 'string', 'max:50', 'unique:production_machines,key'],
            'name'     => ['required', 'string', 'max:100'],
            'division' => ['required', 'string', 'max:50'],
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
            'division'  => ['required', 'string', 'max:50'],
            'hue'       => ['required', 'integer', 'min:0', 'max:359'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $machine->update($data);

        return redirect()->route('admin.production.index')->with('success', 'Machine updated.');
    }

    public function destroyMachine(ProductionMachine $machine)
    {
        // Soft-disable instead of hard delete so historical plan data stays valid
        $machine->update(['is_active' => false]);

        return redirect()->route('admin.production.index')->with('success', 'Machine deactivated.');
    }
}
