<x-layout title="Reminders — Lockie Portal">
<main class="max-w-screen-xl mx-auto px-6 py-10">

    {{-- Page header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Order Reminders</h1>
            <p class="text-slate-500 mt-1 text-sm">Track and chase church envelope orders month by month.</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <form method="GET" action="{{ route('reminders.index') }}" class="flex items-center gap-2">
                <select name="month" onchange="this.form.submit()"
                    class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-sm text-slate-700 bg-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-sky-500">
                    @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()"
                    class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-sm text-slate-700 bg-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-sky-500">
                    @foreach($years as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('reminders.overview') }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.8125rem] font-semibold no-underline hover:bg-slate-50 transition-colors">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Overview
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $errors->first() }}</div>
    @endif

    {{-- Stats + actions bar --}}
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <span class="stat-count inline-flex items-center px-3 py-1 rounded-full text-[0.8125rem] font-semibold bg-slate-100 text-slate-600">{{ $totalCount }} total</span>
        <span class="stat-count inline-flex items-center px-3 py-1 rounded-full text-[0.8125rem] font-semibold bg-green-100 text-green-800">{{ $orderedCount }} ordered</span>
        <span class="stat-count inline-flex items-center px-3 py-1 rounded-full text-[0.8125rem] font-semibold bg-amber-100 text-amber-800">{{ $pendingCount }} pending</span>
        <div class="flex-1"></div>
        @if($totalCount > 0)
        <form action="{{ route('reminders.clear-month') }}" method="POST"
            onsubmit="return confirm('Delete all {{ $totalCount }} entries for {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}? This cannot be undone.')">
            @csrf
            <input type="hidden" name="year"  value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <button type="submit"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-red-300 bg-white text-red-600 text-[0.8125rem] font-semibold cursor-pointer hover:bg-red-50 transition-colors">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                Clear Month
            </button>
        </form>
        <button onclick="openExportModal()"
            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer hover:bg-slate-700 transition-colors">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export for Mailchimp
        </button>
        @endif
    </div>

    {{-- Import accordion --}}
    <div class="bg-white rounded-xl border border-slate-200 mb-6">
        <button onclick="toggleImports()" id="imports-toggle"
            class="w-full flex items-center justify-between px-5 py-3.5 bg-transparent border-0 cursor-pointer font-[inherit]">
            <span class="text-sm font-semibold text-slate-700">Import Data</span>
            <svg id="imports-chevron" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div id="imports-panel" style="display:none;" class="border-t border-slate-50">
            <div class="grid grid-cols-1 sm:grid-cols-3">
                <div class="p-5 border-r border-slate-50">
                    <p class="text-[0.8125rem] font-semibold text-slate-700 mb-1">Datafile Export</p>
                    <p class="text-xs text-slate-400 mb-3.5">Customer list for this month. Use Clear Month first to fully reset.</p>
                    <form action="{{ route('reminders.import-entries') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit" class="self-start px-3 py-1.5 rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer hover:bg-slate-700 transition-colors">Import Entries</button>
                    </form>
                </div>
                <div class="p-5 border-r border-slate-50">
                    <p class="text-[0.8125rem] font-semibold text-slate-700 mb-1">Phone Numbers</p>
                    <p class="text-xs text-slate-400 mb-3.5">Stock-Code, Telephone, Mobile. Merges both and applies to all months.</p>
                    <form action="{{ route('reminders.import-phones') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit" class="self-start px-3 py-1.5 rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer hover:bg-slate-700 transition-colors">Import Phones</button>
                    </form>
                </div>
                <div class="p-5">
                    <p class="text-[0.8125rem] font-semibold text-slate-700 mb-1">Orders (OK) Import</p>
                    <p class="text-xs text-slate-400 mb-3.5">Datafile order export. Marks accounts as Ordered across all months.</p>
                    <form action="{{ route('reminders.import-orders') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit" class="self-start px-3 py-1.5 rounded-lg bg-slate-900 text-white text-[0.8125rem] font-semibold border-0 cursor-pointer hover:bg-slate-700 transition-colors">Import Orders</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Status filter pills --}}
    <div class="flex flex-wrap gap-1.5 mb-4 items-center">
        <span class="text-[0.7rem] font-semibold text-slate-400 uppercase tracking-wide mr-1">Filter:</span>
        <button onclick="filterByStatus(null)" id="filter-all"
            style="display:inline-flex;align-items:center;padding:0.2rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:600;border:2px solid #0f172a;background:#0f172a;color:#fff;cursor:pointer;">All</button>
        @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
        @php $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#fff','text'=>'#334155']; @endphp
        <button onclick="filterByStatus('{{ $key }}')" data-status="{{ $key }}" class="filter-pill"
            style="display:inline-flex;align-items:center;padding:0.2rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:500;border:2px solid transparent;background:{{ $colours['bg'] }};color:{{ $colours['text'] }};cursor:pointer;outline:1px solid rgba(0,0,0,0.08);">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- Main table --}}
    @if($entries->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-12 text-center text-slate-400 text-sm">
        No entries for {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}. Use the import above to load this month's customers.
    </div>
    @else
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table id="reminders-table" class="w-full text-[0.8125rem] border-collapse" style="min-width:700px;">
            <thead>
                <tr class="bg-slate-50 border-b-2 border-slate-200">
                    <th class="px-3 py-2.5 text-left text-[0.695rem] font-bold text-slate-500 uppercase tracking-wide w-20">Account</th>
                    <th class="px-3 py-2.5 text-left text-[0.695rem] font-bold text-slate-500 uppercase tracking-wide">Name</th>
                    <th class="px-3 py-2.5 text-left text-[0.695rem] font-bold text-slate-500 uppercase tracking-wide">Email</th>
                    <th class="px-3 py-2.5 text-right text-[0.695rem] font-bold text-slate-500 uppercase tracking-wide w-20">Value</th>
                    <th class="px-3 py-2.5 text-left text-[0.695rem] font-bold text-slate-500 uppercase tracking-wide w-36">Phone</th>
                    <th class="px-3 py-2.5 text-left text-[0.695rem] font-bold text-slate-500 uppercase tracking-wide w-24">Last Called</th>
                    <th class="px-3 py-2.5 text-left text-[0.695rem] font-bold text-slate-500 uppercase tracking-wide w-48">Status</th>
                    <th class="px-3 py-2.5 text-center text-[0.695rem] font-bold text-slate-500 uppercase tracking-wide w-20">Ordered</th>
                    <th class="w-8"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                @php
                    $sc    = \App\Models\ReminderEntry::STATUS_COLOURS[$entry->status] ?? ['bg'=>'#ffffff','text'=>'#334155'];
                    $rowBg = $sc['bg'];
                @endphp

                {{-- Compact summary row --}}
                <tr id="row-{{ $entry->id }}" data-status="{{ $entry->status }}"
                    onclick="toggleRow({{ $entry->id }}, event)"
                    style="background-color:{{ $rowBg }};border-bottom:1px solid rgba(0,0,0,0.05);cursor:pointer;transition:filter 0.1s;"
                    onmouseover="this.style.filter='brightness(0.97)'" onmouseout="this.style.filter=''">

                    <td style="padding:0.625rem 0.75rem;font-weight:700;font-size:0.8rem;color:#334155;white-space:nowrap;font-family:monospace;">
                        {{ $entry->account_code }}
                    </td>
                    <td style="padding:0.625rem 0.75rem;color:#1e293b;font-weight:500;">
                        {{ $entry->name }}
                    </td>
                    <td style="padding:0.625rem 0.75rem;color:#0369a1;font-size:0.775rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        @if($entry->email)<a href="mailto:{{ $entry->email }}" onclick="event.stopPropagation()" style="color:#0369a1;text-decoration:none;" title="{{ $entry->email }}">{{ $entry->email }}</a>@endif
                    </td>
                    <td style="padding:0.625rem 0.75rem;text-align:right;color:#334155;font-weight:600;white-space:nowrap;">
                        @if($entry->order_value) £{{ number_format((float)$entry->order_value, 2) }} @endif
                    </td>
                    <td style="padding:0.625rem 0.75rem;color:#334155;font-family:monospace;font-size:0.775rem;white-space:nowrap;">
                        {{ $entry->phone }}
                    </td>
                    <td style="padding:0.625rem 0.75rem;color:#64748b;font-size:0.775rem;white-space:nowrap;">
                        {{ $entry->called_date ? $entry->called_date->format('d M Y') : '' }}
                    </td>
                    <td style="padding:0.5rem 0.625rem;">
                        <select data-field="status" onchange="updateEntry({{ $entry->id }}, 'status', this.value); updateStatusSelect(this)"
                            style="border:1px solid rgba(0,0,0,0.12);border-radius:6px;padding:0.3rem 0.5rem;font-size:0.75rem;font-weight:600;cursor:pointer;width:100%;background:{{ $sc['bg'] }};color:{{ $sc['text'] }};">
                            @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ $entry->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td style="padding:0.625rem 0.75rem;text-align:center;">
                        <span data-field="has_ordered" data-ordered="{{ $entry->has_ordered ? '1' : '0' }}">
                            @if($entry->has_ordered)
                            <span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.5rem;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;line-height:1rem;white-space:nowrap;">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Ordered
                            </span>
                            @else
                            <span style="color:#cbd5e1;font-size:0.8rem;">—</span>
                            @endif
                        </span>
                    </td>
                    <td style="padding:0.625rem 0.5rem;text-align:center;">
                        <svg id="chevron-{{ $entry->id }}" style="width:14px;height:14px;color:#94a3b8;transition:transform 0.2s;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </td>
                </tr>

                {{-- Expandable detail row --}}
                <tr id="detail-{{ $entry->id }}" style="display:none;" class="bg-slate-50 border-b-2 border-slate-200">
                    <td colspan="9" class="p-0">
                        <div class="px-6 py-5">

                            {{-- Two-column grid: Account Details | Call Log --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                                {{-- Left: Editable account details --}}
                                <div>
                                    <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Account Details</p>
                                    <p class="text-[0.7rem] text-slate-300 mb-3">Changes save automatically.</p>
                                    @php
                                        $inp = 'w-full px-2 py-1.5 border border-slate-200 rounded-lg text-[0.8rem] text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500';
                                        $lbl = 'block text-[0.7rem] text-slate-500 font-semibold uppercase tracking-wide mb-1';
                                    @endphp
                                    <div class="grid grid-cols-2 gap-x-3 gap-y-2">
                                        <div class="col-span-2">
                                            <label class="{{ $lbl }}">Name</label>
                                            <input type="text" data-field="name" value="{{ $entry->name }}"
                                                onblur="updateEntry({{ $entry->id }}, 'name', this.value || null)"
                                                class="{{ $inp }}">
                                        </div>
                                        <div>
                                            <label class="{{ $lbl }}">Address</label>
                                            <input type="text" data-field="add1" value="{{ $entry->add1 }}"
                                                onblur="updateEntry({{ $entry->id }}, 'add1', this.value || null)"
                                                class="{{ $inp }}">
                                        </div>
                                        <div>
                                            <label class="{{ $lbl }}">Postcode</label>
                                            <input type="text" data-field="postcode" value="{{ $entry->postcode }}"
                                                onblur="updateEntry({{ $entry->id }}, 'postcode', this.value || null)"
                                                class="{{ $inp }} font-mono">
                                        </div>
                                        <div class="col-span-2">
                                            <label class="{{ $lbl }}">Email</label>
                                            <input type="email" data-field="email" value="{{ $entry->email }}"
                                                onblur="updateEntry({{ $entry->id }}, 'email', this.value || null)"
                                                class="{{ $inp }}">
                                        </div>
                                        <div>
                                            <label class="{{ $lbl }}">Phone</label>
                                            <input type="text" data-field="phone" value="{{ $entry->phone }}"
                                                onblur="updateEntry({{ $entry->id }}, 'phone', this.value || null)"
                                                class="{{ $inp }} font-mono">
                                        </div>
                                        <div>
                                            <label class="{{ $lbl }}">Description</label>
                                            <input type="text" data-field="description" value="{{ $entry->description }}"
                                                onblur="updateEntry({{ $entry->id }}, 'description', this.value || null)"
                                                class="{{ $inp }}">
                                        </div>
                                        <div>
                                            <label class="{{ $lbl }}">Doc No</label>
                                            <input type="text" data-field="doc_no" value="{{ $entry->doc_no }}"
                                                onblur="updateEntry({{ $entry->id }}, 'doc_no', this.value || null)"
                                                class="{{ $inp }} font-mono">
                                        </div>
                                        <div>
                                            <label class="{{ $lbl }}">Env Sets</label>
                                            <input type="number" data-field="env_sets" value="{{ $entry->env_sets ? (int)(float)$entry->env_sets : '' }}"
                                                onblur="updateEntry({{ $entry->id }}, 'env_sets', this.value || null)"
                                                class="{{ $inp }}">
                                        </div>
                                        <div>
                                            <label class="{{ $lbl }}">Box Colour</label>
                                            <input type="text" data-field="box_colour" value="{{ $entry->box_colour ? strtolower($entry->box_colour) : '' }}"
                                                onblur="updateEntry({{ $entry->id }}, 'box_colour', this.value || null)"
                                                class="{{ $inp }}">
                                        </div>
                                        <div>
                                            <label class="{{ $lbl }}">Env Colour</label>
                                            <input type="text" data-field="env_colour" value="{{ $entry->env_colour ? strtolower($entry->env_colour) : '' }}"
                                                onblur="updateEntry({{ $entry->id }}, 'env_colour', this.value || null)"
                                                class="{{ $inp }}">
                                        </div>
                                    </div>
                                </div>

                                {{-- Right: Call log --}}
                                <div class="border-l border-slate-200 pl-6">
                                    <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-3">Call Log</p>
                                    <div class="flex flex-col gap-3">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="{{ $lbl }}">Called By</label>
                                                <select data-field="called_by_user_id"
                                                    onchange="updateEntry({{ $entry->id }}, 'called_by_user_id', this.value || null)"
                                                    class="w-full px-2 py-1.5 border border-slate-200 rounded-lg text-sm text-slate-700 bg-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-sky-500">
                                                    <option value="">— Not called —</option>
                                                    @foreach($users as $user)
                                                    <option value="{{ $user->id }}" {{ $entry->called_by_user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="{{ $lbl }}">Called Date</label>
                                                <input type="date" data-field="called_date" value="{{ $entry->called_date?->format('Y-m-d') }}"
                                                    onchange="updateEntry({{ $entry->id }}, 'called_date', this.value || null)"
                                                    class="w-full px-2 py-1.5 border border-slate-200 rounded-lg text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="{{ $lbl }}">Call Notes</label>
                                            <input type="text" data-field="call_notes" value="{{ $entry->call_notes }}"
                                                onblur="updateEntry({{ $entry->id }}, 'call_notes', this.value || null)"
                                                placeholder="e.g. Left voicemail, call back Friday…"
                                                class="w-full px-2.5 py-1.5 border border-slate-200 rounded-lg text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                                        </div>
                                    </div>
                                </div>

                            </div>

                            {{-- Move to month --}}
                            <div class="border-t border-slate-200 mt-4 pt-3.5 flex items-center gap-3 flex-wrap">
                                <span class="text-xs font-semibold text-slate-500 whitespace-nowrap">Move to month:</span>
                                <select id="move-month-{{ $entry->id }}"
                                    class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm text-slate-700 bg-white cursor-pointer focus:outline-none">
                                    @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                                    @endforeach
                                </select>
                                <select id="move-year-{{ $entry->id }}"
                                    class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm text-slate-700 bg-white cursor-pointer focus:outline-none">
                                    @foreach($years as $y)
                                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                                <button onclick="moveEntryToMonth({{ $entry->id }}, this)"
                                    class="px-3.5 py-1.5 rounded-lg bg-red-600 text-white border-0 text-sm font-semibold cursor-pointer whitespace-nowrap hover:bg-red-700 transition-colors">
                                    Move →
                                </button>
                            </div>

                        </div>
                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif

</main>

{{-- Export modal --}}
<div id="export-modal" style="display:none;" class="fixed inset-0 z-[100] bg-black/40 items-center justify-center">
    <div class="bg-white rounded-[14px] shadow-2xl w-full max-w-[560px] mx-4 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-50 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-950">Export for Mailchimp</h2>
            <button onclick="closeExportModal()" class="bg-transparent border-0 cursor-pointer text-slate-400 p-1 leading-none hover:text-slate-600 transition-colors">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="export-form" action="{{ route('reminders.export') }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <div class="mb-5">
                <div class="flex items-center justify-between mb-2.5">
                    <p class="text-[0.8125rem] font-bold text-slate-700">Months to export</p>
                    <div class="flex gap-2">
                        <button type="button" onclick="checkAllMonths(true)"  class="text-[0.7rem] text-sky-700 bg-transparent border-0 cursor-pointer p-0">All</button>
                        <button type="button" onclick="checkAllMonths(false)" class="text-[0.7rem] text-slate-400 bg-transparent border-0 cursor-pointer p-0">None</button>
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-1.5">
                    @foreach(range(1,12) as $m)
                    <label class="flex items-center gap-1.5 text-[0.8125rem] text-slate-700 px-2 py-1.5 border border-slate-200 rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="months[]" value="{{ $m }}" {{ $m == $month ? 'checked' : '' }} class="w-3.5 h-3.5 cursor-pointer accent-slate-900">
                        {{ date('M', mktime(0,0,0,$m,1)) }}
                    </label>
                    @endforeach
                </div>
            </div>
            @php $defaultExportStatuses = ['pending']; @endphp
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2.5">
                    <p class="text-[0.8125rem] font-bold text-slate-700">Statuses to include</p>
                    <div class="flex gap-2">
                        <button type="button" onclick="checkAllStatuses(true)"  class="text-[0.7rem] text-sky-700 bg-transparent border-0 cursor-pointer p-0">All</button>
                        <button type="button" onclick="checkAllStatuses(false)" class="text-[0.7rem] text-slate-400 bg-transparent border-0 cursor-pointer p-0">None</button>
                    </div>
                </div>
                <div class="flex flex-col gap-1">
                    @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                    @php $sc2 = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#f8fafc','text'=>'#334155']; @endphp
                    <label style="background:{{ $sc2['bg'] }};" class="flex items-center gap-2 text-[0.8125rem] px-2.5 py-1.5 rounded-lg cursor-pointer">
                        <input type="checkbox" name="statuses[]" value="{{ $key }}" {{ in_array($key, $defaultExportStatuses) ? 'checked' : '' }} class="w-3.5 h-3.5 cursor-pointer accent-slate-900">
                        <span style="color:{{ $sc2['text'] }};" class="font-medium">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeExportModal()" class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-500 text-sm font-semibold cursor-pointer hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold border-0 cursor-pointer hover:bg-slate-700 transition-colors">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download Excel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var csrfToken     = document.querySelector('meta[name="csrf-token"]').content;
    var statusColours = @json(\App\Models\ReminderEntry::STATUS_COLOURS);
    var activeFilter  = null;

    // ── Row expand/collapse ───────────────────────────────────────────────────
    window.toggleRow = function (id, event) {
        if (['SELECT','OPTION','INPUT','BUTTON','A','LABEL','SVG','POLYLINE'].includes(event.target.tagName.toUpperCase())) return;
        var detail  = document.getElementById('detail-' + id);
        var chevron = document.getElementById('chevron-' + id);
        var isOpen  = detail.style.display !== 'none';
        // Close all others
        document.querySelectorAll('tr[id^="detail-"]').forEach(function (r) { r.style.display = 'none'; });
        document.querySelectorAll('[id^="chevron-"]').forEach(function (c) { c.style.transform = ''; });
        if (!isOpen) {
            detail.style.display = '';
            if (chevron) chevron.style.transform = 'rotate(180deg)';
        }
    };

    // ── Inline save ───────────────────────────────────────────────────────────
    window.updateEntry = function (id, field, value) {
        var body = {}; body[field] = value;
        fetch('/reminders/entries/' + id, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        }).then(function (r) {
            if (!r.ok) { console.error('Update failed'); return; }
            var row = document.getElementById('row-' + id);
            if (field === 'status') {
                var colours = statusColours[value] || { bg: '#ffffff' };
                if (row) {
                    row.setAttribute('data-status', value);
                    row.style.backgroundColor = colours.bg;
                    if (activeFilter && value !== activeFilter) row.style.display = 'none';
                    else if (activeFilter) row.style.display = '';
                }
                var detail = document.getElementById('detail-' + id);
                if (detail && detail.style.display !== 'none') detail.style.backgroundColor = colours.bg + '55';
                refreshStats();
            }
            if (field === 'called_date' && row) {
                var cells = row.querySelectorAll('td');
                if (cells[5]) {
                    if (value) {
                        var d = new Date(value);
                        var mn = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        cells[5].textContent = d.getDate().toString().padStart(2,'0') + ' ' + mn[d.getMonth()] + ' ' + d.getFullYear();
                    } else { cells[5].textContent = ''; }
                }
            }
            if (field === 'name' && row) {
                var cells = row.querySelectorAll('td');
                if (cells[1]) cells[1].textContent = value || '';
            }
            if (field === 'email' && row) {
                var cells = row.querySelectorAll('td');
                if (cells[2]) {
                    if (value) {
                        var safe = value.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                        cells[2].innerHTML = '<a href="mailto:' + safe + '" onclick="event.stopPropagation()" style="color:#0369a1;text-decoration:none;" title="' + safe + '">' + safe + '</a>';
                    } else { cells[2].innerHTML = ''; }
                }
            }
            if (field === 'phone' && row) {
                var cells = row.querySelectorAll('td');
                if (cells[4]) cells[4].textContent = value || '';
            }
        }).catch(function (e) { console.error(e); });
    };

    window.moveEntryToMonth = function (id, btn) {
        var newMonth = parseInt(document.getElementById('move-month-' + id).value);
        var newYear  = parseInt(document.getElementById('move-year-' + id).value);
        var mNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        if (!confirm('Move this entry to ' + mNames[newMonth - 1] + ' ' + newYear + '?\n\nIt will no longer appear in the current month.')) return;
        btn.disabled = true;
        btn.textContent = 'Moving…';
        fetch('/reminders/entries/' + id + '/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ year: newYear, month: newMonth })
        }).then(function (r) {
            return r.json().then(function (data) {
                if (!r.ok) { alert(data.error || 'Move failed.'); btn.disabled = false; btn.textContent = 'Move →'; return; }
                var row = document.getElementById('row-' + id);
                var det = document.getElementById('detail-' + id);
                if (row) row.remove();
                if (det) det.remove();
                refreshStats();
            });
        }).catch(function () { alert('Move failed. Please try again.'); btn.disabled = false; btn.textContent = 'Move →'; });
    };

    window.updateStatusSelect = function (sel) {
        var colours = statusColours[sel.value] || { bg: '#ffffff', text: '#334155' };
        sel.style.background = colours.bg;
        sel.style.color      = colours.text;
    };

    // ── Status filter ─────────────────────────────────────────────────────────
    window.filterByStatus = function (status) {
        activeFilter = status;
        document.querySelectorAll('#reminders-table tbody tr[id^="row-"]').forEach(function (row) {
            var show = !status || row.getAttribute('data-status') === status;
            var id   = row.id.replace('row-', '');
            row.style.display = show ? '' : 'none';
            var detail = document.getElementById('detail-' + id);
            if (detail) detail.style.display = 'none'; // collapse detail on filter change
            var chevron = document.getElementById('chevron-' + id);
            if (chevron) chevron.style.transform = '';
        });
        var allBtn = document.getElementById('filter-all');
        allBtn.style.background   = status ? '#f1f5f9' : '#0f172a';
        allBtn.style.color        = status ? '#475569' : '#fff';
        allBtn.style.borderColor  = status ? 'transparent' : '#0f172a';
        document.querySelectorAll('.filter-pill').forEach(function (pill) {
            var active = pill.getAttribute('data-status') === status;
            pill.style.outline      = active ? '2px solid #0f172a' : '1px solid rgba(0,0,0,0.08)';
            pill.style.outlineOffset = active ? '1px' : '0';
        });
    };

    // ── Stats refresh ─────────────────────────────────────────────────────────
    function refreshStats() {
        var rows = document.querySelectorAll('#reminders-table tbody tr[id^="row-"]');
        var total = rows.length, ordered = 0, pending = 0;
        rows.forEach(function (row) {
            var s = row.getAttribute('data-status');
            if (s === 'order_placed') ordered++;
            if (s === 'pending')      pending++;
        });
        var els = document.querySelectorAll('.stat-count');
        if (els[0]) els[0].textContent = total   + ' total';
        if (els[1]) els[1].textContent = ordered + ' ordered';
        if (els[2]) els[2].textContent = pending + ' pending';
    }

    // ── Export modal ──────────────────────────────────────────────────────────
    window.openExportModal = function () {
        document.getElementById('export-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };
    window.closeExportModal = function () {
        document.getElementById('export-modal').style.display = 'none';
        document.body.style.overflow = '';
    };
    document.getElementById('export-modal').addEventListener('click', function (e) { if (e.target === this) closeExportModal(); });
    document.getElementById('export-form').addEventListener('submit', function () { setTimeout(closeExportModal, 800); });
    window.checkAllMonths   = function (v) { document.querySelectorAll('#export-form input[name="months[]"]').forEach(function(c){c.checked=v;}); };
    window.checkAllStatuses = function (v) { document.querySelectorAll('#export-form input[name="statuses[]"]').forEach(function(c){c.checked=v;}); };

    // ── Imports accordion ─────────────────────────────────────────────────────
    window.toggleImports = function () {
        var panel = document.getElementById('imports-panel');
        var ch    = document.getElementById('imports-chevron');
        var open  = panel.style.display !== 'none';
        panel.style.display = open ? 'none' : 'block';
        if (ch) ch.style.transform = open ? '' : 'rotate(180deg)';
        localStorage.setItem('reminders_imports_open', open ? '0' : '1');
    };
    if (localStorage.getItem('reminders_imports_open') === '1') {
        var p = document.getElementById('imports-panel'), c = document.getElementById('imports-chevron');
        if (p) p.style.display = 'block';
        if (c) c.style.transform = 'rotate(180deg)';
    }
    @if($errors->hasAny(['file', 'phones_file', 'orders_file']))
    (function(){ var p=document.getElementById('imports-panel'),c=document.getElementById('imports-chevron'); if(p)p.style.display='block'; if(c)c.style.transform='rotate(180deg)'; })();
    @endif

    // ── Real-time polling ─────────────────────────────────────────────────────
    @if($entries->isNotEmpty())
    var pollUrl = '{!! route("reminders.poll", ["year" => $year, "month" => $month]) !!}';
    var lastSeen = {}, myPending = {};
    @foreach($entries as $entry)
    lastSeen[{{ $entry->id }}] = '{{ $entry->updated_at }}';
    @endforeach

    function applyPollData(data) {
        Object.keys(data).forEach(function (id) {
            var row = document.getElementById('row-' + id);
            if (!row) return;
            var entry = data[id];
            if (lastSeen[id] === entry.updated_at) return;
            lastSeen[id] = entry.updated_at;
            if (myPending[id]) return;

            var colours = statusColours[entry.status] || { bg: '#ffffff' };
            row.setAttribute('data-status', entry.status);
            row.style.backgroundColor = colours.bg;
            if (activeFilter && entry.status !== activeFilter) row.style.display = 'none';

            var s = row.querySelector('select[data-field="status"]');
            if (s) { s.value = entry.status; updateStatusSelect(s); }

            // Detail row fields
            var det = document.getElementById('detail-' + id);
            if (det) {
                var c = det.querySelector('select[data-field="called_by_user_id"]'); if (c) c.value = entry.called_by_user_id || '';
                var d = det.querySelector('input[data-field="called_date"]');        if (d) d.value = entry.called_date || '';
                var n = det.querySelector('input[data-field="call_notes"]');         if (n && document.activeElement !== n) n.value = entry.call_notes || '';
            }

            // Ordered badge
            var ord = row.querySelector('[data-field="has_ordered"]');
            if (ord) {
                ord.setAttribute('data-ordered', entry.has_ordered ? '1' : '0');
                ord.innerHTML = entry.has_ordered
                    ? '<span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.5rem;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;line-height:1rem;white-space:nowrap;"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Ordered</span>'
                    : '<span style="color:#cbd5e1;font-size:0.8rem;">—</span>';
            }
        });
        refreshStats();
    }
    function doPoll() {
        fetch(pollUrl, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } })
            .then(function(r){return r.json();}).then(applyPollData).catch(function(){});
    }
    setInterval(doPoll, 10000);
    var origUpdate = window.updateEntry;
    window.updateEntry = function(id, field, value) {
        myPending[id] = true; origUpdate(id, field, value);
        setTimeout(function(){ delete myPending[id]; }, 3000);
    };
    @endif
})();
</script>
</x-layout>
