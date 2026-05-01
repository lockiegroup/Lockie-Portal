<?php

namespace App\Http\Controllers;

use App\Mail\PrintJobDateChangedMail;
use App\Models\PrintJob;
use App\Models\PrintJobDateChange;
use App\Models\PrintJobNote;
use App\Models\PrintScheduleSetting;
use App\Services\PrintScheduleSyncService;
use App\Services\UnleashedService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PrintScheduleController extends Controller
{
    public function index(): View
    {
        $boards      = PrintJob::BOARDS;
        $boardLabels = PrintJob::BOARDS;
        $machines    = PrintJob::MACHINES;
        $throughputs = $this->loadThroughputs();

        $lastSync  = PrintJob::active()->max('synced_at');
        $boardJobs = [];
        foreach (array_keys($boards) as $boardKey) {
            $boardJobs[$boardKey] = PrintJob::active()->where('board', $boardKey)
                ->orderBy('position')
                ->with(['notes', 'dateChanges.user'])
                ->get();
        }

        // Machine lead times using per-machine throughput
        $machineLeadTimes = [];
        foreach ($machines as $machine) {
            $total = $boardJobs[$machine]->sum(fn($job) => $job->remaining_quantity);
            $tp    = $throughputs[$machine] ?? 350;
            $machineLeadTimes[$machine] = $tp > 0 ? round($total / $tp, 1) : 0;
        }

        // Compute estimated completion and late flags for machine board jobs
        $today = now()->startOfDay();
        foreach ($machines as $machine) {
            $throughput = $throughputs[$machine] ?? 350;
            $cumulative = 0;
            foreach ($boardJobs[$machine] as $job) {
                $cumulative += $job->remaining_quantity;
                if ($job->required_date && $throughput > 0 && $cumulative > 0) {
                    $estimated           = $this->estimatedCompletion($today, $cumulative, $throughput);
                    $daysLate            = (int) $job->required_date->diffInDays($estimated, false);
                    $job->is_late        = $daysLate > 0;
                    $job->days_overdue   = max(0, $daysLate);
                    $job->est_completion = $estimated;
                } else {
                    $job->is_late        = false;
                    $job->days_overdue   = 0;
                    $job->est_completion = null;
                }
            }
        }

        // Count how many active jobs share each order number (for multi-line badge on cards)
        $orderLineCounts = array_count_values(
            PrintJob::active()
                ->whereNotNull('order_number')
                ->where('order_number', '!=', 'MANUAL')
                ->pluck('order_number')
                ->toArray()
        );

        return view('print-schedule.index', compact(
            'boardJobs',
            'boards',
            'boardLabels',
            'machines',
            'machineLeadTimes',
            'throughputs',
            'lastSync',
            'orderLineCounts'
        ));
    }

    // Mon–Thu = full 8h day (weight 1.0), Fri = 5h day (8:00–13:30 minus 30min break, weight 5/8)
    private const DAY_WEIGHTS = [
        1 => 1.0,          // Monday
        2 => 1.0,          // Tuesday
        3 => 1.0,          // Wednesday
        4 => 1.0,          // Thursday
        5 => 5.0 / 8.0,    // Friday
    ];

    private function loadThroughputs(): array
    {
        return [
            'auto_1' => (int) PrintScheduleSetting::getValue('throughput_auto_1', '350'),
            'auto_2' => (int) PrintScheduleSetting::getValue('throughput_auto_2', '350'),
            'auto_3' => (int) PrintScheduleSetting::getValue('throughput_auto_3', '350'),
            'baby'   => (int) PrintScheduleSetting::getValue('throughput_baby',   '180'),
        ];
    }

    private function estimatedCompletion(Carbon $from, int $packsNeeded, int $throughput): Carbon
    {
        $date      = $from->copy()->addDay()->startOfDay(); // work starts next working day
        $remaining = (float) $packsNeeded;
        for ($i = 0; $i < 500; $i++) {
            $weight = self::DAY_WEIGHTS[$date->dayOfWeek] ?? 0.0;
            if ($weight > 0.0) {
                $remaining -= $throughput * $weight;
                if ($remaining <= 0.0) return $date;
            }
            $date->addDay();
        }
        return $date;
    }

    public function overview(): View
    {
        $machines    = PrintJob::MACHINES;
        $throughputs = $this->loadThroughputs();
        $today       = now()->startOfDay();

        $machineStats = [];
        foreach ($machines as $machine) {
            $jobs           = PrintJob::active()->where('board', $machine)->orderBy('position')->get();
            $totalRemaining = $jobs->sum(fn($j) => $j->remaining_quantity);
            $tp             = $throughputs[$machine] ?? 350;
            $leadDays       = $tp > 0 ? round($totalRemaining / $tp, 1) : 0;

            $lateCount  = 0;
            $cumulative = 0;
            foreach ($jobs as $job) {
                $cumulative += $job->remaining_quantity;
                if ($job->required_date && $tp > 0 && $cumulative > 0) {
                    $estimated = $this->estimatedCompletion($today, $cumulative, $tp);
                    if ($estimated->gt($job->required_date)) {
                        $lateCount++;
                    }
                }
            }

            $machineStats[$machine] = [
                'label'      => PrintJob::BOARDS[$machine],
                'job_count'  => $jobs->count(),
                'remaining'  => $totalRemaining,
                'lead_days'  => $leadDays,
                'throughput' => $tp,
                'late_count' => $lateCount,
            ];
        }

        $dashboardNotes = PrintScheduleSetting::getValue('dashboard_notes', '');
        $lastSync       = PrintJob::active()->max('synced_at');

        return view('print-schedule.overview', compact('machineStats', 'dashboardNotes', 'lastSync'));
    }

    public function sync(): JsonResponse
    {
        $current = \Illuminate\Support\Facades\Cache::get('print_sync_status', []);
        if (($current['status'] ?? '') === 'running') {
            return response()->json(['queued' => true, 'already_running' => true]);
        }

        \Illuminate\Support\Facades\Cache::put('print_sync_status', ['status' => 'running', 'at' => now()->toIso8601String()], 600);

        $artisan = PHP_BINARY . ' ' . base_path('artisan');
        exec("nohup {$artisan} print:sync > /dev/null 2>&1 &");

        return response()->json(['queued' => true]);
    }

    public function syncStatus(): JsonResponse
    {
        $status = \Illuminate\Support\Facades\Cache::get('print_sync_status', ['status' => 'idle']);
        return response()->json($status);
    }

    public function unarchive(\App\Models\PrintJob $job): JsonResponse
    {
        $job->update(['archived_at' => null, 'archive_reason' => null, 'despatched_at' => null]);
        return response()->json(['ok' => true]);
    }

    public function moveBoard(Request $request, PrintJob $job): JsonResponse
    {
        $request->validate([
            'board' => ['required', 'in:' . implode(',', array_keys(PrintJob::BOARDS))],
        ]);

        $board       = $request->input('board');
        $maxPosition = PrintJob::where('board', $board)->max('position') ?? 0;

        $job->update([
            'board'    => $board,
            'position' => $maxPosition + 1,
        ]);

        $boardLabel = PrintJob::BOARDS[$board] ?? $board;
        \App\Models\ActivityLog::record('print.board_move', "Moved {$job->order_number} to {$boardLabel}");

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($request->input('order') as $position => $id) {
            PrintJob::where('id', $id)->update(['position' => $position]);
        }

        return response()->json(['success' => true]);
    }

    public function partComplete(Request $request, PrintJob $job): JsonResponse
    {
        $request->validate([
            'quantity_completed' => ['required', 'integer', 'min:0', 'max:' . $job->order_quantity],
        ]);

        $qty = $request->integer('quantity_completed');
        $job->update(['quantity_completed' => $qty]);

        \App\Models\ActivityLog::record('print.complete', "Updated completion on {$job->order_number}: {$qty} qty completed");

        return response()->json([
            'success'   => true,
            'remaining' => $job->remaining_quantity,
        ]);
    }

    public function toggleMaterial(Request $request, PrintJob $job): JsonResponse
    {
        $request->validate(['checked' => ['required', 'boolean']]);
        $job->update(['material_checked' => $request->boolean('checked')]);
        return response()->json(['success' => true, 'material_checked' => $job->material_checked]);
    }

    public function updateDate(Request $request, PrintJob $job): JsonResponse
    {
        $request->validate([
            'required_date' => ['required', 'date'],
        ]);

        $newDate = $request->input('required_date');
        $oldDate = $job->required_date ? $job->required_date->format('Y-m-d') : null;

        if ($oldDate !== $newDate) {
            PrintJobDateChange::create([
                'print_job_id' => $job->id,
                'user_id'      => auth()->id(),
                'old_date'     => $oldDate,
                'new_date'     => $newDate,
            ]);

            Mail::to('sales@jwproducts.co.uk')->send(new PrintJobDateChangedMail(
                job:       $job,
                oldDate:   $oldDate,
                newDate:   $newDate,
                changedBy: auth()->user()->name,
            ));
        }

        $job->update(['required_date' => $newDate]);

        return response()->json([
            'success'      => true,
            'date_changed' => $job->fresh()->date_changed,
        ]);
    }

    public function storeNote(Request $request, PrintJob $job): JsonResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $note = PrintJobNote::create([
            'print_job_id' => $job->id,
            'user_id'      => auth()->id(),
            'body'         => $request->input('body'),
        ]);

        $note->load('user');

        \App\Models\ActivityLog::record('print.note_add', "Added note to {$job->order_number}");

        return response()->json([
            'success' => true,
            'note'    => [
                'id'         => $note->id,
                'body'       => $note->body,
                'user_name'  => $note->user?->name ?? 'Unknown',
                'created_at' => $note->created_at->format('d M Y, H:i'),
            ],
        ]);
    }

    public function destroyNote(PrintJob $job, PrintJobNote $note): JsonResponse
    {
        abort_unless($note->print_job_id === $job->id, 404);

        $note->delete();

        \App\Models\ActivityLog::record('print.note_delete', "Deleted note from {$job->order_number}");

        return response()->json(['success' => true]);
    }

    public function storeManual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_code'        => ['nullable', 'string', 'max:100'],
            'product_description' => ['required', 'string', 'max:255'],
            'line_comment'        => ['nullable', 'string', 'max:2000'],
            'customer_name'       => ['nullable', 'string', 'max:255'],
            'customer_ref'        => ['nullable', 'string', 'max:255'],
            'order_number'        => ['nullable', 'string', 'max:100'],
            'quantity'            => ['required', 'integer', 'min:1'],
            'required_date'       => ['nullable', 'date'],
            'board'               => ['required', 'in:' . implode(',', array_keys(PrintJob::BOARDS))],
        ]);

        $job = PrintJob::create([
            'product_code'           => $data['product_code'] ?: null,
            'product_description'    => $data['product_description'],
            'line_comment'           => $data['line_comment'] ?: null,
            'customer_name'          => $data['customer_name'] ?: 'Manual',
            'customer_ref'           => $data['customer_ref'] ?: null,
            'order_number'           => $data['order_number'] ?: 'MANUAL',
            'order_quantity'         => $data['quantity'],
            'quantity_completed'     => 0,
            'required_date'          => $data['required_date'] ?: null,
            'original_required_date' => $data['required_date'] ?: null,
            'board'                  => $data['board'],
            'position'               => PrintJob::where('board', $data['board'])->max('position') + 1,
            'is_manual'              => true,
        ]);

        \App\Models\ActivityLog::record('print.manual_add', "Added manual job: {$job->product_description}");

        return response()->json(['success' => true, 'redirect' => route('print.index')]);
    }

    public function completeManual(PrintJob $job): JsonResponse
    {
        abort_unless($job->is_manual, 403);

        $job->update([
            'archived_at'    => now(),
            'archive_reason' => 'completed',
            'despatched_at'  => now()->toDateString(),
        ]);

        \App\Models\ActivityLog::record('print.manual_complete', "Completed manual job: {$job->product_description}");

        return response()->json(['success' => true]);
    }

    public function updateManual(Request $request, PrintJob $job): JsonResponse
    {
        abort_unless($job->is_manual, 403);

        $data = $request->validate([
            'product_code'        => ['nullable', 'string', 'max:100'],
            'product_description' => ['required', 'string', 'max:255'],
            'line_comment'        => ['nullable', 'string', 'max:2000'],
            'customer_name'       => ['nullable', 'string', 'max:255'],
            'customer_ref'        => ['nullable', 'string', 'max:255'],
            'order_number'        => ['nullable', 'string', 'max:100'],
            'order_quantity'      => ['required', 'integer', 'min:1'],
            'required_date'       => ['nullable', 'date'],
        ]);

        $job->update([
            'product_code'        => $data['product_code'] ?: null,
            'product_description' => $data['product_description'],
            'line_comment'        => $data['line_comment'] ?: null,
            'customer_name'       => $data['customer_name'] ?: 'Manual',
            'customer_ref'        => $data['customer_ref'] ?: null,
            'order_number'        => $data['order_number'] ?: 'MANUAL',
            'order_quantity'      => $data['order_quantity'],
            'required_date'       => $data['required_date'] ?: null,
        ]);

        \App\Models\ActivityLog::record('print.manual_add', "Edited manual job: {$job->product_description}");

        return response()->json(['success' => true]);
    }

    public function deleteManual(PrintJob $job): JsonResponse
    {
        abort_unless($job->is_manual, 403);

        $description = $job->product_description;
        $job->delete();

        \App\Models\ActivityLog::record('print.manual_archive', "Deleted manual job: {$description}");

        return response()->json(['success' => true]);
    }

    public function archiveManual(PrintJob $job): JsonResponse
    {
        abort_unless($job->is_manual, 403);

        $job->update([
            'archived_at'    => now(),
            'archive_reason' => 'deleted',
        ]);

        \App\Models\ActivityLog::record('print.manual_archive', "Archived manual job: {$job->product_description}");

        return response()->json(['success' => true]);
    }
}
