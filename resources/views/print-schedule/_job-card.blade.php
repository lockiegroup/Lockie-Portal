@php
    $requiredDateFmt = $job->required_date ? $job->required_date->format('d M Y') : null;
    $orderDateFmt    = $job->order_date    ? $job->order_date->format('d M Y')    : null;
    $noteCount       = $job->notes->count();
@endphp

<div class="job-card bg-white rounded-xl border shadow-sm select-none {{ $job->is_manual ? 'border-green-300' : 'border-slate-200' }}" style="padding: 1.25rem 1.5rem;"
     data-job-id="{{ $job->id }}"
     data-current-board="{{ $job->board }}"
     data-remaining="{{ $job->remaining_quantity }}"
     data-required-date="{{ $job->required_date ? $job->required_date->format('Y-m-d') : '' }}"
     data-search-text="{{ strtolower(collect([$job->order_number, $job->customer_name, $job->customer_ref, $job->product_code, $job->product_description, $job->line_comment])->filter()->implode(' ')) }}"
     id="job-card-{{ $job->id }}">

    {{-- Row 1: Drag handle | Order number | Customer | Board select --}}
    <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:0.75rem;">
        <span class="drag-handle cursor-grab active:cursor-grabbing text-slate-300 hover:text-slate-500 mt-0.5 flex-shrink-0 pt-0.5" title="Drag to reorder">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/>
                <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                <circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/>
            </svg>
        </span>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-xs text-slate-400 font-medium">{{ $job->order_number }}</span>
                <span class="text-sm font-medium text-slate-800 truncate">{{ $job->customer_name }}</span>
                @if($job->is_manual)
                    <span style="font-size:0.65rem;font-weight:700;background:#dcfce7;color:#15803d;padding:1px 6px;border-radius:9999px;text-transform:uppercase;letter-spacing:0.05em;">Manual</span>
                @endif
            </div>
            @if($orderDateFmt || $job->customer_ref)
                <div style="display:flex;flex-wrap:wrap;gap:4px 12px;margin-top:3px;">
                    @if($orderDateFmt)
                        <span class="text-xs text-slate-400">Ordered: {{ $orderDateFmt }}</span>
                    @endif
                    @if($job->customer_ref)
                        <span class="text-xs text-slate-400">Ref: <span class="text-slate-600 font-medium">{{ $job->customer_ref }}</span></span>
                    @endif
                </div>
            @endif
        </div>
        <select
            class="text-xs border border-slate-200 rounded-lg px-2 py-1 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-rose-500 flex-shrink-0 cursor-pointer"
            onchange="moveBoard({{ $job->id }}, this.value)">
            @foreach($boards as $boardKey => $boardLabel)
                <option value="{{ $boardKey }}" {{ $job->board === $boardKey ? 'selected' : '' }}>
                    {{ $boardLabel }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Row 2: Product code — description --}}
    <div class="mb-2">
        <p class="font-semibold text-slate-800 text-sm leading-snug">
            @if($job->product_code)
                <span class="font-mono text-slate-500">{{ $job->product_code }}</span>
                @if($job->product_description)
                    <span class="text-slate-400 font-normal"> &mdash; </span>{{ $job->product_description }}
                @endif
            @else
                <span class="text-slate-400 italic">No product</span>
            @endif
        </p>
    </div>

    {{-- Late warning (also updated live by JS recalculateLateFlags) --}}
    <div id="late-banner-{{ $job->id }}"
         style="background:#fef2f2;border:1px solid #fecaca;border-radius:0.5rem;padding:6px 10px;margin-bottom:0.75rem;display:{{ ($job->is_late ?? false) ? 'flex' : 'none' }};align-items:center;gap:6px;">
        <svg style="width:14px;height:14px;color:#dc2626;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <span class="late-text" style="font-size:0.75rem;color:#dc2626;font-weight:500;">
            @if($job->is_late ?? false)
                Estimated late by {{ $job->days_overdue }} day{{ $job->days_overdue !== 1 ? 's' : '' }}
                &mdash; est. {{ $job->est_completion->format('d M') }}
            @endif
        </span>
    </div>

    {{-- Row 3: Line comment (print data) --}}
    @if($job->line_comment)
        <div class="mb-3 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
            <p class="font-mono text-xs text-blue-800" style="white-space:pre-wrap;word-break:break-word;">{{ $job->line_comment }}</p>
        </div>
    @endif

    {{-- Row 4: Order total · packs · required date --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px 16px;margin-bottom:0.75rem;font-size:0.875rem;color:#475569;">
        <span>
            <span class="text-xs text-slate-400">{{ str_starts_with($job->order_number, 'ASM-') ? 'SO Value:' : 'Net Price:' }} </span>
            <span class="font-medium text-slate-700">&pound;{{ number_format(str_starts_with($job->order_number, 'ASM-') ? (float)$job->order_total : (float)$job->line_total, 2) }}</span>
        </span>
        <span>
            <span class="text-xs text-slate-400">Total: </span>
            <span class="font-medium text-slate-700">{{ $job->order_quantity }}</span>
            <span class="text-slate-400 text-xs"> packs</span>
        </span>
        <span>
            <span class="text-xs text-slate-400">Balance: </span>
            <span class="font-medium {{ $job->remaining_quantity > 0 ? 'text-slate-700' : 'text-green-600' }}" id="remaining-qty-{{ $job->id }}">{{ $job->remaining_quantity }}</span>
            <span class="text-slate-400 text-xs"> packs</span>
        </span>

        {{-- Estimated out (machine boards only; updated live by JS) --}}
        <span id="est-out-{{ $job->id }}"
              style="{{ ($job->est_completion ?? null) ? '' : 'display:none;' }}font-size:0.7rem;color:#94a3b8;">@if($job->est_completion ?? null)&rarr; est del. {{ $job->est_completion->format('d M') }}@endif</span>

        {{-- Required date --}}
        <div class="flex items-center gap-1">
            {{-- Date display --}}
            <span id="date-display-{{ $job->id }}" class="flex items-center gap-1">
                <span class="text-xs text-slate-400">Delivery:</span>
                @if($requiredDateFmt)
                    <span id="date-text-{{ $job->id }}"
                        class="text-xs {{ $job->date_changed ? 'text-amber-600 font-medium' : 'text-slate-600' }}">
                        {{ $requiredDateFmt }}
                    </span>
                    @if($job->date_changed)
                        <button
                            id="date-changed-badge-{{ $job->id }}"
                            onclick="toggleDateLog({{ $job->id }})"
                            class="inline-flex items-center gap-0.5 text-amber-500 hover:text-amber-700 transition-colors text-xs"
                            title="Date changed — click to view history">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            (changed)
                        </button>
                    @else
                        <button
                            id="date-changed-badge-{{ $job->id }}"
                            onclick="toggleDateLog({{ $job->id }})"
                            class="hidden inline-flex items-center gap-0.5 text-amber-500 hover:text-amber-700 transition-colors text-xs">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            (changed)
                        </button>
                    @endif
                @else
                    <span id="date-text-{{ $job->id }}" class="text-xs text-slate-400 italic">No date</span>
                    <button id="date-changed-badge-{{ $job->id }}" class="hidden"></button>
                @endif
                <button onclick="editDate({{ $job->id }})" class="text-slate-300 hover:text-slate-600 transition-colors ml-0.5" title="Edit date">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
            </span>

            {{-- Inline date editor (hidden by default) --}}
            <span id="date-editor-{{ $job->id }}" style="display:none" class="items-center gap-1">
                <input type="date" id="date-input-{{ $job->id }}"
                    value="{{ $job->required_date ? $job->required_date->format('Y-m-d') : '' }}"
                    class="text-xs border border-slate-300 rounded px-2 py-0.5 focus:outline-none focus:ring-1 focus:ring-rose-500">
                <button id="date-save-btn-{{ $job->id }}"
                    onclick="saveDate({{ $job->id }})"
                    style="background:#e11d48;color:#fff;font-size:0.75rem;padding:2px 8px;border-radius:4px;border:none;cursor:pointer;">Save</button>
                <button onclick="cancelDate({{ $job->id }})"
                    style="font-size:0.75rem;color:#64748b;padding:2px 4px;background:none;border:none;cursor:pointer;">&#10005;</button>
            </span>
        </div>
    </div>

    {{-- Date change log (hidden by default) --}}
    @if($job->dateChanges->count() > 0)
        <div id="date-change-log-{{ $job->id }}" class="hidden mb-3 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
            <p class="text-xs font-medium text-amber-700 mb-1.5">Date change history:</p>
            <div class="space-y-1">
                @foreach($job->dateChanges->sortByDesc('created_at') as $change)
                    <p class="text-xs text-amber-700">
                        <span class="text-amber-400">{{ $change->created_at->format('d M Y, H:i') }}</span>
                        &mdash;
                        {{ $change->user?->name ?? 'System' }}:
                        {{ $change->old_date ? $change->old_date->format('d M Y') : 'none' }}
                        &rarr;
                        <strong>{{ $change->new_date ? $change->new_date->format('d M Y') : 'none' }}</strong>
                    </p>
                @endforeach
            </div>
        </div>
    @else
        <div id="date-change-log-{{ $job->id }}" class="hidden"></div>
    @endif

    {{-- Row 5: Material checked | Notes toggle | Part complete --}}
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

        {{-- Material checked toggle --}}
        <button id="material-btn-{{ $job->id }}"
            onclick="toggleMaterial({{ $job->id }})"
            class="inline-flex items-center gap-1.5 text-xs border rounded-lg px-3 py-1.5 transition-colors"
            style="{{ $job->material_checked
                ? 'background:#16a34a;color:#fff;border-color:#16a34a;'
                : 'background:#f8fafc;color:#64748b;border-color:#e2e8f0;' }}">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span id="material-label-{{ $job->id }}">{{ $job->material_checked ? 'Material checked' : 'Material?' }}</span>
        </button>

        {{-- Notes toggle --}}
        <button onclick="toggleNotes({{ $job->id }})"
            style="display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;border-radius:8px;padding:5px 12px;border:1px solid;cursor:pointer;transition:opacity 0.15s;
                {{ $noteCount > 0 ? 'background:#ffb66c;border-color:#e09040;color:#000;font-weight:600;' : 'background:#f8fafc;border-color:#e2e8f0;color:#64748b;font-weight:400;' }}">
            <svg style="width:14px;height:14px;flex-shrink:0;" viewBox="0 0 24 24" fill="{{ $noteCount > 0 ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            Notes
            <span id="note-count-{{ $job->id }}"
                style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 4px;font-size:0.7rem;border-radius:9px;
                    {{ $noteCount > 0 ? 'background:rgba(0,0,0,0.18);color:#000;font-weight:700;' : 'background:#e2e8f0;color:#64748b;' }}">{{ $noteCount }}</span>
        </button>

        {{-- Part complete --}}
        <div style="display:flex;align-items:center;gap:4px;">
            <span id="complete-display-{{ $job->id }}" style="display:flex;align-items:center;gap:4px;">
                <button onclick="editComplete({{ $job->id }})"
                    class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg px-3 py-1.5 transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 11 12 14 22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                    Part complete
                    <span class="text-slate-400">(<span id="completed-qty-{{ $job->id }}">{{ $job->quantity_completed }}</span>)</span>
                </button>
            </span>
            <span id="complete-editor-{{ $job->id }}" style="display:none" class="items-center gap-1">
                <input type="number" id="complete-input-{{ $job->id }}"
                    value="{{ $job->quantity_completed }}"
                    min="0" max="{{ $job->order_quantity }}"
                    class="text-xs border border-slate-300 rounded px-2 py-0.5 w-20 focus:outline-none focus:ring-1 focus:ring-rose-500">
                <span class="text-xs text-slate-400">/ {{ $job->order_quantity }}</span>
                <button id="complete-save-btn-{{ $job->id }}"
                    onclick="saveComplete({{ $job->id }})"
                    style="background:#e11d48;color:#fff;font-size:0.75rem;padding:2px 8px;border-radius:4px;border:none;cursor:pointer;">Save</button>
                <button onclick="cancelComplete({{ $job->id }})"
                    style="font-size:0.75rem;color:#64748b;padding:2px 4px;background:none;border:none;cursor:pointer;">&#10005;</button>
            </span>
        </div>
    </div>

    {{-- Manual job actions --}}
    @if($job->is_manual)
    <div style="display:flex;gap:8px;margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid #f1f5f9;">
        <button onclick="openEditManualModal({{ $job->id }}, {{ json_encode($job->product_code) }}, {{ json_encode($job->product_description) }}, {{ json_encode($job->line_comment) }}, {{ json_encode($job->customer_name) }}, {{ json_encode($job->customer_ref) }}, {{ json_encode($job->order_number) }}, {{ $job->order_quantity }}, '{{ $job->required_date ? $job->required_date->format('Y-m-d') : '' }}')"
            style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:6px 10px;background:#eff6ff;color:#1d4ed8;font-size:0.75rem;font-weight:600;border-radius:8px;border:1px solid #bfdbfe;cursor:pointer;"
            onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
            <svg style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit
        </button>
        <button onclick="archiveManualJob({{ $job->id }})"
            style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:6px 10px;background:#f8fafc;color:#64748b;font-size:0.75rem;font-weight:600;border-radius:8px;border:1px solid #e2e8f0;cursor:pointer;"
            onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#e2e8f0'">
            <svg style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/>
            </svg>
            Archive
        </button>
        <button onclick="deleteManualJob({{ $job->id }}, {{ json_encode($job->product_description) }})"
            title="Permanently delete this job"
            style="display:flex;align-items:center;justify-content:center;padding:6px 8px;background:#fff;color:#dc2626;font-size:0.75rem;border-radius:8px;border:1px solid #fecaca;cursor:pointer;flex-shrink:0;"
            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
            <svg style="width:13px;height:13px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
        </button>
    </div>
    @endif

    {{-- Notes panel (hidden by default) --}}
    <div id="notes-panel-{{ $job->id }}" style="display:none;margin-top:0.75rem;border-top:1px solid #f1f5f9;padding-top:0.75rem;">

        {{-- Existing notes --}}
        <div id="notes-list-{{ $job->id }}" style="display:flex;flex-direction:column;gap:8px;margin-bottom:0.75rem;">
            @forelse($job->notes->sortByDesc('created_at') as $note)
                <div class="note-item" style="display:flex;gap:8px;font-size:0.875rem;background:#f8fafc;border-radius:0.5rem;padding:0.75rem;" id="note-{{ $note->id }}">
                    <div class="flex-1 min-w-0">
                        <p class="text-slate-700">{{ $note->body }}</p>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ $note->user?->name ?? 'Unknown' }}
                            &bull;
                            {{ $note->created_at->format('d M Y, H:i') }}
                        </p>
                    </div>
                    <button onclick="deleteNote({{ $job->id }}, {{ $note->id }})"
                        class="text-slate-300 hover:text-red-500 transition-colors text-lg leading-none flex-shrink-0">&times;</button>
                </div>
            @empty
                {{-- Empty placeholder; will be replaced when first note added --}}
            @endforelse
        </div>

        {{-- Add note --}}
        <div style="display:flex;gap:8px;">
            <input type="text"
                id="note-input-{{ $job->id }}"
                placeholder="Add a note…"
                maxlength="1000"
                onkeydown="if(event.key==='Enter'){addNote({{ $job->id }})}"
                class="flex-1 text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent">
            <button id="note-add-btn-{{ $job->id }}"
                onclick="addNote({{ $job->id }})"
                class="text-sm bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg transition-colors">
                Add
            </button>
        </div>
    </div>

</div>
