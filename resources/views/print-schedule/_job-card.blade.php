@php
    $requiredDateFmt = $job->required_date ? $job->required_date->format('d M Y') : null;
    $noteCount       = $job->notes->count();
@endphp

<div class="job-card bg-white rounded-xl border border-slate-200 shadow-sm p-4 select-none"
     data-job-id="{{ $job->id }}"
     data-current-board="{{ $job->board }}"
     data-remaining="{{ $job->remaining_quantity }}"
     id="job-card-{{ $job->id }}">

    {{-- Row 1: Drag handle | Order number | Customer | Board select --}}
    <div class="flex items-start gap-3 mb-3">
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
            </div>
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

    {{-- Row 3: Line comment (print data) --}}
    @if($job->line_comment)
        <div class="mb-3 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
            <p class="text-xs text-blue-400 font-medium mb-0.5 uppercase tracking-wide">Print data:</p>
            <p class="font-mono text-xs text-blue-800 whitespace-pre-wrap break-words">{{ $job->line_comment }}</p>
        </div>
    @endif

    {{-- Row 4: Order total · packs · required date --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mb-3 text-sm text-slate-600">
        <span class="font-medium text-slate-700">&pound;{{ number_format((float)$job->order_total, 2) }}</span>
        <span>
            <span id="remaining-qty-{{ $job->id }}">{{ $job->remaining_quantity }}</span>/<span>{{ $job->order_quantity }}</span>
            <span class="text-slate-400 text-xs">packs</span>
        </span>

        {{-- Required date --}}
        <div class="flex items-center gap-1">
            {{-- Date display --}}
            <span id="date-display-{{ $job->id }}" class="flex items-center gap-1">
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
                    class="text-xs bg-rose-600 hover:bg-rose-700 text-white px-2 py-0.5 rounded transition-colors">Save</button>
                <button onclick="cancelDate({{ $job->id }})"
                    class="text-xs text-slate-500 hover:text-slate-700 px-1">&#10005;</button>
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

    {{-- Row 5: Notes toggle | Part complete --}}
    <div class="flex items-center gap-2 flex-wrap">

        {{-- Notes toggle --}}
        <button onclick="toggleNotes({{ $job->id }})"
            class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg px-3 py-1.5 transition-colors">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            Notes
            <span id="note-count-{{ $job->id }}"
                class="inline-flex items-center justify-center w-4 h-4 text-xs rounded-full bg-slate-200 text-slate-600">{{ $noteCount }}</span>
        </button>

        {{-- Part complete --}}
        <div class="flex items-center gap-1">
            <span id="complete-display-{{ $job->id }}" class="flex items-center gap-1">
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
            <span id="complete-editor-{{ $job->id }}" class="hidden flex items-center gap-1">
                <input type="number" id="complete-input-{{ $job->id }}"
                    value="{{ $job->quantity_completed }}"
                    min="0" max="{{ $job->order_quantity }}"
                    class="text-xs border border-slate-300 rounded px-2 py-0.5 w-20 focus:outline-none focus:ring-1 focus:ring-rose-500">
                <span class="text-xs text-slate-400">/ {{ $job->order_quantity }}</span>
                <button id="complete-save-btn-{{ $job->id }}"
                    onclick="saveComplete({{ $job->id }})"
                    class="text-xs bg-rose-600 hover:bg-rose-700 text-white px-2 py-0.5 rounded transition-colors">Save</button>
                <button onclick="cancelComplete({{ $job->id }})"
                    class="text-xs text-slate-500 hover:text-slate-700 px-1">&#10005;</button>
            </span>
        </div>
    </div>

    {{-- Notes panel (hidden by default) --}}
    <div id="notes-panel-{{ $job->id }}" class="hidden mt-3 border-t border-slate-100 pt-3">

        {{-- Existing notes --}}
        <div id="notes-list-{{ $job->id }}" class="space-y-2 mb-3">
            @forelse($job->notes->sortByDesc('created_at') as $note)
                <div class="note-item flex gap-2 text-sm bg-slate-50 rounded-lg p-3" id="note-{{ $note->id }}">
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
        <div class="flex gap-2">
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
