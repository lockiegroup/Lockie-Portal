<x-layout title="Cash Flow — Lockie Portal">

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

        {{-- Header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:1.75rem;">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Cash Flow</h1>
                <p class="text-sm text-slate-500 mt-1">Plan and track income and expenses. Mark entries as forecast then update to actual when confirmed.</p>
            </div>
            <button onclick="openModal()"
                style="background:#0f172a;color:#fff;font-size:0.8rem;font-weight:600;padding:8px 18px;border-radius:8px;border:none;cursor:pointer;white-space:nowrap;flex-shrink:0;">
                + Add Entry
            </button>
        </div>

        {{-- Controls bar --}}
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:1.25rem;">

            {{-- Monthly / Daily toggle --}}
            <div style="display:flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <a href="{{ route('cash-flow.index', ['view' => 'monthly', 'horizon' => $horizon]) }}"
                    style="padding:6px 14px;font-size:0.78rem;font-weight:600;text-decoration:none;{{ $viewMode === 'monthly' ? 'background:#0f172a;color:#fff;' : 'background:#fff;color:#64748b;' }}">
                    Monthly
                </a>
                <a href="{{ route('cash-flow.index', ['view' => 'daily', 'horizon' => $horizon]) }}"
                    style="padding:6px 14px;font-size:0.78rem;font-weight:600;text-decoration:none;border-left:1px solid #e2e8f0;{{ $viewMode === 'daily' ? 'background:#0f172a;color:#fff;' : 'background:#fff;color:#64748b;' }}">
                    Daily
                </a>
            </div>

            {{-- Horizon selector --}}
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="font-size:0.75rem;color:#64748b;white-space:nowrap;">Horizon:</span>
                <div style="display:flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                    @foreach([3 => '3m', 6 => '6m', 12 => '12m', 18 => '18m', 24 => '24m'] as $val => $label)
                        <a href="{{ route('cash-flow.index', ['horizon' => $val, 'view' => $viewMode]) }}"
                            style="padding:6px 10px;font-size:0.75rem;font-weight:600;text-decoration:none;white-space:nowrap;{{ $val !== 3 ? 'border-left:1px solid #e2e8f0;' : '' }}{{ $horizon == $val ? 'background:#0f172a;color:#fff;' : 'background:#fff;color:#64748b;' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Legend --}}
            <div style="display:flex;align-items:center;gap:10px;margin-left:auto;flex-wrap:wrap;">
                <span style="display:flex;align-items:center;gap:5px;font-size:0.72rem;color:#64748b;">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#16a34a;"></span> Actual
                </span>
                <span style="display:flex;align-items:center;gap:5px;font-size:0.72rem;color:#64748b;">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#94a3b8;border:2px dashed #94a3b8;background:none;"></span> Forecast
                </span>
            </div>
        </div>

        {{-- MONTHLY SUMMARY --}}
        @if($viewMode === 'monthly')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" style="margin-bottom:1.5rem;">
            <div style="padding:10px 18px;border-bottom:1px solid #f1f5f9;background:#f8fafc;">
                <h2 style="font-size:0.78rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">Monthly Summary</h2>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                            <th style="padding:8px 18px;text-align:left;font-weight:600;color:#64748b;white-space:nowrap;">Month</th>
                            <th style="padding:8px 18px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Income</th>
                            <th style="padding:8px 18px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Expenses</th>
                            <th style="padding:8px 18px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($months as $m)
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:10px 18px;color:#374151;font-weight:500;">{{ $m['label'] }}</td>
                            <td style="padding:10px 18px;text-align:right;">
                                @if($m['income'] > 0)
                                    <span style="color:#16a34a;font-weight:600;">£{{ number_format($m['income'], 2) }}</span>
                                    @if($m['actual_in'] > 0 && $m['forecast_in'] > 0)
                                        <div style="font-size:0.7rem;color:#94a3b8;margin-top:1px;">
                                            £{{ number_format($m['actual_in'], 2) }} actual &middot; £{{ number_format($m['forecast_in'], 2) }} forecast
                                        </div>
                                    @elseif($m['forecast_in'] > 0)
                                        <div style="font-size:0.7rem;color:#94a3b8;margin-top:1px;">forecast</div>
                                    @endif
                                @else
                                    <span style="color:#cbd5e1;">—</span>
                                @endif
                            </td>
                            <td style="padding:10px 18px;text-align:right;">
                                @if($m['expense'] > 0)
                                    <span style="color:#dc2626;font-weight:600;">£{{ number_format($m['expense'], 2) }}</span>
                                    @if($m['actual_out'] > 0 && $m['forecast_out'] > 0)
                                        <div style="font-size:0.7rem;color:#94a3b8;margin-top:1px;">
                                            £{{ number_format($m['actual_out'], 2) }} actual &middot; £{{ number_format($m['forecast_out'], 2) }} forecast
                                        </div>
                                    @elseif($m['forecast_out'] > 0)
                                        <div style="font-size:0.7rem;color:#94a3b8;margin-top:1px;">forecast</div>
                                    @endif
                                @else
                                    <span style="color:#cbd5e1;">—</span>
                                @endif
                            </td>
                            <td style="padding:10px 18px;text-align:right;font-weight:700;color:{{ $m['net'] >= 0 ? '#16a34a' : '#dc2626' }};">
                                {{ $m['net'] >= 0 ? '+' : '' }}£{{ number_format($m['net'], 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    @php $ti = collect($months)->sum('income'); $to2 = collect($months)->sum('expense'); $tn = $ti - $to2; @endphp
                    <tfoot>
                        <tr style="background:#f8fafc;border-top:2px solid #e2e8f0;">
                            <td style="padding:10px 18px;font-weight:700;color:#0f172a;">Total</td>
                            <td style="padding:10px 18px;text-align:right;font-weight:700;color:#16a34a;">£{{ number_format($ti, 2) }}</td>
                            <td style="padding:10px 18px;text-align:right;font-weight:700;color:#dc2626;">£{{ number_format($to2, 2) }}</td>
                            <td style="padding:10px 18px;text-align:right;font-weight:700;color:{{ $tn >= 0 ? '#16a34a' : '#dc2626' }};">
                                {{ $tn >= 0 ? '+' : '' }}£{{ number_format($tn, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

        {{-- DAILY VIEW --}}
        @if($viewMode === 'daily')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" style="margin-bottom:1.5rem;">
            <div style="padding:10px 18px;border-bottom:1px solid #f1f5f9;background:#f8fafc;">
                <h2 style="font-size:0.78rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">Daily Cash Flow</h2>
            </div>
            @if(empty($daily))
                <div style="padding:40px 24px;text-align:center;color:#94a3b8;font-size:0.875rem;">No entries for this period.</div>
            @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                            <th style="padding:8px 18px;text-align:left;font-weight:600;color:#64748b;white-space:nowrap;">Date</th>
                            <th style="padding:8px 18px;text-align:left;font-weight:600;color:#64748b;">Description</th>
                            <th style="padding:8px 18px;text-align:center;font-weight:600;color:#64748b;">Type</th>
                            <th style="padding:8px 18px;text-align:center;font-weight:600;color:#64748b;">Status</th>
                            <th style="padding:8px 18px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Amount</th>
                            <th style="padding:8px 18px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($daily as $row)
                        <tr style="border-bottom:1px solid #f1f5f9;{{ $row['status'] === 'forecast' ? 'opacity:0.8;' : '' }}"
                            onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                            <td style="padding:9px 18px;color:#64748b;white-space:nowrap;font-size:0.8rem;">{{ $row['label'] }}</td>
                            <td style="padding:9px 18px;color:#0f172a;font-weight:500;">
                                {{ $row['description'] }}
                                @if($row['category'])
                                    <span style="margin-left:6px;padding:1px 7px;background:#f1f5f9;color:#475569;border-radius:999px;font-size:0.68rem;font-weight:500;">{{ $row['category'] }}</span>
                                @endif
                            </td>
                            <td style="padding:9px 18px;text-align:center;">
                                @if($row['type'] === 'income')
                                    <span style="padding:2px 9px;background:#dcfce7;color:#15803d;border-radius:999px;font-size:0.7rem;font-weight:600;">IN</span>
                                @else
                                    <span style="padding:2px 9px;background:#fee2e2;color:#b91c1c;border-radius:999px;font-size:0.7rem;font-weight:600;">OUT</span>
                                @endif
                            </td>
                            <td style="padding:9px 18px;text-align:center;">
                                @if($row['status'] === 'actual')
                                    <span style="padding:2px 9px;background:#dcfce7;color:#15803d;border-radius:999px;font-size:0.7rem;font-weight:600;">Actual</span>
                                @else
                                    <span style="padding:2px 9px;background:#f1f5f9;color:#64748b;border-radius:999px;font-size:0.7rem;font-weight:500;border:1px dashed #cbd5e1;">Forecast</span>
                                @endif
                            </td>
                            <td style="padding:9px 18px;text-align:right;font-weight:600;color:{{ $row['type'] === 'income' ? '#16a34a' : '#dc2626' }};white-space:nowrap;">
                                {{ $row['type'] === 'income' ? '+' : '−' }}£{{ number_format($row['amount'], 2) }}
                            </td>
                            <td style="padding:9px 18px;text-align:right;font-weight:700;white-space:nowrap;color:{{ $row['balance'] >= 0 ? '#16a34a' : '#dc2626' }};">
                                {{ $row['balance'] >= 0 ? '' : '−' }}£{{ number_format(abs($row['balance']), 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

        {{-- ENTRIES TABLE --}}
        @if($entries->isEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm" style="padding:56px 24px;text-align:center;">
                <svg style="width:36px;height:36px;margin:0 auto 12px;color:#cbd5e1;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                <p style="font-size:0.875rem;font-weight:500;color:#64748b;">No entries yet for this period</p>
                <p style="font-size:0.8rem;color:#94a3b8;margin-top:4px;">Click <strong>+ Add Entry</strong> to get started.</p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div style="padding:10px 18px;border-bottom:1px solid #f1f5f9;background:#f8fafc;display:flex;align-items:center;justify-content:space-between;">
                    <h2 style="font-size:0.78rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">All Entries</h2>
                    <span style="font-size:0.72rem;color:#94a3b8;">{{ $entries->count() }} {{ $entries->count() === 1 ? 'entry' : 'entries' }}</span>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                        <thead>
                            <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                <th style="padding:8px 18px;text-align:left;font-weight:600;color:#64748b;white-space:nowrap;">Date</th>
                                <th style="padding:8px 18px;text-align:left;font-weight:600;color:#64748b;">Description</th>
                                <th style="padding:8px 18px;text-align:left;font-weight:600;color:#64748b;">Category</th>
                                <th style="padding:8px 18px;text-align:center;font-weight:600;color:#64748b;">Type</th>
                                <th style="padding:8px 18px;text-align:center;font-weight:600;color:#64748b;">Status</th>
                                <th style="padding:8px 18px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Amount</th>
                                <th style="padding:8px 18px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                <tr style="border-bottom:1px solid #f1f5f9;"
                                    onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''"
                                    data-entry="{{ json_encode([
                                        'id'          => $entry->id,
                                        'entry_date'  => $entry->entry_date->format('Y-m-d'),
                                        'description' => $entry->description,
                                        'type'        => $entry->type,
                                        'amount'      => (string) $entry->amount,
                                        'status'      => $entry->status,
                                        'category'    => $entry->category ?? '',
                                        'notes'       => $entry->notes ?? '',
                                    ]) }}">
                                    <td style="padding:10px 18px;color:#64748b;white-space:nowrap;font-size:0.8rem;">
                                        {{ $entry->entry_date->format('d M Y') }}
                                    </td>
                                    <td style="padding:10px 18px;color:#0f172a;font-weight:500;max-width:240px;">
                                        {{ $entry->description }}
                                        @if($entry->notes)
                                            <div style="font-size:0.72rem;color:#94a3b8;margin-top:1px;font-weight:400;">{{ $entry->notes }}</div>
                                        @endif
                                    </td>
                                    <td style="padding:10px 18px;">
                                        @if($entry->category)
                                            <span style="padding:2px 8px;background:#f1f5f9;color:#475569;border-radius:999px;font-size:0.7rem;font-weight:500;">{{ $entry->category }}</span>
                                        @else
                                            <span style="color:#e2e8f0;font-size:0.8rem;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 18px;text-align:center;">
                                        @if($entry->type === 'income')
                                            <span style="padding:2px 9px;background:#dcfce7;color:#15803d;border-radius:999px;font-size:0.7rem;font-weight:600;">IN</span>
                                        @else
                                            <span style="padding:2px 9px;background:#fee2e2;color:#b91c1c;border-radius:999px;font-size:0.7rem;font-weight:600;">OUT</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 18px;text-align:center;">
                                        @if($entry->status === 'actual')
                                            <span style="padding:2px 9px;background:#dcfce7;color:#15803d;border-radius:999px;font-size:0.7rem;font-weight:600;">Actual</span>
                                        @else
                                            <span style="padding:2px 9px;background:#f1f5f9;color:#64748b;border-radius:999px;font-size:0.7rem;font-weight:500;border:1px dashed #cbd5e1;">Forecast</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 18px;text-align:right;font-weight:600;white-space:nowrap;color:{{ $entry->type === 'income' ? '#16a34a' : '#dc2626' }};">
                                        {{ $entry->type === 'income' ? '+' : '−' }}£{{ number_format($entry->amount, 2) }}
                                    </td>
                                    <td style="padding:10px 18px;text-align:right;white-space:nowrap;">
                                        <button onclick="editEntry(this.closest('tr'))"
                                            style="background:none;border:none;cursor:pointer;color:#64748b;padding:4px 8px;border-radius:6px;font-size:0.78rem;"
                                            onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">Edit</button>
                                        <button onclick="deleteEntry({{ $entry->id }})"
                                            style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px 8px;border-radius:6px;font-size:0.78rem;"
                                            onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </main>

    {{-- Add / Edit Modal --}}
    <div id="cf-modal" style="display:none;position:fixed;inset:0;z-index:50;align-items:flex-start;justify-content:center;padding:40px 16px;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.45);" onclick="closeModal()"></div>
        <div style="position:relative;background:#fff;border-radius:14px;padding:28px 28px 24px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <h2 id="cf-modal-title" style="font-size:1rem;font-weight:700;color:#0f172a;">Add Entry</h2>
                <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;"
                    onmouseover="this.style.color='#475569'" onmouseout="this.style.color='#94a3b8'">
                    <svg style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div id="cf-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:0.8rem;padding:8px 12px;border-radius:8px;margin-bottom:14px;"></div>

            <form id="cf-form" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" id="cf-id">

                {{-- Status toggle --}}
                <div>
                    <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:6px;">Status</label>
                    <div id="cf-status-toggle" style="display:flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                        <button type="button" id="btn-forecast" onclick="setStatus('forecast')"
                            style="flex:1;padding:8px;font-size:0.82rem;font-weight:600;border:none;cursor:pointer;transition:all 0.15s;background:#f8fafc;color:#64748b;">
                            Forecast
                        </button>
                        <button type="button" id="btn-actual" onclick="setStatus('actual')"
                            style="flex:1;padding:8px;font-size:0.82rem;font-weight:600;border:none;border-left:1px solid #e2e8f0;cursor:pointer;transition:all 0.15s;background:#f8fafc;color:#64748b;">
                            Actual
                        </button>
                    </div>
                    <input type="hidden" id="cf-status" value="forecast">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Date</label>
                        <input type="date" id="cf-date" required
                            style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Type</label>
                        <select id="cf-type"
                            style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;background:#fff;">
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Description</label>
                    <input type="text" id="cf-description" required placeholder="e.g. Customer payment, Payroll, Rent…"
                        style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Amount (£)</label>
                        <input type="number" id="cf-amount" required min="0.01" step="0.01" placeholder="0.00"
                            style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Category <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" id="cf-category" list="cf-categories-list" placeholder="e.g. Wages, Sales…"
                            style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                        <datalist id="cf-categories-list">
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div>
                    <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Notes <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <textarea id="cf-notes" rows="2" placeholder="Any additional detail…"
                        style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;resize:vertical;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                </div>

                <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:4px;">
                    <button type="button" onclick="closeModal()"
                        style="padding:8px 18px;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;font-size:0.85rem;font-weight:500;border-radius:8px;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit" id="cf-submit"
                        style="padding:8px 22px;background:#0f172a;color:#fff;font-size:0.85rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                        Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function setStatus(val) {
        document.getElementById('cf-status').value = val;
        const fBtn = document.getElementById('btn-forecast');
        const aBtn = document.getElementById('btn-actual');
        if (val === 'forecast') {
            fBtn.style.background = '#0f172a'; fBtn.style.color = '#fff';
            aBtn.style.background = '#f8fafc'; aBtn.style.color = '#64748b';
        } else {
            aBtn.style.background = '#16a34a'; aBtn.style.color = '#fff';
            fBtn.style.background = '#f8fafc'; fBtn.style.color = '#64748b';
        }
    }

    function openModal(entry) {
        const status = entry ? entry.status : 'forecast';
        document.getElementById('cf-id').value          = entry ? entry.id : '';
        document.getElementById('cf-modal-title').textContent = entry ? 'Edit Entry' : 'Add Entry';
        document.getElementById('cf-date').value        = entry ? entry.entry_date : new Date().toISOString().slice(0, 10);
        document.getElementById('cf-type').value        = entry ? entry.type : 'income';
        document.getElementById('cf-description').value = entry ? entry.description : '';
        document.getElementById('cf-amount').value      = entry ? entry.amount : '';
        document.getElementById('cf-category').value    = entry ? (entry.category || '') : '';
        document.getElementById('cf-notes').value       = entry ? (entry.notes || '') : '';
        document.getElementById('cf-error').style.display = 'none';
        setStatus(status);
        document.getElementById('cf-modal').style.display = 'flex';
        setTimeout(() => document.getElementById('cf-description').focus(), 50);
    }

    function editEntry(row) {
        openModal(JSON.parse(row.dataset.entry));
    }

    function closeModal() {
        document.getElementById('cf-modal').style.display = 'none';
    }

    document.getElementById('cf-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const id  = document.getElementById('cf-id').value;
        const url = id ? '/cash-flow/' + id : '{{ route('cash-flow.store') }}';
        const btn = document.getElementById('cf-submit');
        btn.disabled = true; btn.textContent = 'Saving…';

        try {
            const res = await fetch(url, {
                method : id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body   : JSON.stringify({
                    entry_date  : document.getElementById('cf-date').value,
                    type        : document.getElementById('cf-type').value,
                    description : document.getElementById('cf-description').value,
                    amount      : document.getElementById('cf-amount').value,
                    status      : document.getElementById('cf-status').value,
                    category    : document.getElementById('cf-category').value || null,
                    notes       : document.getElementById('cf-notes').value   || null,
                }),
            });

            if (res.ok) {
                window.location.reload();
            } else {
                const data = await res.json();
                const msg  = data.message || Object.values(data.errors ?? {}).flat().join(' ') || 'An error occurred.';
                document.getElementById('cf-error').textContent   = msg;
                document.getElementById('cf-error').style.display = '';
                btn.disabled = false; btn.textContent = 'Save Entry';
            }
        } catch {
            document.getElementById('cf-error').textContent   = 'Network error. Please try again.';
            document.getElementById('cf-error').style.display = '';
            btn.disabled = false; btn.textContent = 'Save Entry';
        }
    });

    async function deleteEntry(id) {
        if (!confirm('Delete this entry? This cannot be undone.')) return;
        const res = await fetch('/cash-flow/' + id, {
            method : 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (res.ok) window.location.reload();
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    </script>

</x-layout>
