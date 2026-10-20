<x-layout title="Factory Training — Lockie Portal">
<main class="max-w-[1400px] mx-auto px-4 sm:px-6 py-8">

    {{-- Page header --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 mb-1">Factory Training</h1>
            <p class="text-slate-500 text-sm">Operator machine training matrix &amp; records.</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            @if($canEdit)
            <button onclick="openModal('departments-modal')"
                class="inline-flex items-center gap-1.5 px-3.5 py-[0.4rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.8125rem] font-semibold cursor-pointer hover:bg-slate-50 transition-colors">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Departments
            </button>
            <button onclick="openModal('operators-modal')"
                class="inline-flex items-center gap-1.5 px-3.5 py-[0.4rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.8125rem] font-semibold cursor-pointer hover:bg-slate-50 transition-colors">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Operators
            </button>
            <button onclick="openModal('machines-modal')"
                class="inline-flex items-center gap-1.5 px-3.5 py-[0.4rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-none cursor-pointer hover:bg-slate-800 transition-colors">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
                Machines
            </button>
            @endif
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ $errors->first() }}</div>
    @endif

    @php
        $totalOperators = $operators->count();
        $totalMachines  = $machines->count();
        $neverTrained   = 0;
        $expired        = 0;
        $expiring       = 0;
        foreach ($operators as $op) {
            foreach ($machines as $mac) {
                $rec = $matrix[$op->id][$mac->id] ?? null;
                if (!$rec) { $neverTrained++; continue; }
                $s = $rec->status();
                if ($s === 'expired')  $expired++;
                if ($s === 'expiring') $expiring++;
            }
        }
    @endphp

    {{-- Stats bar --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <div class="inline-flex items-center gap-2 px-3.5 py-[0.375rem] rounded-full bg-slate-100 text-slate-600 text-[0.8125rem] font-semibold">
            {{ $totalOperators }} operator{{ $totalOperators !== 1 ? 's' : '' }}
        </div>
        <div class="inline-flex items-center gap-2 px-3.5 py-[0.375rem] rounded-full bg-slate-100 text-slate-600 text-[0.8125rem] font-semibold">
            {{ $totalMachines }} machine{{ $totalMachines !== 1 ? 's' : '' }}
        </div>
        @if($neverTrained > 0)
        <div class="inline-flex items-center gap-2 px-3.5 py-[0.375rem] rounded-full bg-slate-100 text-slate-500 text-[0.8125rem] font-semibold">
            {{ $neverTrained }} gap{{ $neverTrained !== 1 ? 's' : '' }}
        </div>
        @endif
        @if($expired > 0)
        <div class="inline-flex items-center gap-2 px-3.5 py-[0.375rem] rounded-full bg-red-100 text-red-800 text-[0.8125rem] font-semibold">
            {{ $expired }} overdue
        </div>
        @endif
        @if($expiring > 0)
        <div class="inline-flex items-center gap-2 px-3.5 py-[0.375rem] rounded-full bg-amber-100 text-amber-800 text-[0.8125rem] font-semibold">
            {{ $expiring }} due soon
        </div>
        @endif
    </div>

    {{-- Training Matrix --}}
    @php $matrixJson = []; @endphp

    {{-- Department filter --}}
    @if($categories->isNotEmpty())
    <div class="flex flex-wrap gap-2 mb-4 items-center">
        <span class="text-[0.72rem] font-bold text-slate-400 uppercase tracking-wide mr-1">Filter:</span>
        <button onclick="filterDept('__all__')" id="dept-btn-__all__"
            class="px-3 py-[0.3rem] rounded-full border border-slate-800 bg-slate-800 text-white text-[0.78rem] font-semibold cursor-pointer">
            All
        </button>
        @foreach($categories as $cat)
        <button onclick="filterDept('{{ addslashes($cat) }}')" id="dept-btn-{{ Str::slug($cat) }}"
            class="px-3 py-[0.3rem] rounded-full border border-slate-200 bg-white text-slate-600 text-[0.78rem] font-semibold cursor-pointer">
            {{ $cat }}
        </button>
        @endforeach
    </div>
    @endif

    @if($operators->isEmpty() || $machines->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-400 text-sm">
        @if($operators->isEmpty() && $machines->isEmpty())
            No operators or machines configured yet. Use the buttons above to add them.
        @elseif($operators->isEmpty())
            No active operators. Use "Operators" to add them.
        @else
            No active machines. Use "Machines" to add them.
        @endif
    </div>
    @else

    @php
    $matrixJson = [];
    foreach ($operators as $op) {
        foreach ($machines as $mac) {
            $rec     = $matrix[$op->id][$mac->id] ?? null;
            $allRecs = $recordHistory[$op->id][$mac->id] ?? [];
            $plans   = $plannedMatrix[$op->id][$mac->id] ?? [];
            $matrixJson[$op->id][$mac->id] = [
                'record' => $rec ? [
                    'id'           => $rec->id,
                    'trained_date' => $rec->trained_date->format('d M Y'),
                    'notes'        => $rec->notes,
                    'has_pdf'      => (bool)$rec->pdf_path,
                    'pdf_url'      => $rec->pdf_path ? route('training.records.pdf', $rec->id) : null,
                    'added_by'     => $rec->addedBy?->name,
                    'status'       => $rec->status(),
                    'delete_url'   => route('training.records.destroy', $rec->id),
                ] : null,
                'history' => array_map(fn($r) => [
                    'id'           => $r->id,
                    'trained_date' => $r->trained_date->format('d M Y'),
                    'notes'        => $r->notes,
                    'has_pdf'      => (bool)$r->pdf_path,
                    'pdf_url'      => $r->pdf_path ? route('training.records.pdf', $r->id) : null,
                    'added_by'     => $r->addedBy?->name,
                    'delete_url'   => route('training.records.destroy', $r->id),
                ], count($allRecs) > 1 ? array_slice($allRecs, 1) : []),
                'planned' => array_map(fn($p) => [
                    'id'           => $p->id,
                    'date'         => $p->planned_date->format('d M Y'),
                    'notes'        => $p->notes,
                    'destroy_url'  => route('training.planned.destroy', $p->id),
                    'complete_url' => route('training.planned.complete', $p->id),
                ], $plans),
                'operator_id'   => $op->id,
                'machine_id'    => $mac->id,
                'operator_name' => $op->name,
                'machine_name'  => $mac->name,
            ];
        }
    }

    $machineGroups = [];
    foreach ($machines as $mac) {
        $cat = $mac->category ?: 'Uncategorised';
        $machineGroups[$cat][] = $mac;
    }
    @endphp

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto" style="-webkit-overflow-scrolling:touch;">
            <table class="border-collapse text-[0.8rem] whitespace-nowrap w-full">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-[3] bg-slate-50 px-3.5 py-2 text-left text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide border-b border-slate-200 border-r-2 border-r-slate-200 min-w-[160px]">Operator</th>
                        @foreach($machineGroups as $cat => $catMachines)
                        <th colspan="{{ count($catMachines) }}" data-dept="{{ $cat }}"
                            class="px-2.5 py-1.5 text-center text-[0.68rem] font-bold text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200 border-l border-l-slate-200">
                            {{ $cat }}
                        </th>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="sticky left-0 z-[3] bg-slate-50 p-0 border-b-2 border-slate-200 border-r-2 border-r-slate-200"></th>
                        @foreach($machineGroups as $cat => $catMachines)
                            @foreach($catMachines as $mac)
                            <th data-dept="{{ $cat }}" class="px-2.5 py-1.5 text-center text-[0.75rem] font-semibold text-slate-700 border-b-2 border-slate-200 border-l border-l-slate-100 min-w-[110px]">
                                {{ $mac->name }}
                                @if($mac->retrain_months)
                                <div class="text-[0.65rem] font-normal text-slate-400">{{ $mac->retrain_months }}mo</div>
                                @endif
                            </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($operators as $op)
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="sticky left-0 z-[1] bg-white px-3.5 py-2 text-[0.8125rem] font-semibold text-slate-700 border-r-2 border-r-slate-200 min-w-[160px]">
                            {{ $op->name }}
                        </td>
                        @foreach($machineGroups as $cat => $catMachines)
                            @foreach($catMachines as $mac)
                            @php
                                $rec        = $matrix[$op->id][$mac->id] ?? null;
                                $hasPlanned = !empty($plannedMatrix[$op->id][$mac->id]);
                                $status     = $rec ? $rec->status() : 'none';
                                $hasPdf     = $rec && $rec->pdf_path;
                                $firstPlan = ($status === 'none' && $hasPlanned) ? ($plannedMatrix[$op->id][$mac->id][0] ?? null) : null;
                                $bgColor   = match(true) {
                                    $status === 'valid' || $status === 'no_expiry' => '#dcfce7',
                                    $status === 'expiring'                         => '#fef3c7',
                                    $status === 'expired'                          => '#fee2e2',
                                    $firstPlan !== null                            => '#fff7ed',
                                    default                                        => '#f8fafc',
                                };
                                $txtColor = match(true) {
                                    $status === 'valid' || $status === 'no_expiry' => '#166534',
                                    $status === 'expiring'                         => '#92400e',
                                    $status === 'expired'                          => '#991b1b',
                                    $firstPlan !== null                            => '#c2410c',
                                    default                                        => '#94a3b8',
                                };
                                $expiryLabel = null;
                                if ($rec && $mac->retrain_months && in_array($status, ['expiring', 'expired'])) {
                                    $expiryLabel = $rec->trained_date->copy()->addMonths($mac->retrain_months)->format('d M y');
                                }
                            @endphp
                            <td data-dept="{{ $cat }}" class="px-1.5 py-1 text-center border-l border-l-slate-100 align-middle">
                                <span @if($canEdit) onclick="openCell({{ $op->id }}, {{ $mac->id }})" @endif
                                    class="inline-flex flex-col items-center justify-center gap-px px-1.5 py-1 rounded-md text-[0.72rem] font-semibold min-w-[90px] relative{{ $canEdit ? ' cursor-pointer' : '' }}"
                                    style="background:{{ $bgColor }};color:{{ $txtColor }};">
                                    <span class="inline-flex items-center justify-center gap-[3px]">
                                    @if($firstPlan)
                                        <span class="text-[0.68rem]">Planned</span>
                                        <span>{{ $firstPlan->planned_date->format('d M y') }}</span>
                                    @elseif($status === 'none')
                                        <span class="text-slate-300">—</span>
                                    @elseif($status === 'valid' || $status === 'no_expiry')
                                        <span>✓</span>
                                        <span>{{ $rec->trained_date->format('d M y') }}</span>
                                    @elseif($status === 'expiring')
                                        <span>⚠</span>
                                        <span>due {{ $expiryLabel }}</span>
                                    @elseif($status === 'expired')
                                        <span>✗</span>
                                        <span>due {{ $expiryLabel }}</span>
                                    @endif
                                    </span>
                                    @if($rec && !$hasPdf)
                                    <span class="text-[0.6rem] font-bold text-amber-800 bg-amber-100 rounded px-1 tracking-wide">No PDF</span>
                                    @endif
                                    @if($hasPlanned && $status !== 'none')
                                    <span title="Planned session" class="absolute top-0.5 right-1 text-[0.6rem] text-indigo-500">📅</span>
                                    @endif
                                </span>
                            </td>
                            @endforeach
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($canEdit)
    {{-- ====================== RECORD MODAL ====================== --}}
    <div id="record-modal" style="display:none;" class="fixed inset-0 z-[100] items-center justify-center bg-black/45 p-4">
        <div class="bg-white rounded-2xl w-full max-w-[560px] max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <div>
                    <p id="modal-operator-name" class="text-[0.75rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5"></p>
                    <h2 id="modal-machine-name" class="text-[1.05rem] font-bold text-slate-800 m-0"></h2>
                </div>
                <button onclick="closeModal('record-modal')" class="bg-transparent border-none cursor-pointer text-slate-400 p-1 rounded-md leading-none hover:text-slate-600">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="px-6 py-5">

                {{-- Existing record --}}
                <div id="modal-record-section" style="display:none;" class="mb-5 p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-3">Current Record</p>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Trained Date</p>
                            <p id="modal-trained-date" class="text-sm font-semibold text-slate-800 m-0"></p>
                        </div>
                        <div>
                            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Added By</p>
                            <p id="modal-added-by" class="text-sm text-slate-700 m-0"></p>
                        </div>
                        <div id="modal-pdf-wrap">
                            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Certificate</p>
                            <a id="modal-pdf-link" href="#" target="_blank" class="text-[0.8125rem] text-blue-600 font-semibold no-underline hover:underline">View Certificate</a>
                        </div>
                    </div>
                    <div id="modal-notes-wrap" class="mb-3">
                        <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Notes</p>
                        <p id="modal-notes" class="text-[0.8125rem] text-slate-600 m-0 whitespace-pre-line"></p>
                    </div>
                    <form id="modal-delete-record-form" method="POST" class="m-0" onsubmit="return confirm('Delete this training record? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="px-3 py-[0.3rem] rounded-lg border border-red-300 bg-white text-red-600 text-[0.75rem] font-semibold cursor-pointer hover:bg-red-50 transition-colors">
                            Delete Record
                        </button>
                    </form>
                </div>

                {{-- Training history --}}
                <div id="modal-history-section" style="display:none;" class="mb-5">
                    <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-2">Previous Records</p>
                    <div id="modal-history-list"></div>
                </div>

                {{-- Planned sessions --}}
                <div id="modal-planned-section" style="display:none;" class="mb-5">
                    <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-2">Planned Sessions</p>
                    <div id="modal-planned-list"></div>
                </div>

                {{-- Add Training Record --}}
                <details class="mb-4">
                    <summary class="text-[0.8125rem] font-bold text-slate-800 cursor-pointer py-2 list-none flex items-center gap-2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Training Record
                    </summary>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 mt-2">
                        <form id="modal-record-form" action="{{ route('training.records.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="operator_id" id="record-operator-id">
                            <input type="hidden" name="machine_id"  id="record-machine-id">
                            <div class="mb-3">
                                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Trained Date *</label>
                                <input type="date" name="trained_date" required
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 bg-white box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div class="mb-3">
                                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Upload Certificate (PDF or image)</label>
                                <input type="file" name="file" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp"
                                    class="w-full text-[0.8125rem] text-slate-700">
                            </div>
                            <div class="mb-3.5">
                                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Notes</label>
                                <textarea name="notes" rows="2"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 resize-y box-border focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                            </div>
                            <button type="submit"
                                class="px-3.5 py-[0.4rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-none cursor-pointer hover:bg-slate-800 transition-colors">
                                Save Record
                            </button>
                        </form>
                    </div>
                </details>

                {{-- Add Planned Session --}}
                <details>
                    <summary class="text-[0.8125rem] font-bold text-slate-800 cursor-pointer py-2 list-none flex items-center gap-2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Add Planned Session
                    </summary>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 mt-2">
                        <form id="modal-planned-form" action="{{ route('training.planned.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="operator_id" id="planned-operator-id">
                            <input type="hidden" name="machine_id"  id="planned-machine-id">
                            <div class="mb-3">
                                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Planned Date *</label>
                                <input type="date" name="planned_date" required
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 bg-white box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div class="mb-3.5">
                                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Notes</label>
                                <textarea name="notes" rows="2"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 resize-y box-border focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                            </div>
                            <button type="submit"
                                class="px-3.5 py-[0.4rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-none cursor-pointer hover:bg-slate-800 transition-colors">
                                Save Plan
                            </button>
                        </form>
                    </div>
                </details>

            </div>
        </div>
    </div>

    {{-- ====================== MACHINES MODAL ====================== --}}
    <div id="machines-modal" style="display:none;" class="fixed inset-0 z-[100] items-start justify-center bg-black/45 py-8 px-4 overflow-y-auto">
        <div class="bg-white rounded-2xl w-full max-w-[700px] shadow-2xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <h2 class="text-[1.05rem] font-bold text-slate-800 m-0">Manage Machines</h2>
                <button onclick="closeModal('machines-modal')" class="bg-transparent border-none cursor-pointer text-slate-400 p-1 leading-none hover:text-slate-600">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">

                {{-- Add machine form --}}
                <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 mb-5">
                    <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-3">Add New Machine</p>
                    <form action="{{ route('training.machines.store') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Name *</label>
                                <input type="text" name="name" required maxlength="100"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div>
                                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Department</label>
                                <select name="category"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 bg-white box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                                    <option value="">— None —</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept->name }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3.5">
                            <div>
                                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Re-train Interval (months)</label>
                                <input type="number" name="retrain_months" min="1" max="120"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div class="flex items-center pt-6">
                                <label class="flex items-center gap-2 text-[0.8125rem] text-slate-700 cursor-pointer">
                                    <input type="hidden" name="active" value="0">
                                    <input type="checkbox" name="active" value="1" checked class="w-[15px] h-[15px] cursor-pointer">
                                    Active
                                </label>
                            </div>
                        </div>
                        <button type="submit"
                            class="px-3.5 py-[0.4rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-none cursor-pointer hover:bg-slate-800 transition-colors">
                            Add Machine
                        </button>
                    </form>
                </div>

                {{-- Machines list grouped by department --}}
                @php
                $allMachines = \App\Models\TrainingMachine::orderBy('category')->orderBy('sort_order')->orderBy('name')->get();
                $machinesByDept = $allMachines->groupBy(fn($m) => $m->category ?: 'Uncategorised');
                @endphp
                @if($allMachines->isEmpty())
                <p class="text-slate-400 text-sm text-center py-4">No machines added yet.</p>
                @else
                @foreach($machinesByDept as $deptName => $deptMachines)
                <div class="mb-4">
                    <div class="text-[0.68rem] font-bold text-slate-400 uppercase tracking-wider py-1 mb-1 border-b border-slate-200">
                        {{ $deptName }}
                    </div>
                    <div class="machine-sort-group border border-slate-200 rounded-xl overflow-hidden" data-reorder-url="{{ route('training.machines.reorder') }}">
                        @foreach($deptMachines as $mac)
                        <div class="machine-row bg-white px-4 py-3 border-b border-slate-100 flex items-center gap-3" data-id="{{ $mac->id }}">
                            <span class="drag-handle text-slate-300 cursor-grab flex-shrink-0 leading-none p-0.5" title="Drag to reorder">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="18" x2="16" y2="18"/></svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="text-[0.8125rem] font-semibold" style="color:{{ $mac->active ? '#1e293b' : '#94a3b8' }};">
                                    {{ $mac->name }}
                                    @if(!$mac->active)<span class="text-[0.7rem] text-slate-400 font-normal"> (inactive)</span>@endif
                                </div>
                                @if($mac->retrain_months)
                                <div class="text-[0.75rem] text-slate-400">Re-train: {{ $mac->retrain_months }}mo</div>
                                @endif
                            </div>
                            <button onclick="toggleEdit('machine-edit-{{ $mac->id }}')"
                                class="flex-shrink-0 px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.75rem] cursor-pointer hover:bg-slate-50 transition-colors">
                                Edit
                            </button>
                            <form action="{{ route('training.machines.destroy', $mac->id) }}" method="POST" class="m-0 flex-shrink-0"
                                onsubmit="return confirm('Delete machine {{ addslashes($mac->name) }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="px-2.5 py-1 rounded-lg border border-red-300 bg-white text-red-600 text-[0.75rem] cursor-pointer hover:bg-red-50 transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                        {{-- Edit form --}}
                        <div id="machine-edit-{{ $mac->id }}" class="mac-no-drag" style="display:none;">
                            <div class="px-4 py-3.5 bg-slate-50 border-b border-slate-200">
                            <form action="{{ route('training.machines.update', $mac->id) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Name *</label>
                                        <input type="text" name="name" value="{{ $mac->name }}" required maxlength="100"
                                            class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                                    </div>
                                    <div>
                                        <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Department</label>
                                        <select name="category"
                                            class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 bg-white box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                                            <option value="">— None —</option>
                                            @foreach($departments as $dept)
                                            <option value="{{ $dept->name }}" {{ $mac->category === $dept->name ? 'selected' : '' }}>{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3 mb-3.5">
                                    <div>
                                        <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Re-train Interval (months)</label>
                                        <input type="number" name="retrain_months" value="{{ $mac->retrain_months }}" min="1" max="120"
                                            class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                                    </div>
                                    <div class="flex items-center pt-6">
                                        <label class="flex items-center gap-2 text-[0.8125rem] text-slate-700 cursor-pointer">
                                            <input type="hidden" name="active" value="0">
                                            <input type="checkbox" name="active" value="1" {{ $mac->active ? 'checked' : '' }} class="w-[15px] h-[15px] cursor-pointer">
                                            Active
                                        </label>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit"
                                        class="px-3 py-[0.35rem] rounded-lg bg-slate-900 text-white text-[0.75rem] font-semibold border-none cursor-pointer hover:bg-slate-800 transition-colors">
                                        Save
                                    </button>
                                    <button type="button" onclick="toggleEdit('machine-edit-{{ $mac->id }}')"
                                        class="px-3 py-[0.35rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.75rem] cursor-pointer hover:bg-slate-50 transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- ====================== OPERATORS MODAL ====================== --}}
    <div id="operators-modal" style="display:none;" class="fixed inset-0 z-[100] items-start justify-center bg-black/45 py-8 px-4 overflow-y-auto">
        <div class="bg-white rounded-2xl w-full max-w-[600px] shadow-2xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <h2 class="text-[1.05rem] font-bold text-slate-800 m-0">Manage Operators</h2>
                <button onclick="closeModal('operators-modal')" class="bg-transparent border-none cursor-pointer text-slate-400 p-1 leading-none hover:text-slate-600">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">

                {{-- Add operator form --}}
                <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 mb-5">
                    <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-3">Add New Operator</p>
                    <form action="{{ route('training.operators.store') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Name *</label>
                                <input type="text" name="name" required maxlength="150"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div>
                                <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Email</label>
                                <input type="email" name="email" maxlength="255"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                        </div>
                        <div class="mb-3.5">
                            <label class="flex items-center gap-2 text-[0.8125rem] text-slate-700 cursor-pointer">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" name="active" value="1" checked class="w-[15px] h-[15px] cursor-pointer">
                                Active
                            </label>
                        </div>
                        <button type="submit"
                            class="px-3.5 py-[0.4rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-none cursor-pointer hover:bg-slate-800 transition-colors">
                            Add Operator
                        </button>
                    </form>
                </div>

                {{-- Operators list --}}
                @php $allOperators = \App\Models\TrainingOperator::orderBy('name')->get(); @endphp
                @if($allOperators->isEmpty())
                <p class="text-slate-400 text-sm text-center py-4">No operators added yet.</p>
                @else
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    @foreach($allOperators as $op)
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="text-[0.8125rem] font-semibold" style="color:{{ $op->active ? '#1e293b' : '#94a3b8' }};">
                                {{ $op->name }}
                                @if(!$op->active)<span class="text-[0.7rem] text-slate-400 font-normal"> (inactive)</span>@endif
                            </div>
                            @if($op->email)
                            <div class="text-[0.75rem] text-slate-400">{{ $op->email }}</div>
                            @endif
                        </div>
                        <button onclick="toggleEdit('op-edit-{{ $op->id }}')"
                            class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.75rem] cursor-pointer hover:bg-slate-50 transition-colors">
                            Edit
                        </button>
                        <form action="{{ route('training.operators.destroy', $op->id) }}" method="POST" class="m-0"
                            onsubmit="return confirm('Delete operator {{ addslashes($op->name) }}? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="px-2.5 py-1 rounded-lg border border-red-300 bg-white text-red-600 text-[0.75rem] cursor-pointer hover:bg-red-50 transition-colors">
                                Delete
                            </button>
                        </form>
                    </div>
                    {{-- Edit form --}}
                    <div id="op-edit-{{ $op->id }}" style="display:none;" class="px-4 py-3.5 bg-slate-50 border-b border-slate-200">
                        <form action="{{ route('training.operators.update', $op->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Name *</label>
                                    <input type="text" name="name" value="{{ $op->name }}" required maxlength="150"
                                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1">Email</label>
                                    <input type="email" name="email" value="{{ $op->email }}" maxlength="255"
                                        class="w-full border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                            </div>
                            <div class="mb-3.5">
                                <label class="flex items-center gap-2 text-[0.8125rem] text-slate-700 cursor-pointer">
                                    <input type="hidden" name="active" value="0">
                                    <input type="checkbox" name="active" value="1" {{ $op->active ? 'checked' : '' }} class="w-[15px] h-[15px] cursor-pointer">
                                    Active
                                </label>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit"
                                    class="px-3 py-[0.35rem] rounded-lg bg-slate-900 text-white text-[0.75rem] font-semibold border-none cursor-pointer hover:bg-slate-800 transition-colors">
                                    Save
                                </button>
                                <button type="button" onclick="toggleEdit('op-edit-{{ $op->id }}')"
                                    class="px-3 py-[0.35rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.75rem] cursor-pointer hover:bg-slate-50 transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ====================== DEPARTMENTS MODAL ====================== --}}
    <div id="departments-modal" style="display:none;" class="fixed inset-0 z-[100] items-start justify-center bg-black/45 py-8 px-4 overflow-y-auto">
        <div class="bg-white rounded-2xl w-full max-w-[500px] shadow-2xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <h2 class="text-[1.05rem] font-bold text-slate-800 m-0">Manage Departments</h2>
                <button onclick="closeModal('departments-modal')" class="bg-transparent border-none cursor-pointer text-slate-400 p-1 leading-none hover:text-slate-600">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">

                {{-- Add department --}}
                <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 mb-5">
                    <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-3">Add Department</p>
                    <form action="{{ route('training.departments.store') }}" method="POST">
                        @csrf
                        <div class="flex gap-2">
                            <input type="text" name="name" required maxlength="100" placeholder="Department name"
                                class="flex-1 border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <button type="submit"
                                class="px-3.5 py-[0.4rem] rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-none cursor-pointer whitespace-nowrap hover:bg-slate-800 transition-colors">
                                Add
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Departments list --}}
                @php $allDepts = \App\Models\TrainingDepartment::orderBy('name')->get(); @endphp
                @if($allDepts->isEmpty())
                <p class="text-slate-400 text-sm text-center py-4">No departments added yet.</p>
                @else
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    @foreach($allDepts as $dept)
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-3">
                        <span class="flex-1 text-[0.8125rem] font-semibold text-slate-700">{{ $dept->name }}</span>
                        <button onclick="toggleEdit('dept-edit-{{ $dept->id }}')"
                            class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.75rem] cursor-pointer hover:bg-slate-50 transition-colors">
                            Edit
                        </button>
                        <form action="{{ route('training.departments.destroy', $dept->id) }}" method="POST" class="m-0"
                            onsubmit="return confirm('Delete department {{ addslashes($dept->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="px-2.5 py-1 rounded-lg border border-red-300 bg-white text-red-600 text-[0.75rem] cursor-pointer hover:bg-red-50 transition-colors">
                                Delete
                            </button>
                        </form>
                    </div>
                    <div id="dept-edit-{{ $dept->id }}" style="display:none;" class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                        <form action="{{ route('training.departments.update', $dept->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="flex gap-2">
                                <input type="text" name="name" value="{{ $dept->name }}" required maxlength="100"
                                    class="flex-1 border border-slate-200 rounded-lg px-2.5 py-[7px] text-[0.8125rem] text-slate-700 box-border focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <button type="submit"
                                    class="px-3 py-[0.35rem] rounded-lg bg-slate-900 text-white text-[0.75rem] font-semibold border-none cursor-pointer hover:bg-slate-800 transition-colors">
                                    Save
                                </button>
                                <button type="button" onclick="toggleEdit('dept-edit-{{ $dept->id }}')"
                                    class="px-3 py-[0.35rem] rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.75rem] cursor-pointer hover:bg-slate-50 transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif {{-- canEdit --}}

