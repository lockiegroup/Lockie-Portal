<x-layout title="Reminders — Lockie Portal">
<main class="max-w-screen-xl mx-auto px-6 py-10">

    {{-- Page header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Order Reminders</h1>
            <p class="text-slate-500 mt-1 text-sm">Track and chase church envelope orders month by month.</p>
        </div>

        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <form method="GET" action="{{ route('reminders.index') }}" style="display:flex;align-items:center;gap:0.5rem;">
                <select name="month" onchange="this.form.submit()"
                    style="border:1px solid #e2e8f0;border-radius:8px;padding:6px 10px;font-size:0.875rem;color:#334155;background:#fff;cursor:pointer;">
                    @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()"
                    style="border:1px solid #e2e8f0;border-radius:8px;padding:6px 10px;font-size:0.875rem;color:#334155;background:#fff;cursor:pointer;">
                    @foreach($years as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('reminders.overview') }}"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;font-weight:600;text-decoration:none;">
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
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem;margin-bottom:1.25rem;">
        <span class="stat-count" style="display:inline-flex;align-items:center;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:600;background:#f1f5f9;color:#475569;">{{ $totalCount }} total</span>
        <span class="stat-count" style="display:inline-flex;align-items:center;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:600;background:#dcfce7;color:#166534;">{{ $orderedCount }} ordered</span>
        <span class="stat-count" style="display:inline-flex;align-items:center;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:600;background:#fef3c7;color:#92400e;">{{ $pendingCount }} pending</span>
        <div style="flex:1;"></div>
        @if($totalCount > 0)
        <form action="{{ route('reminders.clear-month') }}" method="POST"
            onsubmit="return confirm('Delete all {{ $totalCount }} entries for {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}? This cannot be undone.')">
            @csrf
            <input type="hidden" name="year"  value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <button type="submit"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;border-radius:8px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                Clear Month
            </button>
        </form>
        <button onclick="openExportModal()"
            style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.4rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export for Mailchimp
        </button>
        @endif
    </div>

    {{-- Import accordion --}}
    <div class="bg-white rounded-xl border border-slate-200 mb-6">
        <button onclick="toggleImports()" id="imports-toggle"
            style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:0.875rem 1.25rem;background:none;border:none;cursor:pointer;font-family:inherit;">
            <span style="font-size:0.875rem;font-weight:600;color:#334155;">Import Data</span>
            <svg id="imports-chevron" style="width:14px;height:14px;color:#94a3b8;transition:transform 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div id="imports-panel" style="display:none;border-top:1px solid #f1f5f9;">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
                <div style="padding:1.25rem;border-right:1px solid #f1f5f9;">
                    <p style="font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.25rem;">Datafile Export</p>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-bottom:0.875rem;">Customer list for this month. Use Clear Month first to fully reset.</p>
                    <form action="{{ route('reminders.import-entries') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit" style="align-self:flex-start;padding:0.375rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">Import Entries</button>
                    </form>
                </div>
                <div style="padding:1.25rem;border-right:1px solid #f1f5f9;">
                    <p style="font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.25rem;">Phone Numbers</p>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-bottom:0.875rem;">Stock-Code, Telephone, Mobile. Merges both and applies to all months.</p>
                    <form action="{{ route('reminders.import-phones') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit" style="align-self:flex-start;padding:0.375rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">Import Phones</button>
                    </form>
                </div>
                <div style="padding:1.25rem;">
                    <p style="font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.25rem;">Orders (OK) Import</p>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-bottom:0.875rem;">Datafile order export. Marks accounts as Ordered across all months.</p>
                    <form action="{{ route('reminders.import-orders') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit" style="align-self:flex-start;padding:0.375rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">Import Orders</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Status filter pills --}}
    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-bottom:1rem;align-items:center;">
        <span style="font-size:0.7rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-right:0.25rem;">Filter:</span>
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
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:3rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
        No entries for {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}. Use the import above to load this month's customers.
    </div>
    @else
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
        <table id="reminders-table" style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.695rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;width:80px;">Account</th>
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.695rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Name</th>
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.695rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Email</th>
                    <th style="padding:0.625rem 0.75rem;text-align:right;font-size:0.695rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;width:80px;">Value</th>
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.695rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;width:150px;">Phone</th>
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.695rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;width:100px;">Last Called</th>
                    <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.695rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;width:195px;">Status</th>
                    <th style="padding:0.625rem 0.75rem;text-align:center;font-size:0.695rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;width:75px;">Ordered</th>
                    <th style="width:32px;"></th>
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
                <tr id="detail-{{ $entry->id }}" style="display:none;background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <td colspan="9" style="padding:0;">
                        <div style="padding:1.25rem 1.5rem;display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

                            {{-- Left: Import data --}}
                            <div>
                                <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.75rem;">Account Details</p>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem 1.5rem;">
                                    @if($entry->description)
                                    <div style="grid-column:span 2;">
                                        <p style="font-size:0.7rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.125rem;">Description</p>
                                        <p style="font-size:0.8125rem;color:#334155;">{{ $entry->description }}</p>
                                    </div>
                                    @endif
                                    @if($entry->add1)
                                    <div>
                                        <p style="font-size:0.7rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.125rem;">Address</p>
                                        <p style="font-size:0.8125rem;color:#334155;">{{ $entry->add1 }}</p>
                                    </div>
                                    @endif
                                    @if($entry->postcode)
                                    <div>
                                        <p style="font-size:0.7rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.125rem;">Postcode</p>
                                        <p style="font-size:0.8125rem;color:#334155;font-family:monospace;">{{ $entry->postcode }}</p>
                                    </div>
                                    @endif
                                    @if($entry->doc_no)
                                    <div>
                                        <p style="font-size:0.7rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.125rem;">Doc No</p>
                                        <p style="font-size:0.8125rem;color:#334155;font-family:monospace;">{{ $entry->doc_no }}</p>
                                    </div>
                                    @endif
                                    @if($entry->env_sets)
                                    <div>
                                        <p style="font-size:0.7rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.125rem;">Env Sets</p>
                                        <p style="font-size:0.8125rem;color:#334155;">{{ number_format((float)$entry->env_sets, 0) }}</p>
                                    </div>
                                    @endif
                                    @if($entry->box_colour || $entry->env_colour)
                                    <div style="grid-column:span 2;display:flex;gap:1.5rem;">
                                        @if($entry->box_colour)
                                        <div>
                                            <p style="font-size:0.7rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.125rem;">Box</p>
                                            <p style="font-size:0.8125rem;color:#334155;text-transform:capitalize;">{{ strtolower($entry->box_colour) }}</p>
                                        </div>
                                        @endif
                                        @if($entry->env_colour)
                                        <div>
                                            <p style="font-size:0.7rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.125rem;">Envelope</p>
                                            <p style="font-size:0.8125rem;color:#334155;text-transform:capitalize;">{{ strtolower($entry->env_colour) }}</p>
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Right: Call tracking --}}
                            <div style="border-left:1px solid #e2e8f0;padding-left:1.5rem;">
                                <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.75rem;">Call Log</p>
                                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                                        <div>
                                            <label style="font-size:0.7rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:0.25rem;">Called By</label>
                                            <select data-field="called_by_user_id"
                                                onchange="updateEntry({{ $entry->id }}, 'called_by_user_id', this.value || null)"
                                                style="border:1px solid #e2e8f0;border-radius:7px;padding:0.375rem 0.5rem;font-size:0.8125rem;color:#334155;background:#fff;cursor:pointer;width:100%;">
                                                <option value="">— Not called —</option>
                                                @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ $entry->called_by_user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label style="font-size:0.7rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:0.25rem;">Called Date</label>
                                            <input type="date" data-field="called_date" value="{{ $entry->called_date?->format('Y-m-d') }}"
                                                onchange="updateEntry({{ $entry->id }}, 'called_date', this.value || null)"
                                                style="border:1px solid #e2e8f0;border-radius:7px;padding:0.375rem 0.5rem;font-size:0.8125rem;color:#334155;background:#fff;width:100%;">
                                        </div>
                                    </div>
                                    <div>
                                        <label style="font-size:0.7rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:0.25rem;">Call Notes</label>
                                        <input type="text" data-field="call_notes" value="{{ $entry->call_notes }}"
                                            onblur="updateEntry({{ $entry->id }}, 'call_notes', this.value || null)"
                                            placeholder="e.g. Left voicemail, call back Friday…"
                                            style="border:1px solid #e2e8f0;border-radius:7px;padding:0.375rem 0.625rem;font-size:0.8125rem;color:#334155;background:#fff;width:100%;box-sizing:border-box;">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</main>

