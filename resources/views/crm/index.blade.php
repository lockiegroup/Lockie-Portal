<x-layout title="Customer Insights — Lockie Portal">
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div style="margin-bottom:1.75rem;">
        <h1 class="text-2xl font-bold text-slate-800">Customer Insights</h1>
        <p class="text-slate-500 mt-1 text-sm">Ranked by spend in the last 12 months vs the prior 12 months.</p>
        @if($salesFrom && $salesTo)
            <p class="text-xs text-slate-400 mt-1">Data covers: <span class="text-slate-500 font-medium">{{ $salesFrom }} – {{ $salesTo }}</span></p>
        @endif
    </div>

    {{-- Filters --}}
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:1.25rem;">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search customer or code…"
            style="flex:1;min-width:180px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#1e293b;background:#fff;outline:none;">

        <select name="warehouse"
            style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#1e293b;background:#fff;">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $w)
                <option value="{{ $w }}" {{ $warehouse === $w ? 'selected' : '' }}>{{ $w }}</option>
            @endforeach
        </select>

        <button type="submit"
            style="padding:8px 16px;background:#1e293b;color:#fff;border:none;border-radius:8px;font-size:0.875rem;font-weight:500;cursor:pointer;">
            Filter
        </button>

        @if($search || $warehouse || $filter)
            <a href="{{ route('crm.index') }}"
               style="padding:8px 14px;background:#f1f5f9;color:#64748b;border-radius:8px;font-size:0.875rem;text-decoration:none;">
                Clear
            </a>
        @endif

        <a href="{{ route('crm.export', array_filter(['search' => $search, 'warehouse' => $warehouse, 'filter' => $filter, 'contacted_days' => $filter === 'contacted' ? $contactedDays : null])) }}"
           style="padding:8px 14px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:8px;font-size:0.875rem;font-weight:500;text-decoration:none;white-space:nowrap;">
            ↓ Export CSV
        </a>

        <button type="button" onclick="openBulkModal()"
            style="padding:8px 14px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;font-size:0.875rem;font-weight:500;cursor:pointer;white-space:nowrap;">
            Bulk Log Contact
        </button>
    </form>

    {{-- Quick filter tabs --}}
    <div style="display:flex;gap:8px;margin-bottom:{{ $filter === 'contacted' ? '0.5rem' : '1.5rem' }};flex-wrap:wrap;align-items:center;">
        <a href="{{ route('crm.index', array_filter(['warehouse' => $warehouse, 'search' => $search])) }}"
           style="padding:6px 14px;border-radius:999px;font-size:0.8125rem;font-weight:500;text-decoration:none;
                  {{ !$filter ? 'background:#1e293b;color:#fff;' : 'background:#f1f5f9;color:#64748b;' }}">
            All customers
        </a>
        <a href="{{ route('crm.index', array_filter(['filter' => 'dropoff', 'warehouse' => $warehouse, 'search' => $search])) }}"
           style="padding:6px 14px;border-radius:999px;font-size:0.8125rem;font-weight:500;text-decoration:none;
                  {{ $filter === 'dropoff' ? 'background:#dc2626;color:#fff;' : 'background:#fef2f2;color:#dc2626;' }}">
            ↘ Dropping off
        </a>
        <a href="{{ route('crm.index', array_filter(['filter' => 'overdue', 'warehouse' => $warehouse, 'search' => $search])) }}"
           style="padding:6px 14px;border-radius:999px;font-size:0.8125rem;font-weight:500;text-decoration:none;
                  {{ $filter === 'overdue' ? 'background:#d97706;color:#fff;' : 'background:#fffbeb;color:#d97706;' }}">
            ⏱ Overdue for order
        </a>
        <a href="{{ route('crm.index', array_filter(['filter' => 'contacted', 'contacted_days' => $contactedDays, 'warehouse' => $warehouse, 'search' => $search])) }}"
           style="padding:6px 14px;border-radius:999px;font-size:0.8125rem;font-weight:500;text-decoration:none;
                  {{ $filter === 'contacted' ? 'background:#0369a1;color:#fff;' : 'background:#eff6ff;color:#0369a1;' }}">
            ✓ Recently contacted
        </a>
    </div>

    {{-- Period chips (only when "Recently contacted" is active) --}}
    @if($filter === 'contacted')
    <div style="display:flex;gap:6px;margin-bottom:1.5rem;align-items:center;">
        <span style="font-size:0.75rem;color:#94a3b8;font-weight:500;">Period:</span>
        @foreach(['all' => 'All time', 7 => '7 days', 30 => '30 days', 60 => '60 days', 90 => '90 days'] as $days => $label)
            <a href="{{ route('crm.index', array_filter(['filter' => 'contacted', 'contacted_days' => $days, 'warehouse' => $warehouse, 'search' => $search])) }}"
               style="padding:3px 10px;border-radius:999px;font-size:0.75rem;font-weight:500;text-decoration:none;
                      {{ $contactedDays === $days ? 'background:#0369a1;color:#fff;' : 'background:#e0f2fe;color:#0369a1;' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    @endif

    {{-- Table --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;min-width:900px;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th style="padding:10px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Customer</th>
                    <th style="padding:10px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Last 12m</th>
                    <th style="padding:10px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Prev 12m</th>
                    <th style="padding:10px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Change</th>
                    <th style="padding:10px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Last Order</th>
                    <th style="padding:10px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Frequency</th>
                    <th style="padding:10px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Expected Next</th>
                    <th style="padding:10px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Last Contact</th>
                    <th style="padding:10px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                    @php
                        $pct       = $c->pct_change;
                        $hasGrown  = $pct !== null && $pct >= 5;
                        $hasDrop   = $pct !== null && $pct <= -5;
                        $pctColour = $hasGrown ? '#16a34a' : ($hasDrop ? '#dc2626' : '#94a3b8');
                        $pctBg     = $hasGrown ? '#f0fdf4' : ($hasDrop ? '#fef2f2' : '#f8fafc');
                        $daysSince = $c->last_order ? $c->last_order->diffInDays($asOf) : null;

                        // Expected next order colour (relative to data as-of date)
                        $nextColour = '#64748b';
                        $nextBg     = 'transparent';
                        if ($c->expected_next) {
                            $daysUntil = $asOf->diffInDays($c->expected_next, false); // negative = overdue
                            if ($daysUntil < 0)        { $nextColour = '#dc2626'; $nextBg = '#fef2f2'; }
                            elseif ($daysUntil <= 14)  { $nextColour = '#d97706'; $nextBg = '#fffbeb'; }
                        }
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9;" class="crm-row">
                        <td style="padding:11px 16px;">
                            <div style="display:flex;align-items:center;gap:9px;">
                                @if($c->is_dropoff)
                                    <span title="Dropping off" style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#fef2f2;flex-shrink:0;">
                                        <svg style="width:11px;height:11px;color:#dc2626;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                                    </span>
                                @endif
                                <div>
                                    <a href="{{ route('crm.show', ['customerCode' => $c->customer_code, 'warehouse' => $warehouse]) }}"
                                       style="font-weight:600;color:#1e293b;text-decoration:none;">
                                        {{ $c->customer ?: $c->customer_code }}
                                    </a>
                                    <div style="font-size:0.75rem;color:#94a3b8;margin-top:1px;">
                                        {{ $c->customer_code }}
                                        @if($c->customer_type) &bull; {{ $c->customer_type }} @endif
                                        @if($c->key_account && $c->key_account->user_id)
                                            @php $ka = $c->key_account; @endphp
                                            &bull; <a href="{{ route('key-accounts.show', $ka->id) }}"
                                                style="color:{{ $ka->type === 'key' ? '#0369a1' : '#059669' }};text-decoration:none;font-weight:500;">
                                                {{ $ka->type === 'key' ? 'Key Account' : 'Growth Account' }}
                                            </a>
                                            <span style="color:#94a3b8;">({{ $ka->user->name }})</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:11px 16px;text-align:right;font-weight:600;color:#1e293b;">
                            £{{ number_format($c->current_total, 0) }}
                        </td>
                        <td style="padding:11px 16px;text-align:right;color:#64748b;">
                            {{ $c->prev_total > 0 ? '£' . number_format($c->prev_total, 0) : '—' }}
                        </td>
                        <td style="padding:11px 16px;text-align:right;">
                            @if($pct !== null && abs($pct) >= 1)
                                <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:600;color:{{ $pctColour }};background:{{ $pctBg }};">
                                    {{ $pct > 0 ? '+' : '' }}{{ number_format($pct, 0) }}%
                                </span>
                            @else
                                <span style="color:#cbd5e1;font-size:0.75rem;">—</span>
                            @endif
                        </td>
                        <td style="padding:11px 16px;">
                            @if($c->last_order)
                                <span style="color:{{ $daysSince > 180 ? '#dc2626' : ($daysSince > 90 ? '#d97706' : '#64748b') }};font-size:0.875rem;">
                                    {{ $c->last_order->format('d M Y') }}
                                </span>
                                <div style="font-size:0.75rem;color:#94a3b8;">{{ $c->last_order->diffForHumans($asOf) }}</div>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td style="padding:11px 16px;color:#64748b;font-size:0.875rem;">
                            @if($c->avg_days)
                                every ~{{ $c->avg_days }}d
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td style="padding:11px 16px;">
                            @if($c->expected_next)
                                <div style="display:inline-block;padding:3px 9px;border-radius:6px;background:{{ $nextBg !== 'transparent' ? $nextBg : 'transparent' }};">
                                    <span style="font-size:0.875rem;color:{{ $nextColour }};font-weight:{{ $c->is_overdue ? '600' : '400' }};">
                                        {{ $c->expected_next->format('d M Y') }}
                                    </span>
                                    <div style="font-size:0.75rem;color:{{ $nextColour }};opacity:0.8;">
                                        @if($c->is_overdue)
                                            {{ abs((int) $asOf->diffInDays($c->expected_next, false)) }}d overdue
                                        @else
                                            in {{ $asOf->diffInDays($c->expected_next) }}d
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span style="color:#cbd5e1;font-size:0.75rem;">Not enough data</span>
                            @endif
                        </td>
                        <td style="padding:11px 16px;">
                            @php $lc = $c->key_account?->latestContact?->contacted_at ? \Carbon\Carbon::parse($c->key_account->latestContact->contacted_at) : null; @endphp
                            @if($lc)
                                <span style="font-size:0.875rem;color:#334155;font-weight:{{ $filter === 'contacted' ? '600' : '400' }};">{{ $lc->format('d M Y') }}</span>
                                <div style="font-size:0.75rem;color:#94a3b8;">{{ $lc->diffForHumans() }}</div>
                            @else
                                <span style="color:#cbd5e1;font-size:0.875rem;">—</span>
                            @endif
                        </td>
                        <td style="padding:11px 16px;">
                            <a href="{{ route('crm.show', ['customerCode' => $c->customer_code, 'warehouse' => $warehouse]) }}"
                               style="font-size:0.75rem;color:#94a3b8;text-decoration:none;white-space:nowrap;">View &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="padding:3rem;text-align:center;color:#94a3b8;">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Show more --}}
    @if($hasMore)
        <div style="margin-top:1.25rem;text-align:center;">
            <a href="{{ route('crm.index', array_filter(['warehouse' => $warehouse, 'search' => $search, 'filter' => $filter, 'limit' => $limit + 100])) }}"
               style="display:inline-block;padding:9px 24px;background:#f1f5f9;color:#475569;border-radius:8px;font-size:0.875rem;font-weight:500;text-decoration:none;border:1px solid #e2e8f0;">
                Show more <span style="color:#94a3b8;">(showing {{ $customers->count() }} of {{ $totalCount }})</span>
            </a>
        </div>
    @endif

    <div style="margin-top:0.75rem;font-size:0.75rem;color:#94a3b8;text-align:right;">
        Showing {{ $customers->count() }} of {{ $totalCount }} customer{{ $totalCount !== 1 ? 's' : '' }}
        @if($warehouse) in {{ $warehouse }} @endif
        @if($filter) &bull; {{ $filter }} @endif
    </div>

</main>
<style>.crm-row:hover { background:#f8fafc; }</style>

{{-- Bulk Log Contact Modal --}}
<div id="bulk-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div style="position:absolute;inset:0;background:rgba(15,23,42,0.45);" onclick="closeBulkModal()"></div>
    <div style="position:relative;background:#fff;border-radius:14px;padding:1.5rem;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,0.18);max-height:90vh;overflow-y:auto;">

        {{-- Step 1: Input --}}
        <div id="bulk-step-1">
            <h2 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 0.25rem;">Bulk Log Contact</h2>
            <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1.25rem;">Paste account codes below — one per line, or comma-separated. A contact entry will be added for every matched account.</p>

            <div style="margin-bottom:0.875rem;">
                <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Account Codes <span style="color:#dc2626;">*</span></label>
                <textarea id="bulk-codes" rows="8" placeholder="CUST001&#10;CUST002&#10;CUST003&#10;…"
                    style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.8125rem;font-family:monospace;box-sizing:border-box;outline:none;resize:vertical;"
                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.875rem;">
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Date <span style="color:#dc2626;">*</span></label>
                    <input type="date" id="bulk-date" value="{{ date('Y-m-d') }}"
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Note <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="bulk-note" placeholder="e.g. Eshot sent – Summer 2026" maxlength="2000"
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
            </div>

            <div id="bulk-step1-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 12px;font-size:0.8125rem;color:#dc2626;margin-bottom:0.875rem;"></div>

            <div style="display:flex;gap:0.5rem;">
                <button type="button" onclick="closeBulkModal()"
                    style="flex:1;padding:0.5rem;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;font-size:0.875rem;font-weight:500;border-radius:8px;cursor:pointer;">
                    Cancel
                </button>
                <button type="button" id="bulk-preview-btn" onclick="bulkPreview()"
                    style="flex:2;padding:0.5rem;background:#1d4ed8;color:#fff;font-size:0.875rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                    Preview
                </button>
            </div>
        </div>

        {{-- Step 2: Preview --}}
        <div id="bulk-step-2" style="display:none;">
            <h2 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 1rem;">Confirm Bulk Log</h2>

            <div id="bulk-matched-wrap" style="margin-bottom:1rem;">
                <p id="bulk-match-summary" style="font-size:0.875rem;font-weight:600;color:#1e293b;margin:0 0 0.5rem;"></p>
                <div id="bulk-matched-list" style="max-height:160px;overflow-y:auto;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:6px 10px;font-size:0.8rem;line-height:1.7;"></div>
            </div>

            <div id="bulk-notfound-wrap" style="display:none;margin-bottom:1rem;">
                <p style="font-size:0.8125rem;font-weight:600;color:#dc2626;margin:0 0 0.5rem;">Not found in system — will be skipped:</p>
                <div id="bulk-notfound-list" style="max-height:100px;overflow-y:auto;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:6px 10px;font-size:0.8rem;font-family:monospace;color:#991b1b;line-height:1.7;"></div>
            </div>

            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:9px 12px;font-size:0.8125rem;color:#1d4ed8;margin-bottom:1rem;">
                <span id="bulk-confirm-summary"></span>
            </div>

            <div id="bulk-step2-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 12px;font-size:0.8125rem;color:#dc2626;margin-bottom:0.875rem;"></div>

            <div style="display:flex;gap:0.5rem;">
                <button type="button" onclick="bulkBack()"
                    style="flex:1;padding:0.5rem;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;font-size:0.875rem;font-weight:500;border-radius:8px;cursor:pointer;">
                    Back
                </button>
                <button type="button" id="bulk-confirm-btn" onclick="bulkConfirm()"
                    style="flex:2;padding:0.5rem;background:#16a34a;color:#fff;font-size:0.875rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                    Confirm &amp; Log
                </button>
            </div>
        </div>

        {{-- Step 3: Done --}}
        <div id="bulk-step-3" style="display:none;text-align:center;padding:1rem 0;">
            <div style="font-size:2rem;margin-bottom:0.75rem;">✓</div>
            <p id="bulk-done-msg" style="font-size:1rem;font-weight:600;color:#1e293b;margin:0 0 0.25rem;"></p>
            <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1.5rem;">Contact entries have been added.</p>
            <button type="button" onclick="closeBulkModal()"
                style="padding:0.5rem 1.5rem;background:#1e293b;color:#fff;font-size:0.875rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                Close
            </button>
        </div>

    </div>
</div>

<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    window.openBulkModal = function () {
        document.getElementById('bulk-modal').style.display = 'flex';
        showBulkStep(1);
        document.getElementById('bulk-codes').focus();
    };

    window.closeBulkModal = function () {
        document.getElementById('bulk-modal').style.display = 'none';
    };

    function showBulkStep(n) {
        [1, 2, 3].forEach(i => {
            document.getElementById('bulk-step-' + i).style.display = i === n ? 'block' : 'none';
        });
        document.getElementById('bulk-step1-error').style.display = 'none';
        document.getElementById('bulk-step2-error').style.display = 'none';
    }

    window.bulkBack = function () { showBulkStep(1); };

    window.bulkPreview = async function () {
        const codes = document.getElementById('bulk-codes').value.trim();
        const date  = document.getElementById('bulk-date').value;
        const note  = document.getElementById('bulk-note').value.trim();
        const errEl = document.getElementById('bulk-step1-error');

        errEl.style.display = 'none';
        if (!codes) { errEl.textContent = 'Please paste at least one account code.'; errEl.style.display = 'block'; return; }
        if (!date)  { errEl.textContent = 'Please select a date.'; errEl.style.display = 'block'; return; }
        if (!note)  { errEl.textContent = 'Please enter a note.'; errEl.style.display = 'block'; return; }

        const btn = document.getElementById('bulk-preview-btn');
        btn.disabled = true; btn.textContent = 'Checking…';

        try {
            const res  = await fetch('{{ route("crm.bulk-contacts.preview") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ account_codes: codes }),
            });
            const data = await res.json();

            // Matched list
            const matchedEl = document.getElementById('bulk-matched-list');
            const summaryEl = document.getElementById('bulk-match-summary');
            summaryEl.textContent = data.matched.length + ' account' + (data.matched.length !== 1 ? 's' : '') + ' found:';
            matchedEl.innerHTML = data.matched.length
                ? data.matched.map(m => `<span style="color:#475569;"><strong style="font-family:monospace">${escHtml(m.code)}</strong> — ${escHtml(m.name)}</span>`).join('<br>')
                : '<span style="color:#94a3b8;">None</span>';

            // Not-found list
            const nfWrap = document.getElementById('bulk-notfound-wrap');
            const nfList = document.getElementById('bulk-notfound-list');
            if (data.not_found.length) {
                nfList.textContent = data.not_found.join('\n');
                nfWrap.style.display = 'block';
            } else {
                nfWrap.style.display = 'none';
            }

            // Confirm summary
            const d = document.getElementById('bulk-date').value;
            document.getElementById('bulk-confirm-summary').textContent =
                'Logging "' + note + '" on ' + d + ' for ' + data.matched.length + ' account' + (data.matched.length !== 1 ? 's' : '') + '.';

            const confirmBtn = document.getElementById('bulk-confirm-btn');
            confirmBtn.disabled = data.matched.length === 0;

            showBulkStep(2);
        } catch (e) {
            errEl.textContent = 'Preview failed: ' + e.message;
            errEl.style.display = 'block';
        } finally {
            btn.disabled = false; btn.textContent = 'Preview';
        }
    };

    window.bulkConfirm = async function () {
        const codes = document.getElementById('bulk-codes').value.trim();
        const date  = document.getElementById('bulk-date').value;
        const note  = document.getElementById('bulk-note').value.trim();
        const errEl = document.getElementById('bulk-step2-error');
        const btn   = document.getElementById('bulk-confirm-btn');

        errEl.style.display = 'none';
        btn.disabled = true; btn.textContent = 'Logging…';

        try {
            const res  = await fetch('{{ route("crm.bulk-contacts.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ account_codes: codes, contacted_at: date, note: note }),
            });
            const data = await res.json();

            if (!res.ok) {
                errEl.textContent = 'Error: ' + (data.message || JSON.stringify(data));
                errEl.style.display = 'block';
                return;
            }

            document.getElementById('bulk-done-msg').textContent =
                'Logged contact for ' + data.logged + ' account' + (data.logged !== 1 ? 's' : '') + '.';
            showBulkStep(3);
        } catch (e) {
            errEl.textContent = 'Submit failed: ' + e.message;
            errEl.style.display = 'block';
        } finally {
            btn.disabled = false; btn.textContent = 'Confirm & Log';
        }
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeBulkModal();
    });

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
</script>

</x-layout>