</main>

<script>
function filterDept(dept) {
    // Update pill styles
    var allBtn = document.getElementById('dept-btn-__all__');
    if (allBtn) {
        allBtn.style.background = dept === '__all__' ? '#334155' : '#fff';
        allBtn.style.color      = dept === '__all__' ? '#fff' : '#475569';
        allBtn.style.borderColor = dept === '__all__' ? '#334155' : '#e2e8f0';
    }
    document.querySelectorAll('[id^="dept-btn-"]').forEach(function(btn) {
        if (btn.id === 'dept-btn-__all__') return;
        var active = (dept !== '__all__' && btn.getAttribute('onclick') === "filterDept('" + dept + "')");
        btn.style.background  = active ? '#334155' : '#fff';
        btn.style.color       = active ? '#fff' : '#475569';
        btn.style.borderColor = active ? '#334155' : '#e2e8f0';
    });
    // Show/hide columns
    document.querySelectorAll('[data-dept]').forEach(function(el) {
        el.style.display = (dept === '__all__' || el.getAttribute('data-dept') === dept) ? '' : 'none';
    });
}

// Machine drag-and-drop reordering via SortableJS
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.machine-sort-group').forEach(function(group) {
        Sortable.create(group, {
            handle: '.drag-handle',
            filter: '.mac-no-drag',
            preventOnFilter: false,
            animation: 150,
            onEnd: function() {
                var ids = Array.from(group.querySelectorAll('.machine-row[data-id]')).map(function(r) { return r.dataset.id; });
                // Keep each edit panel immediately after its machine row
                ids.forEach(function(id) {
                    var row  = group.querySelector('.machine-row[data-id="' + id + '"]');
                    var edit = document.getElementById('machine-edit-' + id);
                    if (row && edit) row.after(edit);
                });
                fetch(group.getAttribute('data-reorder-url'), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify({order: ids})
                });
            }
        });
    });
});

