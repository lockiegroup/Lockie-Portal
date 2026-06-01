<x-layout title="Factory Training — Lockie Portal">
<main style="max-width:1400px;margin:0 auto;padding:2rem 1.5rem;">

    {{-- Page header --}}
    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Factory Training</h1>
            <p style="color:#64748b;font-size:0.875rem;margin:0;">Operator machine training matrix &amp; records.</p>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <a href="{{ route('training.planned') }}"
               style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;font-weight:600;text-decoration:none;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Planned Training
            </a>
            <button onclick="openModal('departments-modal')"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Departments
            </button>
            <button onclick="openModal('operators-modal')"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Operators
            </button>
            <button onclick="openModal('machines-modal')"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
                Machines
            </button>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div style="margin-bottom:1rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:0.875rem;border-radius:8px;padding:0.75rem 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div style="margin-bottom:1rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:0.875rem;border-radius:8px;padding:0.75rem 1rem;">{{ $errors->first() }}</div>
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
    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-bottom:1.5rem;">
        <div style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.375rem 0.875rem;border-radius:9999px;background:#f1f5f9;color:#475569;font-size:0.8125rem;font-weight:600;">
            {{ $totalOperators }} operator{{ $totalOperators !== 1 ? 's' : '' }}
        </div>
        <div style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.375rem 0.875rem;border-radius:9999px;background:#f1f5f9;color:#475569;font-size:0.8125rem;font-weight:600;">
            {{ $totalMachines }} machine{{ $totalMachines !== 1 ? 's' : '' }}
        </div>
        @if($neverTrained > 0)
        <div style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.375rem 0.875rem;border-radius:9999px;background:#f1f5f9;color:#64748b;font-size:0.8125rem;font-weight:600;">
            {{ $neverTrained }} gap{{ $neverTrained !== 1 ? 's' : '' }}
        </div>
        @endif
        @if($expired > 0)
        <div style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.375rem 0.875rem;border-radius:9999px;background:#fee2e2;color:#991b1b;font-size:0.8125rem;font-weight:600;">
            {{ $expired }} overdue
        </div>
        @endif
        @if($expiring > 0)
        <div style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.375rem 0.875rem;border-radius:9999px;background:#fef3c7;color:#92400e;font-size:0.8125rem;font-weight:600;">
            {{ $expiring }} due soon
        </div>
        @endif
    </div>

    {{-- Training Matrix --}}
    @php $matrixJson = []; @endphp
    @if($operators->isEmpty() || $machines->isEmpty())
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:3rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
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

    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
            <table style="border-collapse:collapse;font-size:0.8rem;white-space:nowrap;width:100%;">
                <thead>
                    <tr>
                        <th style="position:sticky;left:0;z-index:3;background:#f8fafc;padding:8px 14px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e2e8f0;border-right:2px solid #e2e8f0;min-width:160px;">Operator</th>
                        @foreach($machineGroups as $cat => $catMachines)
                        <th colspan="{{ count($catMachines) }}"
                            style="padding:6px 10px;text-align:center;font-size:0.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-left:1px solid #e2e8f0;">
                            {{ $cat }}
                        </th>
                        @endforeach
                    </tr>
                    <tr>
                        <th style="position:sticky;left:0;z-index:3;background:#f8fafc;padding:0;border-bottom:2px solid #e2e8f0;border-right:2px solid #e2e8f0;"></th>
                        @foreach($machineGroups as $cat => $catMachines)
                            @foreach($catMachines as $mac)
                            <th style="padding:6px 10px;text-align:center;font-size:0.75rem;font-weight:600;color:#334155;border-bottom:2px solid #e2e8f0;border-left:1px solid #f1f5f9;min-width:110px;max-width:140px;overflow:hidden;text-overflow:ellipsis;"
                                title="{{ $mac->name }}">
                                {{ Str::limit($mac->name, 18) }}
                                @if($mac->retrain_months)
                                <div style="font-size:0.65rem;font-weight:400;color:#94a3b8;">{{ $mac->retrain_months }}mo</div>
                                @endif
                            </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($operators as $op)
                    <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="position:sticky;left:0;z-index:1;background:#fff;padding:8px 14px;font-size:0.8125rem;font-weight:600;color:#334155;border-right:2px solid #e2e8f0;min-width:160px;">
                            {{ $op->name }}
                        </td>
                        @foreach($machineGroups as $cat => $catMachines)
                            @foreach($catMachines as $mac)
                            @php
                                $rec        = $matrix[$op->id][$mac->id] ?? null;
                                $hasPlanned = !empty($plannedMatrix[$op->id][$mac->id]);
                                $status     = $rec ? $rec->status() : 'none';
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
                            <td style="padding:4px 6px;text-align:center;border-left:1px solid #f1f5f9;vertical-align:middle;">
                                <button onclick="openCell({{ $op->id }}, {{ $mac->id }})"
                                    style="display:inline-flex;align-items:center;justify-content:center;gap:3px;padding:4px 7px;border-radius:6px;background:{{ $bgColor }};color:{{ $txtColor }};border:none;cursor:pointer;font-size:0.72rem;font-weight:600;min-width:90px;position:relative;">
                                    @if($firstPlan)
                                        <span style="font-size:0.68rem;">Planned</span>
                                        <span>{{ $firstPlan->planned_date->format('d M y') }}</span>
                                    @elseif($status === 'none')
                                        <span style="color:#cbd5e1;">—</span>
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
                                    @if($hasPlanned && $status !== 'none')
                                    <span title="Planned session" style="position:absolute;top:2px;right:3px;font-size:0.6rem;color:#6366f1;">📅</span>
                                    @endif
                                </button>
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

    {{-- ====================== RECORD MODAL ====================== --}}
    <div id="record-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);padding:1rem;">
        <div style="background:#fff;border-radius:14px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;">
                <div>
                    <p id="modal-operator-name" style="font-size:0.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 2px;"></p>
                    <h2 id="modal-machine-name" style="font-size:1.05rem;font-weight:700;color:#1e293b;margin:0;"></h2>
                </div>
                <button onclick="closeModal('record-modal')" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;border-radius:6px;line-height:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div style="padding:1.25rem 1.5rem;">

                {{-- Existing record --}}
                <div id="modal-record-section" style="display:none;margin-bottom:1.25rem;padding:1rem;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
                    <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.75rem;">Current Record</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                        <div>
                            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 2px;">Trained Date</p>
                            <p id="modal-trained-date" style="font-size:0.875rem;font-weight:600;color:#1e293b;margin:0;"></p>
                        </div>
                        <div>
                            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 2px;">Added By</p>
                            <p id="modal-added-by" style="font-size:0.875rem;color:#334155;margin:0;"></p>
                        </div>
                        <div id="modal-pdf-wrap">
                            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 2px;">Certificate</p>
                            <a id="modal-pdf-link" href="#" target="_blank" style="font-size:0.8125rem;color:#2563eb;font-weight:600;text-decoration:none;">Download PDF</a>
                        </div>
                    </div>
                    <div id="modal-notes-wrap" style="margin-bottom:0.75rem;">
                        <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 2px;">Notes</p>
                        <p id="modal-notes" style="font-size:0.8125rem;color:#475569;margin:0;white-space:pre-line;"></p>
                    </div>
                    <form id="modal-delete-record-form" method="POST" style="margin:0;" onsubmit="return confirm('Delete this training record? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            style="padding:0.3rem 0.75rem;border-radius:7px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.75rem;font-weight:600;cursor:pointer;">
                            Delete Record
                        </button>
                    </form>
                </div>

                {{-- Training history --}}
                <div id="modal-history-section" style="display:none;margin-bottom:1.25rem;">
                    <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">Previous Records</p>
                    <div id="modal-history-list"></div>
                </div>

                {{-- Planned sessions --}}
                <div id="modal-planned-section" style="display:none;margin-bottom:1.25rem;">
                    <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">Planned Sessions</p>
                    <div id="modal-planned-list"></div>
                </div>

                {{-- Add Training Record --}}
                <details style="margin-bottom:1rem;">
                    <summary style="font-size:0.8125rem;font-weight:700;color:#1e293b;cursor:pointer;padding:0.5rem 0;list-style:none;display:flex;align-items:center;gap:0.5rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Training Record
                    </summary>
                    <div style="padding:1rem;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;margin-top:0.5rem;">
                        <form id="modal-record-form" action="{{ route('training.records.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="operator_id" id="record-operator-id">
                            <input type="hidden" name="machine_id"  id="record-machine-id">
                            <div style="margin-bottom:0.75rem;">
                                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Trained Date *</label>
                                <input type="date" name="trained_date" required
                                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                            </div>
                            <div style="margin-bottom:0.75rem;">
                                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Upload PDF Certificate</label>
                                <input type="file" name="file" accept=".pdf"
                                    style="width:100%;font-size:0.8125rem;color:#334155;">
                            </div>
                            <div style="margin-bottom:0.875rem;">
                                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Notes</label>
                                <textarea name="notes" rows="2"
                                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;resize:vertical;box-sizing:border-box;"></textarea>
                            </div>
                            <button type="submit"
                                style="padding:0.4rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                                Save Record
                            </button>
                        </form>
                    </div>
                </details>

                {{-- Add Planned Session --}}
                <details>
                    <summary style="font-size:0.8125rem;font-weight:700;color:#1e293b;cursor:pointer;padding:0.5rem 0;list-style:none;display:flex;align-items:center;gap:0.5rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Add Planned Session
                    </summary>
                    <div style="padding:1rem;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;margin-top:0.5rem;">
                        <form id="modal-planned-form" action="{{ route('training.planned.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="operator_id" id="planned-operator-id">
                            <input type="hidden" name="machine_id"  id="planned-machine-id">
                            <div style="margin-bottom:0.75rem;">
                                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Planned Date *</label>
                                <input type="date" name="planned_date" required
                                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                            </div>
                            <div style="margin-bottom:0.875rem;">
                                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Notes</label>
                                <textarea name="notes" rows="2"
                                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;resize:vertical;box-sizing:border-box;"></textarea>
                            </div>
                            <button type="submit"
                                style="padding:0.4rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                                Save Plan
                            </button>
                        </form>
                    </div>
                </details>

            </div>
        </div>
    </div>

    {{-- ====================== MACHINES MODAL ====================== --}}
    <div id="machines-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:flex-start;justify-content:center;background:rgba(0,0,0,0.45);padding:2rem 1rem;overflow-y:auto;">
        <div style="background:#fff;border-radius:14px;width:100%;max-width:700px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;">
                <h2 style="font-size:1.05rem;font-weight:700;color:#1e293b;margin:0;">Manage Machines</h2>
                <button onclick="closeModal('machines-modal')" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:1.25rem 1.5rem;">

                {{-- Add machine form --}}
                <div style="background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;padding:1rem;margin-bottom:1.25rem;">
                    <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.75rem;">Add New Machine</p>
                    <form action="{{ route('training.machines.store') }}" method="POST">
                        @csrf
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                            <div>
                                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Name *</label>
                                <input type="text" name="name" required maxlength="100"
                                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Department</label>
                                <select name="category"
                                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                                    <option value="">— None —</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept->name }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.875rem;">
                            <div>
                                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Re-train Interval (months)</label>
                                <input type="number" name="retrain_months" min="1" max="120"
                                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                            </div>
                            <div style="display:flex;align-items:center;padding-top:1.5rem;">
                                <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#334155;cursor:pointer;">
                                    <input type="hidden" name="active" value="0">
                                    <input type="checkbox" name="active" value="1" checked
                                        style="width:15px;height:15px;cursor:pointer;">
                                    Active
                                </label>
                            </div>
                        </div>
                        <button type="submit"
                            style="padding:0.4rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                            Add Machine
                        </button>
                    </form>
                </div>

                {{-- Machines list --}}
                @php $allMachines = \App\Models\TrainingMachine::orderBy('category')->orderBy('name')->get(); @endphp
                @if($allMachines->isEmpty())
                <p style="color:#94a3b8;font-size:0.875rem;text-align:center;padding:1rem 0;">No machines added yet.</p>
                @else
                <div style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                    @foreach($allMachines as $mac)
                    <div style="padding:0.75rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:0.75rem;">
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:0.8125rem;font-weight:600;color:{{ $mac->active ? '#1e293b' : '#94a3b8' }};">
                                {{ $mac->name }}
                                @if(!$mac->active)<span style="font-size:0.7rem;color:#94a3b8;font-weight:400;"> (inactive)</span>@endif
                            </div>
                            <div style="font-size:0.75rem;color:#94a3b8;">
                                @if($mac->category){{ $mac->category }}@endif
                                @if($mac->retrain_months) &middot; Re-train: {{ $mac->retrain_months }}mo @endif
                            </div>
                        </div>
                        <button onclick="toggleEdit('machine-edit-{{ $mac->id }}')"
                            style="padding:0.25rem 0.625rem;border-radius:7px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.75rem;cursor:pointer;">
                            Edit
                        </button>
                        <form action="{{ route('training.machines.destroy', $mac->id) }}" method="POST" style="margin:0;"
                            onsubmit="return confirm('Delete machine {{ addslashes($mac->name) }}? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                style="padding:0.25rem 0.625rem;border-radius:7px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.75rem;cursor:pointer;">
                                Delete
                            </button>
                        </form>
                    </div>
                    {{-- Edit form --}}
                    <div id="machine-edit-{{ $mac->id }}" style="display:none;padding:0.875rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                        <form action="{{ route('training.machines.update', $mac->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                                <div>
                                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Name *</label>
                                    <input type="text" name="name" value="{{ $mac->name }}" required maxlength="100"
                                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                                </div>
                                <div>
                                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Department</label>
                                    <select name="category"
                                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;background:#fff;box-sizing:border-box;">
                                        <option value="">— None —</option>
                                        @foreach($departments as $dept)
                                        <option value="{{ $dept->name }}" {{ $mac->category === $dept->name ? 'selected' : '' }}>{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.875rem;">
                                <div>
                                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Re-train Interval (months)</label>
                                    <input type="number" name="retrain_months" value="{{ $mac->retrain_months }}" min="1" max="120"
                                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                                </div>
                                <div style="display:flex;align-items:center;padding-top:1.5rem;">
                                    <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#334155;cursor:pointer;">
                                        <input type="hidden" name="active" value="0">
                                        <input type="checkbox" name="active" value="1" {{ $mac->active ? 'checked' : '' }}
                                            style="width:15px;height:15px;cursor:pointer;">
                                        Active
                                    </label>
                                </div>
                            </div>
                            <div style="display:flex;gap:0.5rem;">
                                <button type="submit"
                                    style="padding:0.35rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.75rem;font-weight:600;border:none;cursor:pointer;">
                                    Save
                                </button>
                                <button type="button" onclick="toggleEdit('machine-edit-{{ $mac->id }}')"
                                    style="padding:0.35rem 0.75rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.75rem;cursor:pointer;">
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

    {{-- ====================== OPERATORS MODAL ====================== --}}
    <div id="operators-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:flex-start;justify-content:center;background:rgba(0,0,0,0.45);padding:2rem 1rem;overflow-y:auto;">
        <div style="background:#fff;border-radius:14px;width:100%;max-width:600px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;">
                <h2 style="font-size:1.05rem;font-weight:700;color:#1e293b;margin:0;">Manage Operators</h2>
                <button onclick="closeModal('operators-modal')" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:1.25rem 1.5rem;">

                {{-- Add operator form --}}
                <div style="background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;padding:1rem;margin-bottom:1.25rem;">
                    <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.75rem;">Add New Operator</p>
                    <form action="{{ route('training.operators.store') }}" method="POST">
                        @csrf
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                            <div>
                                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Name *</label>
                                <input type="text" name="name" required maxlength="150"
                                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Email</label>
                                <input type="email" name="email" maxlength="255"
                                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                            </div>
                        </div>
                        <div style="margin-bottom:0.875rem;">
                            <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#334155;cursor:pointer;">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" name="active" value="1" checked style="width:15px;height:15px;cursor:pointer;">
                                Active
                            </label>
                        </div>
                        <button type="submit"
                            style="padding:0.4rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                            Add Operator
                        </button>
                    </form>
                </div>

                {{-- Operators list --}}
                @php $allOperators = \App\Models\TrainingOperator::orderBy('name')->get(); @endphp
                @if($allOperators->isEmpty())
                <p style="color:#94a3b8;font-size:0.875rem;text-align:center;padding:1rem 0;">No operators added yet.</p>
                @else
                <div style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                    @foreach($allOperators as $op)
                    <div style="padding:0.75rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:0.75rem;">
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:0.8125rem;font-weight:600;color:{{ $op->active ? '#1e293b' : '#94a3b8' }};">
                                {{ $op->name }}
                                @if(!$op->active)<span style="font-size:0.7rem;color:#94a3b8;font-weight:400;"> (inactive)</span>@endif
                            </div>
                            @if($op->email)
                            <div style="font-size:0.75rem;color:#94a3b8;">{{ $op->email }}</div>
                            @endif
                        </div>
                        <button onclick="toggleEdit('op-edit-{{ $op->id }}')"
                            style="padding:0.25rem 0.625rem;border-radius:7px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.75rem;cursor:pointer;">
                            Edit
                        </button>
                        <form action="{{ route('training.operators.destroy', $op->id) }}" method="POST" style="margin:0;"
                            onsubmit="return confirm('Delete operator {{ addslashes($op->name) }}? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                style="padding:0.25rem 0.625rem;border-radius:7px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.75rem;cursor:pointer;">
                                Delete
                            </button>
                        </form>
                    </div>
                    {{-- Edit form --}}
                    <div id="op-edit-{{ $op->id }}" style="display:none;padding:0.875rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                        <form action="{{ route('training.operators.update', $op->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                                <div>
                                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Name *</label>
                                    <input type="text" name="name" value="{{ $op->name }}" required maxlength="150"
                                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                                </div>
                                <div>
                                    <label style="display:block;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Email</label>
                                    <input type="email" name="email" value="{{ $op->email }}" maxlength="255"
                                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                                </div>
                            </div>
                            <div style="margin-bottom:0.875rem;">
                                <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#334155;cursor:pointer;">
                                    <input type="hidden" name="active" value="0">
                                    <input type="checkbox" name="active" value="1" {{ $op->active ? 'checked' : '' }} style="width:15px;height:15px;cursor:pointer;">
                                    Active
                                </label>
                            </div>
                            <div style="display:flex;gap:0.5rem;">
                                <button type="submit"
                                    style="padding:0.35rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.75rem;font-weight:600;border:none;cursor:pointer;">
                                    Save
                                </button>
                                <button type="button" onclick="toggleEdit('op-edit-{{ $op->id }}')"
                                    style="padding:0.35rem 0.75rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.75rem;cursor:pointer;">
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
    <div id="departments-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:flex-start;justify-content:center;background:rgba(0,0,0,0.45);padding:2rem 1rem;overflow-y:auto;">
        <div style="background:#fff;border-radius:14px;width:100%;max-width:500px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;">
                <h2 style="font-size:1.05rem;font-weight:700;color:#1e293b;margin:0;">Manage Departments</h2>
                <button onclick="closeModal('departments-modal')" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:1.25rem 1.5rem;">

                {{-- Add department --}}
                <div style="background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;padding:1rem;margin-bottom:1.25rem;">
                    <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.75rem;">Add Department</p>
                    <form action="{{ route('training.departments.store') }}" method="POST">
                        @csrf
                        <div style="display:flex;gap:0.5rem;">
                            <input type="text" name="name" required maxlength="100" placeholder="Department name"
                                style="flex:1;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                            <button type="submit"
                                style="padding:0.4rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;white-space:nowrap;">
                                Add
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Departments list --}}
                @php $allDepts = \App\Models\TrainingDepartment::orderBy('name')->get(); @endphp
                @if($allDepts->isEmpty())
                <p style="color:#94a3b8;font-size:0.875rem;text-align:center;padding:1rem 0;">No departments added yet.</p>
                @else
                <div style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                    @foreach($allDepts as $dept)
                    <div style="padding:0.75rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:0.75rem;">
                        <span style="flex:1;font-size:0.8125rem;font-weight:600;color:#334155;">{{ $dept->name }}</span>
                        <button onclick="toggleEdit('dept-edit-{{ $dept->id }}')"
                            style="padding:0.25rem 0.625rem;border-radius:7px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.75rem;cursor:pointer;">
                            Edit
                        </button>
                        <form action="{{ route('training.departments.destroy', $dept->id) }}" method="POST" style="margin:0;"
                            onsubmit="return confirm('Delete department {{ addslashes($dept->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                style="padding:0.25rem 0.625rem;border-radius:7px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.75rem;cursor:pointer;">
                                Delete
                            </button>
                        </form>
                    </div>
                    <div id="dept-edit-{{ $dept->id }}" style="display:none;padding:0.75rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                        <form action="{{ route('training.departments.update', $dept->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div style="display:flex;gap:0.5rem;">
                                <input type="text" name="name" value="{{ $dept->name }}" required maxlength="100"
                                    style="flex:1;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:0.8125rem;color:#334155;box-sizing:border-box;">
                                <button type="submit"
                                    style="padding:0.35rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.75rem;font-weight:600;border:none;cursor:pointer;">
                                    Save
                                </button>
                                <button type="button" onclick="toggleEdit('dept-edit-{{ $dept->id }}')"
                                    style="padding:0.35rem 0.75rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.75rem;cursor:pointer;">
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

</main>

<script>
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
            var pdfBtn = r.has_pdf ? '<a href="' + r.pdf_url + '" target="_blank" style="font-size:0.75rem;color:#2563eb;font-weight:600;text-decoration:none;">PDF</a>' : '';
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
</script>
</x-layout>
