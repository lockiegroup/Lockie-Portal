<x-layout title="Cash Flow — Lockie Portal">

<meta name="csrf-token" content="{{ csrf_token() }}">

<main style="max-width:1400px;margin:0 auto;padding:2rem 1.5rem;">

    {{-- SECTION 1: Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:1.5rem;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">Cash Flow</h1>
            <p style="font-size:0.875rem;color:#64748b;margin:0.25rem 0 0;">Weekly planning derived from entries below.</p>
        </div>
        <button onclick="openCatModal()" style="background:#1e293b;color:#fff;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;cursor:pointer;font-weight:600;">
            Manage Categories
        </button>
    </div>

    {{-- SECTION 2: Controls bar --}}
    <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;flex-wrap:wrap;gap:1rem;">

        {{-- Opening Balance --}}
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <span style="font-size:0.875rem;color:#64748b;font-weight:500;">Opening Balance:</span>
            <span
                onclick="editOpeningBalance()"
                style="font-size:1rem;font-weight:700;cursor:pointer;padding:0.2rem 0.5rem;border-radius:0.4rem;border:1px dashed #cbd5e1;color:{{ $openingBalance >= 0 ? '#16a34a' : '#dc2626' }};"
                title="Click to edit">
                £{{ number_format($openingBalance, 2) }}
            </span>
        </div>

        <div style="flex:1;"></div>

        {{-- Horizon selector --}}
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <span style="font-size:0.875rem;color:#64748b;font-weight:500;">Horizon:</span>
            @foreach([4,8,13,26] as $h)
                <a href="{{ route('cash-flow.index', ['horizon'=>$h,'search'=>$search,'status'=>$statusFilter]) }}"
                   style="padding:0.25rem 0.75rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;text-decoration:none;
                          background:{{ $horizon==$h ? '#1e293b' : '#f1f5f9' }};
                          color:{{ $horizon==$h ? '#fff' : '#475569' }};">
                    {{ $h }}w
                </a>
            @endforeach
        </div>
    </div>

    {{-- SECTION 3: Weekly spreadsheet --}}
    <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);margin-bottom:1.5rem;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="border-collapse:collapse;min-width:100%;font-size:0.8125rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="position:sticky;left:0;z-index:2;background:#f8fafc;text-align:left;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;min-width:170px;">
                            Category
                        </th>
                        @foreach($weeks as $i => $week)
                            @php $wk = $weekKeys[$i]; @endphp
                            <th style="text-align:right;padding:0.5rem 0.75rem;font-weight:600;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;min-width:110px;">
                                <div style="font-size:0.7rem;color:#94a3b8;font-weight:500;">{{ $week->format('D') }}</div>
                                {{ $week->format('d M') }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>

                    {{-- Opening Balance row --}}
                    @php $ob = $openingBalance; @endphp
                    <tr style="background:#f0fdf4;">
                        <td style="position:sticky;left:0;z-index:1;background:#f0fdf4;padding:0.5rem 1rem;font-weight:600;color:#15803d;border-bottom:1px solid #e2e8f0;white-space:nowrap;">
                            Opening Balance
                        </td>
                        @foreach($weeks as $i => $week)
                            @php
                                $wk = $weekKeys[$i];
                                $running = $i === 0 ? $ob : ($weeklyCalc[$weekKeys[$i-1]]['closing'] ?? $ob);
                                $displayOb = $i === 0 ? $ob : ($weeklyCalc[$weekKeys[$i-1]]['closing'] ?? $ob);
                                $obColor = $displayOb >= 0 ? '#16a34a' : '#dc2626';
                            @endphp
                            <td style="text-align:right;padding:0.5rem 0.75rem;font-weight:600;color:{{ $obColor }};border-bottom:1px solid #e2e8f0;white-space:nowrap;">
                                £{{ number_format($displayOb, 2) }}
                            </td>
                        @endforeach
                    </tr>

                    {{-- INCOME section header --}}
                    <tr style="background:#dcfce7;">
                        <td colspan="{{ count($weeks) + 1 }}" style="padding:0.4rem 1rem;font-weight:700;color:#15803d;font-size:0.75rem;letter-spacing:0.05em;text-transform:uppercase;">
                            INCOME
                        </td>
                    </tr>

                    {{-- Income category rows --}}
                    @foreach($incomeCategories as $cat)
                        <tr style="background:#fff;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                            <td style="position:sticky;left:0;z-index:1;background:inherit;padding:0.5rem 1rem;color:#334155;border-bottom:1px solid #f1f5f9;white-space:nowrap;">
                                {{ $cat->name }}
                            </td>
                            @foreach($weeks as $i => $week)
                                @php
                                    $wk = $weekKeys[$i];
                                    $cell = $matrix[$cat->id][$wk] ?? null;
                                    $amt = $cell ? $cell['amount'] : null;
                                    $hasForecast = $cell ? $cell['has_forecast'] : false;
                                    $hasActual = $cell ? $cell['has_actual'] : false;
                                @endphp
                                <td style="text-align:right;padding:0.5rem 0.75rem;border-bottom:1px solid #f1f5f9;white-space:nowrap;color:#16a34a;">
                                    @if($amt !== null && $amt != 0)
                                        £{{ number_format($amt, 2) }}
                                        @if($hasForecast && !$hasActual)
                                            <span style="font-size:0.65rem;background:#fef9c3;color:#854d0e;border-radius:0.25rem;padding:0.1rem 0.3rem;margin-left:0.2rem;font-weight:700;">F</span>
                                        @endif
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach

                    {{-- Total Income row --}}
                    <tr style="background:#f0fdf4;">
                        <td style="position:sticky;left:0;z-index:1;background:#f0fdf4;padding:0.5rem 1rem;font-weight:700;color:#15803d;border-bottom:2px solid #e2e8f0;white-space:nowrap;">
                            Total Income
                        </td>
                        @foreach($weeks as $i => $week)
                            @php $wk = $weekKeys[$i]; $totalInc = $weeklyCalc[$wk]['income'] ?? 0; @endphp
                            <td style="text-align:right;padding:0.5rem 0.75rem;font-weight:700;color:#15803d;border-bottom:2px solid #e2e8f0;white-space:nowrap;">
                                £{{ number_format($totalInc, 2) }}
                            </td>
                        @endforeach
                    </tr>

                    {{-- EXPENSES section header --}}
                    <tr style="background:#fee2e2;">
                        <td colspan="{{ count($weeks) + 1 }}" style="padding:0.4rem 1rem;font-weight:700;color:#b91c1c;font-size:0.75rem;letter-spacing:0.05em;text-transform:uppercase;">
                            EXPENSES
                        </td>
                    </tr>

                    {{-- Expense category rows --}}
                    @foreach($expenseCategories as $cat)
                        <tr style="background:#fff;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                            <td style="position:sticky;left:0;z-index:1;background:inherit;padding:0.5rem 1rem;color:#334155;border-bottom:1px solid #f1f5f9;white-space:nowrap;">
                                {{ $cat->name }}
                            </td>
                            @foreach($weeks as $i => $week)
                                @php
                                    $wk = $weekKeys[$i];
                                    $cell = $matrix[$cat->id][$wk] ?? null;
                                    $amt = $cell ? $cell['amount'] : null;
                                    $hasForecast = $cell ? $cell['has_forecast'] : false;
                                    $hasActual = $cell ? $cell['has_actual'] : false;
                                @endphp
                                <td style="text-align:right;padding:0.5rem 0.75rem;border-bottom:1px solid #f1f5f9;white-space:nowrap;color:#dc2626;">
                                    @if($amt !== null && $amt != 0)
                                        £{{ number_format($amt, 2) }}
                                        @if($hasForecast && !$hasActual)
                                            <span style="font-size:0.65rem;background:#fef9c3;color:#854d0e;border-radius:0.25rem;padding:0.1rem 0.3rem;margin-left:0.2rem;font-weight:700;">F</span>
                                        @endif
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach

                    {{-- Total Expenses row --}}
                    <tr style="background:#fef2f2;">
                        <td style="position:sticky;left:0;z-index:1;background:#fef2f2;padding:0.5rem 1rem;font-weight:700;color:#b91c1c;border-bottom:2px solid #e2e8f0;white-space:nowrap;">
                            Total Expenses
                        </td>
                        @foreach($weeks as $i => $week)
                            @php $wk = $weekKeys[$i]; $totalExp = $weeklyCalc[$wk]['expenses'] ?? 0; @endphp
                            <td style="text-align:right;padding:0.5rem 0.75rem;font-weight:700;color:#b91c1c;border-bottom:2px solid #e2e8f0;white-space:nowrap;">
                                £{{ number_format($totalExp, 2) }}
                            </td>
                        @endforeach
                    </tr>

                    {{-- Closing Balance row --}}
                    <tr style="background:#f8fafc;">
                        <td style="position:sticky;left:0;z-index:1;background:#f8fafc;padding:0.625rem 1rem;font-weight:800;color:#1e293b;border-bottom:none;white-space:nowrap;">
                            Closing Balance
                        </td>
                        @foreach($weeks as $i => $week)
                            @php
                                $wk = $weekKeys[$i];
                                $closing = $weeklyCalc[$wk]['closing'] ?? 0;
                                $closingColor = $closing >= 0 ? '#16a34a' : '#dc2626';
                            @endphp
                            <td style="text-align:right;padding:0.625rem 0.75rem;font-weight:800;color:{{ $closingColor }};border-bottom:none;white-space:nowrap;">
                                £{{ number_format($closing, 2) }}
                            </td>
                        @endforeach
                    </tr>

                </tbody>
            </table>
        </div>
    </div>

    {{-- SECTION 4: Entries section --}}
    <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1.25rem 1.5rem;">

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;">
            <h2 style="font-size:1.125rem;font-weight:700;color:#1e293b;margin:0;">Entries</h2>

            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">

                {{-- Search + Status filter form --}}
                <form method="GET" action="{{ route('cash-flow.index') }}" style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <input type="hidden" name="horizon" value="{{ $horizon }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search entries…"
                           style="border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.375rem 0.75rem;font-size:0.875rem;color:#334155;outline:none;width:200px;">

                    {{-- Status tabs --}}
                    <div style="display:flex;border:1px solid #e2e8f0;border-radius:0.5rem;overflow:hidden;">
                        @foreach(['all'=>'All','forecast'=>'Forecast','actual'=>'Actual'] as $val => $label)
                            <button type="submit" name="status" value="{{ $val }}"
                                    style="padding:0.35rem 0.75rem;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;
                                           background:{{ $statusFilter===$val ? '#1e293b' : '#f8fafc' }};
                                           color:{{ $statusFilter===$val ? '#fff' : '#64748b' }};">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </form>

                @if($categories->isEmpty())
                    <span style="font-size:0.875rem;color:#94a3b8;">Set up categories first using <strong>Manage Categories</strong></span>
                @else
                    <button onclick="openModal()" style="background:#16a34a;color:#fff;border:none;border-radius:0.5rem;padding:0.45rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                        + Add Entry
                    </button>
                @endif
            </div>
        </div>

        @if($categories->isEmpty())
            <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:0.9rem;">
                No categories set up yet. Click <strong>Manage Categories</strong> to add income and expense categories.
            </div>
        @elseif($filteredEntries->isEmpty())
            <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:0.9rem;">
                No entries found.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="border-collapse:collapse;width:100%;font-size:0.875rem;">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                            <th style="text-align:left;padding:0.5rem 0.75rem;font-weight:600;color:#475569;white-space:nowrap;">Date</th>
                            <th style="text-align:left;padding:0.5rem 0.75rem;font-weight:600;color:#475569;">Description</th>
                            <th style="text-align:left;padding:0.5rem 0.75rem;font-weight:600;color:#475569;white-space:nowrap;">Category</th>
                            <th style="text-align:left;padding:0.5rem 0.75rem;font-weight:600;color:#475569;white-space:nowrap;">Type</th>
                            <th style="text-align:left;padding:0.5rem 0.75rem;font-weight:600;color:#475569;white-space:nowrap;">Status</th>
                            <th style="text-align:right;padding:0.5rem 0.75rem;font-weight:600;color:#475569;white-space:nowrap;">Amount</th>
                            <th style="text-align:center;padding:0.5rem 0.75rem;font-weight:600;color:#475569;white-space:nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($filteredEntries as $entry)
                            @php
                                $entryData = json_encode([
                                    'id'          => $entry->id,
                                    'category_id' => $entry->category_id,
                                    'entry_date'  => $entry->entry_date->format('Y-m-d'),
                                    'description' => $entry->description,
                                    'type'        => $entry->type,
                                    'amount'      => $entry->amount,
                                    'status'      => $entry->status,
                                    'notes'       => $entry->notes,
                                ]);
                            @endphp
                            <tr data-entry="{{ htmlspecialchars($entryData, ENT_QUOTES) }}"
                                style="border-bottom:1px solid #f1f5f9;"
                                onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                                <td style="padding:0.5rem 0.75rem;white-space:nowrap;color:#475569;">{{ $entry->entry_date->format('d M Y') }}</td>
                                <td style="padding:0.5rem 0.75rem;color:#334155;">{{ $entry->description }}</td>
                                <td style="padding:0.5rem 0.75rem;white-space:nowrap;color:#475569;">{{ $entry->category->name ?? '—' }}</td>
                                <td style="padding:0.5rem 0.75rem;white-space:nowrap;">
                                    <span style="font-size:0.75rem;font-weight:600;padding:0.15rem 0.5rem;border-radius:9999px;
                                                 background:{{ $entry->type==='income' ? '#dcfce7' : '#fee2e2' }};
                                                 color:{{ $entry->type==='income' ? '#15803d' : '#b91c1c' }};">
                                        {{ ucfirst($entry->type) }}
                                    </span>
                                </td>
                                <td style="padding:0.5rem 0.75rem;white-space:nowrap;">
                                    <span style="font-size:0.75rem;font-weight:600;padding:0.15rem 0.5rem;border-radius:9999px;
                                                 background:{{ $entry->status==='actual' ? '#dbeafe' : '#fef9c3' }};
                                                 color:{{ $entry->status==='actual' ? '#1d4ed8' : '#854d0e' }};">
                                        {{ ucfirst($entry->status) }}
                                    </span>
                                </td>
                                <td style="padding:0.5rem 0.75rem;text-align:right;white-space:nowrap;font-weight:600;
                                           color:{{ $entry->type==='income' ? '#16a34a' : '#dc2626' }};">
                                    £{{ number_format($entry->amount, 2) }}
                                </td>
                                <td style="padding:0.5rem 0.75rem;text-align:center;white-space:nowrap;">
                                    <button onclick="editEntry(this.closest('tr'))"
                                            style="background:#f1f5f9;border:none;border-radius:0.4rem;padding:0.3rem 0.6rem;font-size:0.75rem;cursor:pointer;color:#475569;font-weight:600;margin-right:0.25rem;">
                                        Edit
                                    </button>
                                    <button onclick="deleteEntry({{ $entry->id }})"
                                            style="background:#fee2e2;border:none;border-radius:0.4rem;padding:0.3rem 0.6rem;font-size:0.75rem;cursor:pointer;color:#b91c1c;font-weight:600;">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</main>

{{-- SECTION 5: Add/Edit Entry Modal --}}
<div id="entryModal" style="display:none;position:fixed;inset:0;z-index:50;background:rgba(15,23,42,0.45);overflow-y:auto;">
    <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:1rem;">
        <div style="background:#fff;border-radius:0.875rem;box-shadow:0 20px 60px rgba(0,0,0,0.2);width:100%;max-width:480px;padding:1.75rem 2rem;">

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
                <h2 id="modalTitle" style="font-size:1.125rem;font-weight:700;color:#1e293b;margin:0;">Add Entry</h2>
                <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.25rem;line-height:1;">&#x2715;</button>
            </div>

            <input type="hidden" id="entryId" value="">

            {{-- Status toggle --}}
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.8125rem;font-weight:600;color:#475569;display:block;margin-bottom:0.4rem;">Status</label>
                <div style="display:flex;border:1px solid #e2e8f0;border-radius:0.5rem;overflow:hidden;width:fit-content;">
                    <button type="button" id="btn-forecast" onclick="setStatus('forecast')"
                            style="padding:0.4rem 1.25rem;font-size:0.875rem;font-weight:600;border:none;cursor:pointer;background:#1e293b;color:#fff;">
                        Forecast
                    </button>
                    <button type="button" id="btn-actual" onclick="setStatus('actual')"
                            style="padding:0.4rem 1.25rem;font-size:0.875rem;font-weight:600;border:none;cursor:pointer;background:#f8fafc;color:#64748b;">
                        Actual
                    </button>
                </div>
                <input type="hidden" id="entryStatus" value="forecast">
            </div>

            {{-- Date --}}
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.8125rem;font-weight:600;color:#475569;display:block;margin-bottom:0.4rem;">Date</label>
                <input type="date" id="entryDate"
                       style="width:100%;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:#334155;outline:none;box-sizing:border-box;">
            </div>

            {{-- Category --}}
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.8125rem;font-weight:600;color:#475569;display:block;margin-bottom:0.4rem;">Category</label>
                <select id="entryCategoryId" onchange="onCategoryChange()"
                        style="width:100%;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:#334155;outline:none;box-sizing:border-box;background:#fff;">
                    <option value="">— Select category —</option>
                    @if($incomeCategories->isNotEmpty())
                        <optgroup label="Income">
                            @foreach($incomeCategories as $cat)
                                <option value="{{ $cat->id }}" data-type="income">{{ $cat->name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if($expenseCategories->isNotEmpty())
                        <optgroup label="Expenses">
                            @foreach($expenseCategories as $cat)
                                <option value="{{ $cat->id }}" data-type="expense">{{ $cat->name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
            </div>

            {{-- Type (auto-set, read-only display) --}}
            <input type="hidden" id="entryType" value="">
            <div id="entryTypeDisplay" style="margin-bottom:1rem;display:none;">
                <label style="font-size:0.8125rem;font-weight:600;color:#475569;display:block;margin-bottom:0.4rem;">Type</label>
                <span id="entryTypeLabel" style="font-size:0.875rem;font-weight:600;padding:0.25rem 0.75rem;border-radius:9999px;"></span>
            </div>

            {{-- Description --}}
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.8125rem;font-weight:600;color:#475569;display:block;margin-bottom:0.4rem;">Description</label>
                <input type="text" id="entryDescription" placeholder="e.g. Client invoice, Rent payment…"
                       style="width:100%;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:#334155;outline:none;box-sizing:border-box;">
            </div>

            {{-- Amount --}}
            <div style="margin-bottom:1rem;">
                <label style="font-size:0.8125rem;font-weight:600;color:#475569;display:block;margin-bottom:0.4rem;">Amount (£)</label>
                <input type="number" id="entryAmount" min="0" step="0.01" placeholder="0.00"
                       style="width:100%;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:#334155;outline:none;box-sizing:border-box;">
            </div>

            {{-- Notes --}}
            <div style="margin-bottom:1.5rem;">
                <label style="font-size:0.8125rem;font-weight:600;color:#475569;display:block;margin-bottom:0.4rem;">Notes <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                <textarea id="entryNotes" rows="2" placeholder="Optional notes…"
                          style="width:100%;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:#334155;outline:none;box-sizing:border-box;resize:vertical;"></textarea>
            </div>

            {{-- Buttons --}}
            <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                <button type="button" onclick="closeModal()"
                        style="background:#f1f5f9;border:none;border-radius:0.5rem;padding:0.5rem 1.25rem;font-size:0.875rem;font-weight:600;cursor:pointer;color:#475569;">
                    Cancel
                </button>
                <button type="button" onclick="saveEntry()"
                        style="background:#16a34a;border:none;border-radius:0.5rem;padding:0.5rem 1.25rem;font-size:0.875rem;font-weight:600;cursor:pointer;color:#fff;">
                    Save Entry
                </button>
            </div>
        </div>
    </div>
</div>

{{-- SECTION 6: Opening Balance Modal --}}
<div id="obModal" style="display:none;position:fixed;inset:0;z-index:50;background:rgba(15,23,42,0.45);overflow-y:auto;">
    <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:1rem;">
        <div style="background:#fff;border-radius:0.875rem;box-shadow:0 20px 60px rgba(0,0,0,0.2);width:100%;max-width:360px;padding:1.75rem 2rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
                <h2 style="font-size:1.125rem;font-weight:700;color:#1e293b;margin:0;">Opening Balance</h2>
                <button onclick="closeObModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.25rem;line-height:1;">&#x2715;</button>
            </div>
            <div style="margin-bottom:1.25rem;">
                <label style="font-size:0.8125rem;font-weight:600;color:#475569;display:block;margin-bottom:0.4rem;">Balance (£)</label>
                <input type="number" id="obAmount" step="0.01" placeholder="0.00" value="{{ $openingBalance }}"
                       style="width:100%;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;color:#334155;outline:none;box-sizing:border-box;">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
                <button onclick="closeObModal()" style="background:#f1f5f9;border:none;border-radius:0.5rem;padding:0.5rem 1.25rem;font-size:0.875rem;font-weight:600;cursor:pointer;color:#475569;">Cancel</button>
                <button onclick="saveOpeningBalance()" style="background:#1e293b;border:none;border-radius:0.5rem;padding:0.5rem 1.25rem;font-size:0.875rem;font-weight:600;cursor:pointer;color:#fff;">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- SECTION 7: Manage Categories Modal --}}
<div id="catModal" style="display:none;position:fixed;inset:0;z-index:50;background:rgba(15,23,42,0.45);overflow-y:auto;">
    <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:1rem;">
        <div style="background:#fff;border-radius:0.875rem;box-shadow:0 20px 60px rgba(0,0,0,0.2);width:100%;max-width:500px;padding:1.75rem 2rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
                <h2 style="font-size:1.125rem;font-weight:700;color:#1e293b;margin:0;">Manage Categories</h2>
                <button onclick="closeCatModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.25rem;line-height:1;">&#x2715;</button>
            </div>

            {{-- Income categories --}}
            <div style="margin-bottom:1.5rem;">
                <h3 style="font-size:0.9rem;font-weight:700;color:#15803d;margin:0 0 0.625rem;text-transform:uppercase;letter-spacing:0.05em;">Income</h3>
                <ul id="incomeCatList" style="list-style:none;margin:0 0 0.75rem;padding:0;">
                    @foreach($incomeCategories as $cat)
                        <li id="cat-{{ $cat->id }}" style="display:flex;align-items:center;justify-content:space-between;padding:0.4rem 0;border-bottom:1px solid #f1f5f9;">
                            <span style="color:#334155;font-size:0.875rem;">{{ $cat->name }}</span>
                            <button onclick="deleteCategory({{ $cat->id }})" style="background:#fee2e2;border:none;border-radius:0.375rem;padding:0.2rem 0.6rem;font-size:0.75rem;cursor:pointer;color:#b91c1c;font-weight:600;">Remove</button>
                        </li>
                    @endforeach
                </ul>
                <div style="display:flex;gap:0.5rem;">
                    <input type="text" id="newIncomeCat" placeholder="New income category…"
                           style="flex:1;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.4rem 0.75rem;font-size:0.875rem;outline:none;">
                    <button onclick="addCategory('income')" style="background:#15803d;color:#fff;border:none;border-radius:0.5rem;padding:0.4rem 0.9rem;font-size:0.875rem;font-weight:600;cursor:pointer;">Add</button>
                </div>
            </div>

            {{-- Expense categories --}}
            <div>
                <h3 style="font-size:0.9rem;font-weight:700;color:#b91c1c;margin:0 0 0.625rem;text-transform:uppercase;letter-spacing:0.05em;">Expenses</h3>
                <ul id="expenseCatList" style="list-style:none;margin:0 0 0.75rem;padding:0;">
                    @foreach($expenseCategories as $cat)
                        <li id="cat-{{ $cat->id }}" style="display:flex;align-items:center;justify-content:space-between;padding:0.4rem 0;border-bottom:1px solid #f1f5f9;">
                            <span style="color:#334155;font-size:0.875rem;">{{ $cat->name }}</span>
                            <button onclick="deleteCategory({{ $cat->id }})" style="background:#fee2e2;border:none;border-radius:0.375rem;padding:0.2rem 0.6rem;font-size:0.75rem;cursor:pointer;color:#b91c1c;font-weight:600;">Remove</button>
                        </li>
                    @endforeach
                </ul>
                <div style="display:flex;gap:0.5rem;">
                    <input type="text" id="newExpenseCat" placeholder="New expense category…"
                           style="flex:1;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.4rem 0.75rem;font-size:0.875rem;outline:none;">
                    <button onclick="addCategory('expense')" style="background:#b91c1c;color:#fff;border:none;border-radius:0.5rem;padding:0.4rem 0.9rem;font-size:0.875rem;font-weight:600;cursor:pointer;">Add</button>
                </div>
            </div>

            <div style="margin-top:1.5rem;text-align:right;">
                <button onclick="closeCatModal()" style="background:#f1f5f9;border:none;border-radius:0.5rem;padding:0.5rem 1.25rem;font-size:0.875rem;font-weight:600;cursor:pointer;color:#475569;">Done</button>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ── Status toggle ────────────────────────────────────────────────────────────
function setStatus(val) {
    document.getElementById('entryStatus').value = val;
    const btnF = document.getElementById('btn-forecast');
    const btnA = document.getElementById('btn-actual');
    if (val === 'forecast') {
        btnF.style.background = '#1e293b'; btnF.style.color = '#fff';
        btnA.style.background = '#f8fafc'; btnA.style.color = '#64748b';
    } else {
        btnA.style.background = '#1e293b'; btnA.style.color = '#fff';
        btnF.style.background = '#f8fafc'; btnF.style.color = '#64748b';
    }
}

// ── Category change → auto-set type ─────────────────────────────────────────
function onCategoryChange() {
    const sel = document.getElementById('entryCategoryId');
    const opt = sel.options[sel.selectedIndex];
    const type = opt ? opt.getAttribute('data-type') : '';
    document.getElementById('entryType').value = type || '';
    const display = document.getElementById('entryTypeDisplay');
    const label = document.getElementById('entryTypeLabel');
    if (type) {
        display.style.display = 'block';
        label.textContent = type.charAt(0).toUpperCase() + type.slice(1);
        label.style.background = type === 'income' ? '#dcfce7' : '#fee2e2';
        label.style.color = type === 'income' ? '#15803d' : '#b91c1c';
    } else {
        display.style.display = 'none';
    }
}

// ── Entry Modal ──────────────────────────────────────────────────────────────
function openModal(entry = null) {
    document.getElementById('modalTitle').textContent = entry ? 'Edit Entry' : 'Add Entry';
    document.getElementById('entryId').value = entry ? entry.id : '';
    document.getElementById('entryDate').value = entry ? entry.entry_date : '';
    document.getElementById('entryDescription').value = entry ? entry.description : '';
    document.getElementById('entryAmount').value = entry ? entry.amount : '';
    document.getElementById('entryNotes').value = entry ? (entry.notes || '') : '';
    document.getElementById('entryType').value = entry ? entry.type : '';

    // Category select
    const catSel = document.getElementById('entryCategoryId');
    catSel.value = entry ? entry.category_id : '';
    onCategoryChange();

    setStatus(entry ? entry.status : 'forecast');
    document.getElementById('entryModal').style.display = 'block';
}

function editEntry(row) {
    const data = JSON.parse(row.getAttribute('data-entry'));
    openModal(data);
}

function closeModal() {
    document.getElementById('entryModal').style.display = 'none';
}

async function saveEntry() {
    const id = document.getElementById('entryId').value;
    const categoryId = document.getElementById('entryCategoryId').value;
    const type = document.getElementById('entryType').value;

    if (!categoryId) { alert('Please select a category.'); return; }
    if (!document.getElementById('entryDate').value) { alert('Please enter a date.'); return; }
    if (!document.getElementById('entryDescription').value.trim()) { alert('Please enter a description.'); return; }
    if (!document.getElementById('entryAmount').value) { alert('Please enter an amount.'); return; }

    const body = {
        category_id: categoryId,
        entry_date:  document.getElementById('entryDate').value,
        description: document.getElementById('entryDescription').value.trim(),
        type:        type,
        amount:      document.getElementById('entryAmount').value,
        status:      document.getElementById('entryStatus').value,
        notes:       document.getElementById('entryNotes').value.trim(),
    };

    const url    = id ? `/cash-flow/entries/${id}` : '/cash-flow/entries';
    const method = id ? 'PUT' : 'POST';

    const resp = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    });

    if (resp.ok) {
        window.location.reload();
    } else {
        const err = await resp.json().catch(() => ({}));
        alert('Error: ' + (err.message || 'Could not save entry.'));
    }
}

async function deleteEntry(id) {
    if (!confirm('Delete this entry?')) return;
    const resp = await fetch(`/cash-flow/entries/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    if (resp.ok) {
        window.location.reload();
    } else {
        alert('Could not delete entry.');
    }
}

// ── Opening Balance Modal ────────────────────────────────────────────────────
function editOpeningBalance() {
    document.getElementById('obModal').style.display = 'block';
}
function closeObModal() {
    document.getElementById('obModal').style.display = 'none';
}
async function saveOpeningBalance() {
    const amount = document.getElementById('obAmount').value;
    const resp = await fetch('/cash-flow/opening-balance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ opening_balance: amount }),
    });
    if (resp.ok) {
        window.location.reload();
    } else {
        alert('Could not save opening balance.');
    }
}

// ── Categories Modal ─────────────────────────────────────────────────────────
function openCatModal() {
    document.getElementById('catModal').style.display = 'block';
}
function closeCatModal() {
    document.getElementById('catModal').style.display = 'none';
}

async function addCategory(type) {
    const inputId = type === 'income' ? 'newIncomeCat' : 'newExpenseCat';
    const name = document.getElementById(inputId).value.trim();
    if (!name) return;

    const resp = await fetch('/cash-flow/categories', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ name, type }),
    });

    if (resp.ok) {
        const data = await resp.json();
        const listId = type === 'income' ? 'incomeCatList' : 'expenseCatList';
        const li = document.createElement('li');
        li.id = `cat-${data.id}`;
        li.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:0.4rem 0;border-bottom:1px solid #f1f5f9;';
        li.innerHTML = `<span style="color:#334155;font-size:0.875rem;">${data.name}</span>
            <button onclick="deleteCategory(${data.id})" style="background:#fee2e2;border:none;border-radius:0.375rem;padding:0.2rem 0.6rem;font-size:0.75rem;cursor:pointer;color:#b91c1c;font-weight:600;">Remove</button>`;
        document.getElementById(listId).appendChild(li);
        document.getElementById(inputId).value = '';
    } else {
        alert('Could not add category.');
    }
}

async function deleteCategory(id) {
    if (!confirm('Remove this category? Any entries assigned to it will lose their category link.')) return;
    const resp = await fetch(`/cash-flow/categories/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    if (resp.ok) {
        window.location.reload();
    } else {
        alert('Could not remove category.');
    }
}

// Close modals on backdrop click
['entryModal','obModal','catModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>

</x-layout>
