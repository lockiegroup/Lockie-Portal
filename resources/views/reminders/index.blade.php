<x-layout title="Reminders — Lockie Portal">
<main class="max-w-screen-xl mx-auto px-6 py-10">

    {{-- Page header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Order Reminders</h1>
            <p class="text-slate-500 mt-1 text-sm">Track and chase church envelope orders month by month.</p>
        </div>

        {{-- Month / Year selector --}}
        <form method="GET" action="{{ route('reminders.index') }}" class="flex items-center gap-2">
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
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $errors->first() }}</div>
    @endif

    {{-- Stats + Export bar --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem;margin-bottom:1.25rem;">
        <span style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:600;background:#f1f5f9;color:#475569;">
            {{ $totalCount }} total
        </span>
        <span style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:600;background:#dcfce7;color:#166534;">
            {{ $orderedCount }} ordered
        </span>
        <span style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:600;background:#fef3c7;color:#92400e;">
            {{ $pendingCount }} outstanding
        </span>
        <div style="flex:1;"></div>
        @if($totalCount > 0)
        <a href="{{ route('reminders.export', ['year' => $year, 'month' => $month]) }}"
            style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.4rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;text-decoration:none;transition:background 0.15s;"
            onmouseover="this.style.background='#1e293b'" onmouseout="this.style.background='#0f172a'">
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
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:0;border-top:0;">

                {{-- Datafile entries import --}}
                <div style="padding:1.25rem;border-right:1px solid #f1f5f9;">
                    <p style="font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.25rem;">Datafile Export</p>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-bottom:0.875rem;">Paste in the customer list for this month from Datafile.</p>
                    <form action="{{ route('reminders.import-entries') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year"  value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required
                            class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit"
                            style="align-self:flex-start;padding:0.375rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                            Import Entries
                        </button>
                    </form>
                </div>

                {{-- Phone numbers import --}}
                <div style="padding:1.25rem;border-right:1px solid #f1f5f9;">
                    <p style="font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.25rem;">Phone Numbers</p>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-bottom:0.875rem;">Two-column file: Account Code, Phone. Updates the persistent phone lookup and fills this month.</p>
                    <form action="{{ route('reminders.import-phones') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year"  value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required
                            class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit"
                            style="align-self:flex-start;padding:0.375rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                            Import Phones
                        </button>
                    </form>
                </div>

                {{-- Orders import --}}
                <div style="padding:1.25rem;">
                    <p style="font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.25rem;">Orders (OK) Import</p>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-bottom:0.875rem;">File with account codes of customers who have ordered. Ticks the Ordered column for matches.</p>
                    <form action="{{ route('reminders.import-orders') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="hidden" name="year"  value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required
                            class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                        <button type="submit"
                            style="align-self:flex-start;padding:0.375rem 0.75rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                            Import Orders
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    {{-- Status legend --}}
    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-bottom:1rem;">
        @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
        @php $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#fff','text'=>'#334155']; @endphp
        <span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:500;background:{{ $colours['bg'] }};color:{{ $colours['text'] }};border:1px solid rgba(0,0,0,0.06);">
            {{ $label }}
        </span>
        @endforeach
    </div>

    {{-- Main table --}}
    @if($entries->isEmpty())
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:3rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
        No entries for {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}. Use the import above to load this month's customers.
    </div>
    @else
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;position:sticky;left:0;background:#f8fafc;z-index:2;">Account</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;position:sticky;left:68px;background:#f8fafc;z-index:2;min-width:160px;">Name</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Address</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Postcode</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Doc No</th>
                        <th style="padding:0.625rem 0.75rem;text-align:right;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Value</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Email</th>
                        <th style="padding:0.625rem 0.75rem;text-align:right;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Env Sets</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Box</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Env</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Description</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Phone</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Status</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Called By</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Called Date</th>
                        <th style="padding:0.625rem 0.75rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;min-width:180px;">Notes</th>
                        <th style="padding:0.625rem 0.75rem;text-align:center;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0;">Ordered</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                    @php
                        $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$entry->status] ?? ['bg'=>'#ffffff','text'=>'#334155'];
                        $rowBg   = $colours['bg'];
                    @endphp
                    <tr id="row-{{ $entry->id }}" style="background-color:{{ $rowBg }};border-bottom:1px solid rgba(0,0,0,0.05);">
                        <td style="padding:0.5rem 0.75rem;white-space:nowrap;font-weight:600;color:#334155;position:sticky;left:0;background-color:{{ $rowBg }};z-index:1;" class="sticky-col">
                            {{ $entry->account_code }}
                        </td>
                        <td style="padding:0.5rem 0.75rem;color:#334155;position:sticky;left:68px;background-color:{{ $rowBg }};z-index:1;min-width:160px;" class="sticky-col">
                            {{ $entry->name }}
                        </td>
                        <td style="padding:0.5rem 0.75rem;color:#64748b;white-space:nowrap;">{{ $entry->add1 }}</td>
                        <td style="padding:0.5rem 0.75rem;color:#64748b;white-space:nowrap;font-family:monospace;font-size:0.75rem;">{{ $entry->postcode }}</td>
                        <td style="padding:0.5rem 0.75rem;color:#64748b;white-space:nowrap;font-family:monospace;font-size:0.75rem;">{{ $entry->doc_no }}</td>
                        <td style="padding:0.5rem 0.75rem;text-align:right;color:#334155;white-space:nowrap;font-weight:500;">
                            @if($entry->order_value) £{{ number_format((float)$entry->order_value, 2) }} @endif
                        </td>
                        <td style="padding:0.5rem 0.75rem;color:#64748b;white-space:nowrap;max-width:180px;overflow:hidden;text-overflow:ellipsis;">
                            @if($entry->email)
                            <a href="mailto:{{ $entry->email }}" style="color:#0369a1;text-decoration:none;" title="{{ $entry->email }}">{{ $entry->email }}</a>
                            @endif
                        </td>
                        <td style="padding:0.5rem 0.75rem;text-align:right;color:#64748b;white-space:nowrap;">{{ $entry->env_sets ? number_format((float)$entry->env_sets, 0) : '' }}</td>
                        <td style="padding:0.5rem 0.75rem;color:#64748b;white-space:nowrap;text-transform:capitalize;">{{ strtolower($entry->box_colour ?? '') }}</td>
                        <td style="padding:0.5rem 0.75rem;color:#64748b;white-space:nowrap;text-transform:capitalize;">{{ strtolower($entry->env_colour ?? '') }}</td>
                        <td style="padding:0.5rem 0.75rem;color:#64748b;white-space:nowrap;">{{ $entry->description }}</td>
                        <td style="padding:0.5rem 0.75rem;color:#64748b;white-space:nowrap;font-family:monospace;font-size:0.75rem;">{{ $entry->phone }}</td>

                        {{-- Status --}}
                        <td style="padding:0.375rem 0.5rem;white-space:nowrap;">
                            <select onchange="updateEntry({{ $entry->id }}, 'status', this.value)"
                                style="border:1px solid #e2e8f0;border-radius:6px;padding:0.25rem 0.375rem;font-size:0.75rem;color:#334155;background:#fff;cursor:pointer;width:100%;max-width:200px;">
                                @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                                <option value="{{ $key }}" {{ $entry->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Called By --}}
                        <td style="padding:0.375rem 0.5rem;white-space:nowrap;">
                            <select onchange="updateEntry({{ $entry->id }}, 'called_by_user_id', this.value || null)"
                                style="border:1px solid #e2e8f0;border-radius:6px;padding:0.25rem 0.375rem;font-size:0.75rem;color:#334155;background:#fff;cursor:pointer;width:100%;max-width:140px;">
                                <option value="">—</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $entry->called_by_user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Called Date --}}
                        <td style="padding:0.375rem 0.5rem;white-space:nowrap;">
                            <input type="date" value="{{ $entry->called_date?->format('Y-m-d') }}"
                                onchange="updateEntry({{ $entry->id }}, 'called_date', this.value || null)"
                                style="border:1px solid #e2e8f0;border-radius:6px;padding:0.25rem 0.375rem;font-size:0.75rem;color:#334155;background:#fff;width:130px;">
                        </td>

                        {{-- Call Notes --}}
                        <td style="padding:0.375rem 0.5rem;min-width:180px;">
                            <input type="text" value="{{ $entry->call_notes }}"
                                onblur="updateEntry({{ $entry->id }}, 'call_notes', this.value || null)"
                                placeholder="Notes…"
                                style="border:1px solid #e2e8f0;border-radius:6px;padding:0.25rem 0.5rem;font-size:0.75rem;color:#334155;background:#fff;width:100%;min-width:160px;">
                        </td>

                        {{-- Ordered --}}
                        <td style="padding:0.375rem 0.75rem;text-align:center;white-space:nowrap;">
                            <input type="checkbox" {{ $entry->has_ordered ? 'checked' : '' }}
                                onchange="updateEntry({{ $entry->id }}, 'has_ordered', this.checked)"
                                style="width:16px;height:16px;cursor:pointer;accent-color:#16a34a;">
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
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    var statusColours = @json(\App\Models\ReminderEntry::STATUS_COLOURS);

    window.updateEntry = function (id, field, value) {
        var body = {};
        body[field] = value;

        fetch('/reminders/entries/' + id, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        }).then(function (r) {
            if (!r.ok) { console.error('Update failed'); return; }
            if (field === 'status') {
                var colours = statusColours[value] || { bg: '#ffffff', text: '#334155' };
                var row = document.getElementById('row-' + id);
                if (row) {
                    row.style.backgroundColor = colours.bg;
                    row.querySelectorAll('.sticky-col').forEach(function (td) {
                        td.style.backgroundColor = colours.bg;
                    });
                }
            }
            if (field === 'has_ordered') {
                // Visual tick/untick handled by checkbox itself
            }
        }).catch(function (e) { console.error(e); });
    };

    // Imports accordion
    window.toggleImports = function () {
        var panel = document.getElementById('imports-panel');
        var ch    = document.getElementById('imports-chevron');
        if (!panel) return;
        var open = panel.style.display !== 'none';
        panel.style.display = open ? 'none' : 'block';
        if (ch) ch.style.transform = open ? '' : 'rotate(180deg)';
        localStorage.setItem('reminders_imports_open', open ? '0' : '1');
    };

    // Restore accordion state
    if (localStorage.getItem('reminders_imports_open') === '1') {
        var panel = document.getElementById('imports-panel');
        var ch    = document.getElementById('imports-chevron');
        if (panel) panel.style.display = 'block';
        if (ch) ch.style.transform = 'rotate(180deg)';
    }

    // Auto-open imports accordion if there was an import error
    @if($errors->hasAny(['file', 'phones_file', 'orders_file']))
    (function () {
        var panel = document.getElementById('imports-panel');
        var ch    = document.getElementById('imports-chevron');
        if (panel) panel.style.display = 'block';
        if (ch) ch.style.transform = 'rotate(180deg)';
    })();
    @endif
})();
</script>
</x-layout>