@if($canEdit)
var matrixData = @json($matrixJson);

function openModal(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('click', function(e) {
    ['record-modal','machines-modal','operators-modal','departments-modal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && e.target === el) closeModal(id);
    });
});

function toggleEdit(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

window.openCell = function(operatorId, machineId) {
    var opData = matrixData[operatorId];
    if (!opData) return;
    var cell = opData[machineId];
    if (!cell) return;

    document.getElementById('modal-operator-name').textContent = cell.operator_name;
    document.getElementById('modal-machine-name').textContent  = cell.machine_name;

    document.getElementById('record-operator-id').value  = operatorId;
    document.getElementById('record-machine-id').value   = machineId;
    document.getElementById('planned-operator-id').value = operatorId;
    document.getElementById('planned-machine-id').value  = machineId;

    var recSection = document.getElementById('modal-record-section');
    if (cell.record) {
        recSection.style.display = 'block';
        document.getElementById('modal-trained-date').textContent = cell.record.trained_date || '—';
        document.getElementById('modal-added-by').textContent     = cell.record.added_by     || '—';

        var notesWrap = document.getElementById('modal-notes-wrap');
        if (cell.record.notes) {
            notesWrap.style.display = 'block';
            document.getElementById('modal-notes').textContent = cell.record.notes;
        } else {
            notesWrap.style.display = 'none';
        }

        var pdfWrap = document.getElementById('modal-pdf-wrap');
        if (cell.record.has_pdf && cell.record.pdf_url) {
            pdfWrap.style.display = 'block';
            document.getElementById('modal-pdf-link').href = cell.record.pdf_url;
        } else {
            pdfWrap.style.display = 'none';
        }

        document.getElementById('modal-delete-record-form').action = cell.record.delete_url;
    } else {
        recSection.style.display = 'none';
    }

    // History section
    var histSection = document.getElementById('modal-history-section');
    var histList    = document.getElementById('modal-history-list');
    if (cell.history && cell.history.length > 0) {
        histSection.style.display = 'block';
        histList.innerHTML = '';
        cell.history.forEach(function(r) {
            var row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:0.5rem;padding:0.5rem 0.75rem;background:#f8fafc;border-radius:8px;margin-bottom:0.375rem;border:1px solid #e2e8f0;';
            var pdfBtn = r.has_pdf ? '<a href="' + r.pdf_url + '" target="_blank" style="font-size:0.75rem;color:#2563eb;font-weight:600;text-decoration:none;">Cert</a>' : '';
            var byTxt  = r.added_by ? '<span style="font-size:0.72rem;color:#94a3b8;">' + escHtml(r.added_by) + '</span>' : '';
            row.innerHTML =
                '<span style="font-size:0.8125rem;font-weight:600;color:#475569;flex:1;">' + r.trained_date + '</span>' +
                byTxt + pdfBtn +
                '<form method="POST" action="' + r.delete_url + '" style="margin:0;" onsubmit="return confirm(\'Delete this record?\')">' +
                '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                '<input type="hidden" name="_method" value="DELETE">' +
                '<button type="submit" style="padding:2px 7px;border-radius:6px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.7rem;cursor:pointer;">✕</button>' +
                '</form>';
            histList.appendChild(row);
        });
    } else {
        histSection.style.display = 'none';
    }

    var plannedSection = document.getElementById('modal-planned-section');
    var plannedList    = document.getElementById('modal-planned-list');
    if (cell.planned && cell.planned.length > 0) {
        plannedSection.style.display = 'block';
        plannedList.innerHTML = '';
        cell.planned.forEach(function(p) {
            var row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:0.5rem;padding:0.5rem 0.75rem;background:#eef2ff;border-radius:8px;margin-bottom:0.375rem;';
            row.innerHTML =
                '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>' +
                '<span style="font-size:0.8125rem;font-weight:600;color:#4338ca;flex:1;">' + p.date + '</span>' +
                (p.notes ? '<span style="font-size:0.75rem;color:#6366f1;">' + escHtml(p.notes) + '</span>' : '') +
                '<button onclick="completePlanned(' + p.id + ', \'' + p.complete_url + '\', this)" style="padding:2px 7px;border-radius:6px;background:#6366f1;color:#fff;border:none;cursor:pointer;font-size:0.7rem;font-weight:600;">Done</button>' +
                '<form method="POST" action="' + p.destroy_url + '" style="margin:0;" onsubmit="return confirm(\'Delete this planned session?\')">' +
                '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                '<input type="hidden" name="_method" value="DELETE">' +
                '<button type="submit" style="padding:2px 7px;border-radius:6px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.7rem;cursor:pointer;">✕</button>' +
                '</form>';
            plannedList.appendChild(row);
        });
    } else {
        plannedSection.style.display = 'none';
    }

    openModal('record-modal');
};

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function completePlanned(id, url, btn) {
    fetch(url, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.ok) {
            var row = btn.closest('div');
            if (row) row.remove();
        }
    });
}
@endif {{-- canEdit --}}
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
</x-layout>
