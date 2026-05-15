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
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
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
        <span class="stat-count" style="display:inline-flex;align-items:center;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:600;background:#f1f5f9;color:#475569;">
            {{ $totalCount }} total
        </span>
        <span class="stat-count" style="display:inline-flex;align-items:center;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:600;background:#dcfce7;color:#166534;">
            {{ $orderedCount }} ordered
        </span>
        <span class="stat-count" style="display:inline-flex;align-items:center;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:600;background:#fef3c7;color:#92400e;">
            {{ $pendingCount }} outstanding
        </span>

        <div style="flex:1;"></div>

        @if($totalCount > 0)
        {{-- Column picker --}}
        <div style="position:relative;">
            <button onclick="toggleColPicker(event)"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
                Columns
            </button>
            <div id="col-picker" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,0.10);padding:0.75rem 1rem;z-index:50;min-width:220px;">
                <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.5rem;">Data Columns</p>
                @foreach([
                    'address'     => 'Address',
                    'postcode'    => 'Postcode',
                    'docno'       => 'Doc No',
                    'value'       => 'Value',
                    'email'       => 'Email',
                    'envsets'     => 'Env Sets',
                    'box'         => 'Box Colour',
                    'env'         => 'Env Colour',
                    'description' => 'Description',
                    'phone'       => 'Phone',
                ] as $col => $lbl)
                <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#334155;padding:0.2rem 0;cursor:pointer;">
                    <input type="checkbox" checked onchange="toggleCol('{{ $col }}', this.checked)" data-col-toggle="{{ $col }}"
                        style="width:14px;height:14px;cursor:pointer;accent-color:#0f172a;">
                    {{ $lbl }}
                </label>
                @endforeach
                <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-top:0.75rem;margin-bottom:0.5rem;">Call Tracking</p>
                @foreach([
                    'calledby'   => 'Called By',
                    'calleddate' => 'Called Date',
                    'notes'      => 'Notes',
                    'ordered'    => 'Ordered',
                ] as $col => $lbl)
                <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#334155;padding:0.2rem 0;cursor:pointer;">
                    <input type="checkbox" checked onchange="toggleCol('{{ $col }}', this.checked)" data-col-toggle="{{ $col }}"
                        style="width:14px;height:14px;cursor:pointer;accent-color:#0f172a;">
                    {{ $lbl }}
                </label>
                @endforeach
            </div>
        </div>

        {{-- Clear month --}}
        <form action="{{ route('reminders.clear-month') }}" method="POST"
            onsubmit="return confirm('Delete all {{ $totalCount }} entries for {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}? This cannot be undone.')">
            @csrf
            <input type="hidden" name="year"  value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <button type="submit"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;border-radius:8px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>
                </svg>
                Clear Month
            </button>
        </form>

        {{-- Mailchimp export --}}
        <a href="{{ route('reminders.export', ['year' => $year, 'month' => $month]) }}"
            style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.4rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;text-decoration:none;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Export for Mailchimp
        </a>
        @endif
    </div>

    {{-- Import accordion --}}
    <div class="bg-white rounded-xl border border-slate-200 mb-6">
        <button onclick="toggleImports()" id="imports-toggle"
            style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:0.875rem 1.25rem;background:none;border:none;cursor:pointer;font-family:inherit;">
            <span style="font-size:0.875rem;font-weight:600;color:#334155;">Import Data</span>
            <svg id="imports-chevron" style="width:14px;height:14px;color:#94a3b8;transition:transform 0.2s;"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <div id="imports-panel" style="display:none;border-top:1px solid #f1f5f9;">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
                <div style="padding:1.25rem;border-right:1px solid #f1f5f9;">
                    <p style="font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.25rem;">Datafile Export</p>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-bottom:0.875rem;">Paste in the customer list for this month from Datafile. Use Clear Month first to fully reset.</p>
                    <form action="{{ route('reminders.import-entries') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year"  value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required
                            class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit" style="align-self:flex-start;padding:0.375rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">Import Entries</button>
                    </form>
                </div>
                <div style="padding:1.25rem;border-right:1px solid #f1f5f9;">
                    <p style="font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.25rem;">Phone Numbers</p>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-bottom:0.875rem;">Datafile export: Stock-Code, Telephone, Mobile. Merges both numbers and applies to all months.</p>
                    <form action="{{ route('reminders.import-phones') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year"  value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required
                            class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit" style="align-self:flex-start;padding:0.375rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">Import Phones</button>
                    </form>
                </div>
                <div style="padding:1.25rem;">
                    <p style="font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.25rem;">Orders (OK) Import</p>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-bottom:0.875rem;">Datafile order export. Marks accounts as Ordered across all months.</p>
                    <form action="{{ route('reminders.import-orders') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year"  value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required
                            class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
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
            style="display:inline-flex;align-items:center;padding:0.2rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:600;border:2px solid #0f172a;background:#0f172a;color:#fff;cursor:pointer;">
            All
        </button>
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

    @php $cellBase = 'padding:0.5rem 0.75rem;border-right:1px solid rgba(0,0,0,0.06);vertical-align:middle;'; @endphp

    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table id="reminders-table" style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                <thead>
                    @php $thBase = 'padding:0.625rem 0.75rem;text-align:left;font-size:0.695rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:2px solid #e2e8f0;border-right:1px solid #e2e8f0;'; @endphp
                    <tr style="background:#f8fafc;">
                        <th style="{{ $thBase }}position:sticky;left:0;background:#f8fafc;z-index:2;border-right:2px solid #e2e8f0;">Account</th>
                        <th style="{{ $thBase }}position:sticky;left:72px;background:#f8fafc;z-index:2;min-width:170px;border-right:2px solid #e2e8f0;">Name</th>
                        <th data-col="address"     style="{{ $thBase }}">Address</th>
                        <th data-col="postcode"    style="{{ $thBase }}">Postcode</th>
                        <th data-col="docno"       style="{{ $thBase }}">Doc No</th>
                        <th data-col="value"       style="{{ $thBase }}text-align:right;">Value</th>
                        <th data-col="email"       style="{{ $thBase }}min-width:220px;">Email</th>
                        <th data-col="envsets"     style="{{ $thBase }}text-align:right;">Env Sets</th>
                        <th data-col="box"         style="{{ $thBase }}">Box</th>
                        <th data-col="env"         style="{{ $thBase }}">Env</th>
                        <th data-col="description" style="{{ $thBase }}">Description</th>
                        <th data-col="phone"       style="{{ $thBase }}">Phone</th>
                        <th style="{{ $thBase }}border-left:2px solid #e2e8f0;">Status</th>
                        <th data-col="calledby"    style="{{ $thBase }}">Called By</th>
                        <th data-col="calleddate"  style="{{ $thBase }}">Called Date</th>
                        <th data-col="notes"       style="{{ $thBase }}min-width:190px;">Notes</th>
                        <th data-col="ordered"     style="{{ $thBase }}text-align:center;">Ordered</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                    @php
                        $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$entry->status] ?? ['bg'=>'#ffffff','text'=>'#334155'];
                        $rowBg   = $colours['bg'];
                    @endphp
                    <tr id="row-{{ $entry->id }}" data-status="{{ $entry->status }}" style="background-color:{{ $rowBg }};border-bottom:1px solid rgba(0,0,0,0.05);">

                        <td style="{{ $cellBase }}font-weight:600;color:#334155;position:sticky;left:0;background-color:{{ $rowBg }};z-index:1;border-right:2px solid rgba(0,0,0,0.08);white-space:nowrap;" class="sticky-col">
                            {{ $entry->account_code }}
                        </td>
                        <td style="{{ $cellBase }}color:#334155;position:sticky;left:72px;background-color:{{ $rowBg }};z-index:1;min-width:170px;border-right:2px solid rgba(0,0,0,0.08);" class="sticky-col">
                            {{ $entry->name }}
                        </td>
                        <td data-col="address"     style="{{ $cellBase }}color:#64748b;white-space:nowrap;">{{ $entry->add1 }}</td>
                        <td data-col="postcode"    style="{{ $cellBase }}color:#64748b;white-space:nowrap;font-family:monospace;font-size:0.75rem;">{{ $entry->postcode }}</td>
                        <td data-col="docno"       style="{{ $cellBase }}color:#64748b;white-space:nowrap;font-family:monospace;font-size:0.75rem;">{{ $entry->doc_no }}</td>
                        <td data-col="value"       style="{{ $cellBase }}text-align:right;color:#334155;white-space:nowrap;font-weight:500;">
                            @if($entry->order_value) £{{ number_format((float)$entry->order_value, 2) }} @endif
                        </td>
                        <td data-col="email"       style="{{ $cellBase }}white-space:nowrap;">
                            @if($entry->email)
                            <a href="mailto:{{ $entry->email }}" style="color:#0369a1;text-decoration:none;">{{ $entry->email }}</a>
                            @endif
                        </td>
                        <td data-col="envsets"     style="{{ $cellBase }}text-align:right;color:#64748b;white-space:nowrap;">{{ $entry->env_sets ? number_format((float)$entry->env_sets, 0) : '' }}</td>
                        <td data-col="box"         style="{{ $cellBase }}color:#64748b;white-space:nowrap;text-transform:capitalize;">{{ strtolower($entry->box_colour ?? '') }}</td>
                        <td data-col="env"         style="{{ $cellBase }}color:#64748b;white-space:nowrap;text-transform:capitalize;">{{ strtolower($entry->env_colour ?? '') }}</td>
                        <td data-col="description" style="{{ $cellBase }}color:#64748b;white-space:nowrap;">{{ $entry->description }}</td>
                        <td data-col="phone"       style="{{ $cellBase }}color:#64748b;white-space:nowrap;font-family:monospace;font-size:0.75rem;">{{ $entry->phone }}</td>

                        {{-- Status --}}
                        <td style="{{ $cellBase }}white-space:nowrap;border-left:2px solid rgba(0,0,0,0.08);">
                            <select data-field="status" onchange="updateEntry({{ $entry->id }}, 'status', this.value)"
                                style="border:1px solid #e2e8f0;border-radius:6px;padding:0.25rem 0.375rem;font-size:0.75rem;color:#334155;background:#fff;cursor:pointer;width:100%;max-width:200px;">
                                @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                                <option value="{{ $key }}" {{ $entry->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Called By --}}
                        <td data-col="calledby" style="{{ $cellBase }}white-space:nowrap;">
                            <select data-field="called_by_user_id" onchange="updateEntry({{ $entry->id }}, 'called_by_user_id', this.value || null)"
                                style="border:1px solid #e2e8f0;border-radius:6px;padding:0.25rem 0.375rem;font-size:0.75rem;color:#334155;background:#fff;cursor:pointer;width:100%;max-width:140px;">
                                <option value="">—</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $entry->called_by_user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Called Date --}}
                        <td data-col="calleddate" style="{{ $cellBase }}white-space:nowrap;">
                            <input type="date" data-field="called_date" value="{{ $entry->called_date?->format('Y-m-d') }}"
                                onchange="updateEntry({{ $entry->id }}, 'called_date', this.value || null)"
                                style="border:1px solid #e2e8f0;border-radius:6px;padding:0.25rem 0.375rem;font-size:0.75rem;color:#334155;background:#fff;width:130px;">
                        </td>

                        {{-- Notes --}}
                        <td data-col="notes" style="{{ $cellBase }}min-width:190px;">
                            <input type="text" data-field="call_notes" value="{{ $entry->call_notes }}"
                                onblur="updateEntry({{ $entry->id }}, 'call_notes', this.value || null)"
                                placeholder="Notes…"
                                style="border:1px solid #e2e8f0;border-radius:6px;padding:0.25rem 0.5rem;font-size:0.75rem;color:#334155;background:#fff;width:100%;min-width:170px;">
                        </td>

                        {{-- Ordered badge (read-only) --}}
                        <td data-col="ordered" style="{{ $cellBase }}text-align:center;white-space:nowrap;">
                            <span data-field="has_ordered" data-ordered="{{ $entry->has_ordered ? '1' : '0' }}">
                                @if($entry->has_ordered)
                                <span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;line-height:1rem;">
                                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    Ordered
                                </span>
                                @else
                                <span style="color:#cbd5e1;font-size:0.8rem;">—</span>
                                @endif
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</main>