{{-- Export modal --}}
<div id="export-modal" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,0.2);width:100%;max-width:560px;margin:1rem;overflow:hidden;">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <h2 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0;">Export for Mailchimp</h2>
            <button onclick="closeExportModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="export-form" action="{{ route('reminders.export') }}" method="POST" style="padding:1.5rem;">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <div style="margin-bottom:1.25rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.625rem;">
                    <p style="font-size:0.8125rem;font-weight:700;color:#334155;margin:0;">Months to export</p>
                    <div style="display:flex;gap:0.5rem;">
                        <button type="button" onclick="checkAllMonths(true)"  style="font-size:0.7rem;color:#0369a1;background:none;border:none;cursor:pointer;padding:0;">All</button>
                        <button type="button" onclick="checkAllMonths(false)" style="font-size:0.7rem;color:#94a3b8;background:none;border:none;cursor:pointer;padding:0;">None</button>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.375rem;">
                    @foreach(range(1,12) as $m)
                    <label style="display:flex;align-items:center;gap:0.375rem;font-size:0.8125rem;color:#334155;padding:0.375rem 0.5rem;border:1px solid #e2e8f0;border-radius:7px;cursor:pointer;background:#fafafa;">
                        <input type="checkbox" name="months[]" value="{{ $m }}" {{ $m == $month ? 'checked' : '' }} style="width:14px;height:14px;cursor:pointer;accent-color:#0f172a;">
                        {{ date('M', mktime(0,0,0,$m,1)) }}
                    </label>
                    @endforeach
                </div>
            </div>
            @php $defaultExportStatuses = ['pending', 'unable_to_contact', 'using_spares', 'moved_stock']; @endphp
            <div style="margin-bottom:1.5rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.625rem;">
                    <p style="font-size:0.8125rem;font-weight:700;color:#334155;margin:0;">Statuses to include</p>
                    <div style="display:flex;gap:0.5rem;">
                        <button type="button" onclick="checkAllStatuses(true)"  style="font-size:0.7rem;color:#0369a1;background:none;border:none;cursor:pointer;padding:0;">All</button>
                        <button type="button" onclick="checkAllStatuses(false)" style="font-size:0.7rem;color:#94a3b8;background:none;border:none;cursor:pointer;padding:0;">None</button>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:0.25rem;">
                    @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                    @php $sc2 = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#f8fafc','text'=>'#334155']; @endphp
                    <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;padding:0.375rem 0.625rem;border-radius:7px;cursor:pointer;background:{{ $sc2['bg'] }};">
                        <input type="checkbox" name="statuses[]" value="{{ $key }}" {{ in_array($key, $defaultExportStatuses) ? 'checked' : '' }} style="width:14px;height:14px;cursor:pointer;accent-color:#0f172a;">
                        <span style="color:{{ $sc2['text'] }};font-weight:500;">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div style="display:flex;gap:0.75rem;justify-content:flex-end;">
                <button type="button" onclick="closeExportModal()" style="padding:0.5rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:0.875rem;font-weight:600;cursor:pointer;">Cancel</button>
                <button type="submit" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1.25rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.875rem;font-weight:600;border:none;cursor:pointer;">
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
            if (field === 'status') {
                var colours = statusColours[value] || { bg: '#ffffff' };
                var row = document.getElementById('row-' + id);
                if (row) {
                    row.setAttribute('data-status', value);
                    row.style.backgroundColor = colours.bg;
                    if (activeFilter && value !== activeFilter) row.style.display = 'none';
                    else if (activeFilter) row.style.display = '';
                }
                // Also update detail row background
                var detail = document.getElementById('detail-' + id);
                if (detail && detail.style.display !== 'none') detail.style.backgroundColor = colours.bg + '55';
                refreshStats();
            }
            if (field === 'called_date') {
                // Update the "Last Called" display in the summary row
                var row = document.getElementById('row-' + id);
                if (row) {
                    var cells = row.querySelectorAll('td');
                    if (cells[5]) {
                        if (value) {
                            var d = new Date(value);
                            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                            cells[5].textContent = d.getDate().toString().padStart(2,'0') + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
                        } else {
                            cells[5].textContent = '';
                        }
                    }
                }
            }
        }).catch(function (e) { console.error(e); });
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
    var pollUrl = '{!! route('reminders.poll', ['year' => $year, 'month' => $month]) !!'}';
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
