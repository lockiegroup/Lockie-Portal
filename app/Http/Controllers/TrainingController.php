<?php

namespace App\Http\Controllers;

use App\Models\TrainingMachine;
use App\Models\TrainingOperator;
use App\Models\TrainingRecord;
use App\Models\TrainingPlanned;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $machines  = TrainingMachine::where('active', true)->orderBy('category')->orderBy('name')->get();
        $operators = TrainingOperator::where('active', true)->orderBy('name')->get();
        $categories = $machines->pluck('category')->filter()->unique()->sort()->values();

        // Get most recent record per operator+machine (by trained_date)
        $records = TrainingRecord::whereIn('operator_id', $operators->pluck('id'))
            ->whereIn('machine_id', $machines->pluck('id'))
            ->orderBy('trained_date', 'desc')
            ->get();

        $matrix = [];
        foreach ($records as $record) {
            if (!isset($matrix[$record->operator_id][$record->machine_id])) {
                $matrix[$record->operator_id][$record->machine_id] = $record;
            }
        }

        $planned = TrainingPlanned::whereIn('operator_id', $operators->pluck('id'))
            ->whereIn('machine_id', $machines->pluck('id'))
            ->where('completed', false)
            ->get();

        $plannedMatrix = [];
        foreach ($planned as $p) {
            $plannedMatrix[$p->operator_id][$p->machine_id][] = $p;
        }

        return view('training.index', compact(
            'machines', 'operators', 'matrix', 'plannedMatrix', 'categories'
        ));
    }

    public function planned(Request $request): View
    {
        $upcoming = TrainingPlanned::with(['machine', 'operator'])
            ->where('completed', false)
            ->orderBy('planned_date')
            ->get();

        $recentlyCompleted = TrainingPlanned::with(['machine', 'operator'])
            ->where('completed', true)
            ->where('updated_at', '>=', now()->subDays(30))
            ->orderBy('planned_date', 'desc')
            ->get();

        $machines  = TrainingMachine::where('active', true)->orderBy('category')->orderBy('name')->get();
        $operators = TrainingOperator::where('active', true)->orderBy('name')->get();

        return view('training.planned', compact('upcoming', 'recentlyCompleted', 'machines', 'operators'));
    }

    public function storeMachine(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'category'       => 'nullable|string|max:100',
            'description'    => 'nullable|string',
            'retrain_months' => 'nullable|integer|min:1|max:120',
            'active'         => 'boolean',
        ]);
        $data['active'] = $request->boolean('active', true);

        TrainingMachine::create($data);

        return redirect()->back()->with('success', 'Machine added successfully.');
    }

    public function updateMachine(Request $request, TrainingMachine $machine): RedirectResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'category'       => 'nullable|string|max:100',
            'description'    => 'nullable|string',
            'retrain_months' => 'nullable|integer|min:1|max:120',
            'active'         => 'boolean',
        ]);
        $data['active'] = $request->boolean('active', true);

        $machine->update($data);

        return redirect()->back()->with('success', 'Machine updated successfully.');
    }

    public function destroyMachine(Request $request, TrainingMachine $machine): RedirectResponse
    {
        if ($machine->records()->exists()) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete machine: training records exist.']);
        }

        $machine->delete();

        return redirect()->back()->with('success', 'Machine deleted.');
    }

    public function storeOperator(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'employee_code' => 'nullable|string|max:50',
            'department'    => 'nullable|string|max:100',
            'email'         => 'nullable|email|max:255',
            'active'        => 'boolean',
        ]);
        $data['active'] = $request->boolean('active', true);

        TrainingOperator::create($data);

        return redirect()->back()->with('success', 'Operator added successfully.');
    }

    public function updateOperator(Request $request, TrainingOperator $operator): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'employee_code' => 'nullable|string|max:50',
            'department'    => 'nullable|string|max:100',
            'email'         => 'nullable|email|max:255',
            'active'        => 'boolean',
        ]);
        $data['active'] = $request->boolean('active', true);

        $operator->update($data);

        return redirect()->back()->with('success', 'Operator updated successfully.');
    }

    public function destroyOperator(Request $request, TrainingOperator $operator): RedirectResponse
    {
        if ($operator->records()->exists()) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete operator: training records exist.']);
        }

        $operator->delete();

        return redirect()->back()->with('success', 'Operator deleted.');
    }

    public function storeRecord(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'machine_id'   => 'required|exists:training_machines,id',
            'operator_id'  => 'required|exists:training_operators,id',
            'trained_date' => 'required|date',
            'expiry_date'  => 'nullable|date',
            'notes'        => 'nullable|string',
            'file'         => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $pdfPath         = null;
        $pdfOriginalName = null;

        if ($request->hasFile('file')) {
            $file            = $request->file('file');
            $pdfOriginalName = $file->getClientOriginalName();
            $filename        = Str::uuid() . '.pdf';
            $file->storeAs('training-pdfs', $filename, 'local');
            $pdfPath = 'training-pdfs/' . $filename;
        }

        TrainingRecord::create([
            'machine_id'        => $data['machine_id'],
            'operator_id'       => $data['operator_id'],
            'trained_date'      => $data['trained_date'],
            'expiry_date'       => $data['expiry_date'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'pdf_path'          => $pdfPath,
            'pdf_original_name' => $pdfOriginalName,
            'added_by_user_id'  => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Training record added successfully.');
    }

    public function destroyRecord(Request $request, TrainingRecord $record): RedirectResponse
    {
        if ($record->pdf_path && Storage::disk('local')->exists($record->pdf_path)) {
            Storage::disk('local')->delete($record->pdf_path);
        }

        $record->delete();

        return redirect()->back()->with('success', 'Training record deleted.');
    }

    public function downloadPdf(Request $request, TrainingRecord $record)
    {
        abort_unless($record->pdf_path, 404);

        $filename = $record->pdf_original_name ?: basename($record->pdf_path);

        return Storage::disk('local')->download($record->pdf_path, $filename);
    }

    public function storePlanned(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'machine_id'   => 'required|exists:training_machines,id',
            'operator_id'  => 'required|exists:training_operators,id',
            'planned_date' => 'required|date',
            'notes'        => 'nullable|string',
        ]);

        TrainingPlanned::create([
            'machine_id'       => $data['machine_id'],
            'operator_id'      => $data['operator_id'],
            'planned_date'     => $data['planned_date'],
            'notes'            => $data['notes'] ?? null,
            'added_by_user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Planned training session added.');
    }

    public function destroyPlanned(Request $request, TrainingPlanned $planned): RedirectResponse
    {
        $planned->delete();

        return redirect()->back()->with('success', 'Planned session deleted.');
    }

    public function completePlanned(Request $request, TrainingPlanned $planned): JsonResponse
    {
        $planned->update(['completed' => true]);

        return response()->json(['ok' => true]);
    }
}