<script>
(function () {
    var csrfToken     = document.querySelector('meta[name="csrf-token"]').content;
    var statusColours = @json(\App\Models\ReminderEntry::STATUS_COLOURS);
    var activeFilter  = null;

    // ── Column visibility ─────────────────────────────────────────────────────
    var colPrefs = {};
    try { colPrefs = JSON.parse(localStorage.getItem('reminder_col_prefs') || '{}'); } catch(e){}

    function toggleCol(name, visible) {
        document.querySelectorAll('[data-col="' + name + '"]').forEach(function (el) {
            el.style.display = visible ? '' : 'none';
        });
        colPrefs[name] = visible;
        try { localStorage.setItem('reminder_col_prefs', JSON.stringify(colPrefs)); } catch(e){}
    }
    window.toggleCol = toggleCol;

    // Restore saved column prefs on load
    Object.keys(colPrefs).forEach(function (name) {
        if (!colPrefs[name]) {
            toggleCol(name, false);
            var cb = document.querySelector('[data-col-toggle="' + name + '"]');
            if (cb) cb.checked = false;
        }
    });

    // Close picker when clicking outside
    window.toggleColPicker = function (e) {
        e.stopPropagation();
        var p = document.getElementById('col-picker');
        p.style.display = p.style.display === 'none' ? 'block' : 'none';
    };
    document.addEventListener('click', function () {
        var p = document.getElementById('col-picker');
        if (p) p.style.display = 'none';
    });
    var picker = document.getElementById('col-picker');
    if (picker) picker.addEventListener('click', function (e) { e.stopPropagation(); });

    // ── Inline save ───────────────────────────────────────────────────────────
    window.updateEntry = function (id, field, value) {
        var body = {};
        body[field] = value;
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
                    row.querySelectorAll('.sticky-col').forEach(function (td) { td.style.backgroundColor = colours.bg; });
                    if (activeFilter && value !== activeFilter) row.style.display = 'none';
                    else row.style.display = '';
                }
                refreshStats();
            }
        }).catch(function (e) { console.error(e); });
    };

    // ── Status filter ─────────────────────────────────────────────────────────
    window.filterByStatus = function (status) {
        activeFilter = status;
        document.querySelectorAll('#reminders-table tbody tr').forEach(function (row) {
            row.style.display = (!status || row.getAttribute('data-status') === status) ? '' : 'none';
        });
        document.getElementById('filter-all').style.cssText += ';background:' + (status ? '#f1f5f9' : '#0f172a') + ';color:' + (status ? '#475569' : '#fff') + ';border-color:' + (status ? 'transparent' : '#0f172a') + ';';
        document.querySelectorAll('.filter-pill').forEach(function (pill) {
            var active = pill.getAttribute('data-status') === status;
            pill.style.outline    = active ? '2px solid #0f172a' : '1px solid rgba(0,0,0,0.08)';
            pill.style.outlineOffset = active ? '1px' : '0';
        });
    };

    // ── Stats refresh ─────────────────────────────────────────────────────────
    function refreshStats() {
        var rows    = document.querySelectorAll('#reminders-table tbody tr');
        var total   = rows.length;
        var ordered = 0;
        rows.forEach(function (row) { if (row.getAttribute('data-status') === 'order_placed') ordered++; });
        var statEls = document.querySelectorAll('.stat-count');
        if (statEls[0]) statEls[0].textContent = total + ' total';
        if (statEls[1]) statEls[1].textContent = ordered + ' ordered';
        if (statEls[2]) statEls[2].textContent = (total - ordered) + ' outstanding';
    }

    // ── Imports accordion ─────────────────────────────────────────────────────
    window.toggleImports = function () {
        var panel = document.getElementById('imports-panel');
        var ch    = document.getElementById('imports-chevron');
        if (!panel) return;
        var open = panel.style.display !== 'none';
        panel.style.display = open ? 'none' : 'block';
        if (ch) ch.style.transform = open ? '' : 'rotate(180deg)';
        localStorage.setItem('reminders_imports_open', open ? '0' : '1');
    };
    if (localStorage.getItem('reminders_imports_open') === '1') {
        var panel = document.getElementById('imports-panel');
        var ch    = document.getElementById('imports-chevron');
        if (panel) panel.style.display = 'block';
        if (ch) ch.style.transform = 'rotate(180deg)';
    }
    @if($errors->hasAny(['file', 'phones_file', 'orders_file']))
    (function () {
        var panel = document.getElementById('imports-panel');
        var ch    = document.getElementById('imports-chevron');
        if (panel) panel.style.display = 'block';
        if (ch) ch.style.transform = 'rotate(180deg)';
    })();
    @endif

    // ── Click-to-drag horizontal scroll ──────────────────────────────────────
    (function () {
        var scroller = document.querySelector('#reminders-table')?.closest('div[style*="overflow-x"]');
        if (!scroller) return;
        var isDragging = false, startX = 0, scrollLeft = 0, moved = false;

        scroller.addEventListener('mousedown', function (e) {
            // Only drag on the scroll container itself or table rows, not on inputs/selects
            if (['INPUT','SELECT','BUTTON','A','LABEL'].includes(e.target.tagName)) return;
            isDragging = true; moved = false;
            startX = e.pageX - scroller.offsetLeft;
            scrollLeft = scroller.scrollLeft;
            scroller.style.cursor = 'grabbing';
            e.preventDefault();
        });
        document.addEventListener('mouseup', function () {
            isDragging = false;
            scroller.style.cursor = '';
        });
        document.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            var x    = e.pageX - scroller.offsetLeft;
            var walk = x - startX;
            if (Math.abs(walk) > 4) moved = true;
            scroller.scrollLeft = scrollLeft - walk;
        });
        // Prevent click firing on inputs after a drag
        scroller.addEventListener('click', function (e) {
            if (moved) { e.stopPropagation(); e.preventDefault(); moved = false; }
        }, true);

        scroller.style.cursor = 'grab';
    })();

    // ── Real-time polling ─────────────────────────────────────────────────────
    @if($entries->isNotEmpty())
    var pollUrl   = '{{ route('reminders.poll', ['year' => $year, 'month' => $month]) }}';
    var lastSeen  = {};
    var myPending = {};

    @foreach($entries as $entry)
    lastSeen[{{ $entry->id }}] = '{{ $entry->updated_at }}';
    @endforeach

    function applyPollData(data) {
        Object.keys(data).forEach(function (id) {
            var row   = document.getElementById('row-' + id);
            if (!row) return;
            var entry = data[id];
            if (lastSeen[id] === entry.updated_at) return;
            lastSeen[id] = entry.updated_at;
            if (myPending[id]) return;

            var colours = statusColours[entry.status] || { bg: '#ffffff' };
            row.setAttribute('data-status', entry.status);
            row.style.backgroundColor = colours.bg;
            row.querySelectorAll('.sticky-col').forEach(function (td) { td.style.backgroundColor = colours.bg; });
            if (activeFilter && entry.status !== activeFilter) row.style.display = 'none';

            var s = row.querySelector('select[data-field="status"]');         if (s) s.value = entry.status;
            var c = row.querySelector('select[data-field="called_by_user_id"]'); if (c) c.value = entry.called_by_user_id || '';
            var d = row.querySelector('input[data-field="called_date"]');     if (d) d.value = entry.called_date || '';
            var n = row.querySelector('input[data-field="call_notes"]');      if (n && document.activeElement !== n) n.value = entry.call_notes || '';

            var ordSpan = row.querySelector('[data-field="has_ordered"]');
            if (ordSpan) {
                ordSpan.setAttribute('data-ordered', entry.has_ordered ? '1' : '0');
                ordSpan.innerHTML = entry.has_ordered
                    ? '<span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;line-height:1rem;"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Ordered</span>'
                    : '<span style="color:#cbd5e1;font-size:0.8rem;">—</span>';
            }
        });
        refreshStats();
    }

    function doPoll() {
        fetch(pollUrl, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } })
            .then(function (r) { return r.json(); }).then(applyPollData).catch(function () {});
    }
    setInterval(doPoll, 10000);

    var origUpdate = window.updateEntry;
    window.updateEntry = function (id, field, value) {
        myPending[id] = true;
        origUpdate(id, field, value);
        setTimeout(function () { delete myPending[id]; }, 3000);
    };
    @endif
})();
</script>
</x-layout>
