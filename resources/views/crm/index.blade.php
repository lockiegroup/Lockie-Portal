<x-layout title="Customer Insights — Lockie Portal">
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-slate-800">Customer Insights</h1>
        <p class="text-slate-500 mt-1 text-sm">Ranked by spend in the last 12 months vs the prior 12 months.</p>
        @if($salesFrom && $salesTo)
            <p class="text-xs text-slate-400 mt-1">Data covers: <span class="text-slate-500 font-medium">{{ $salesFrom }} – {{ $salesTo }}</span></p>
        @endif
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex gap-2.5 flex-wrap items-center mb-5">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search customer or code…"
            class="flex-1 min-w-[180px] px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">

        <select name="warehouse"
            class="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $w)
                <option value="{{ $w }}" {{ $warehouse === $w ? 'selected' : '' }}>{{ $w }}</option>
            @endforeach
        </select>

        <button type="submit"
            class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg border-0 cursor-pointer hover:bg-slate-700 transition-colors">
            Filter
        </button>

        @if($search || $warehouse || $filter)
            <a href="{{ route('crm.index') }}"
               class="px-3.5 py-2 bg-slate-100 text-slate-500 text-sm rounded-lg no-underline hover:bg-slate-200 transition-colors">
                Clear
            </a>
        @endif

        <a href="{{ route('crm.export', array_filter(['search' => $search, 'warehouse' => $warehouse, 'filter' => $filter, 'contacted_days' => $filter === 'contacted' ? $contactedDays : null])) }}"
           class="px-3.5 py-2 bg-green-50 text-green-700 border border-green-200 text-sm font-medium rounded-lg no-underline whitespace-nowrap hover:bg-green-100 transition-colors">
            ↓ Export CSV
        </a>

        <button type="button" onclick="openBulkModal()"
            class="px-3.5 py-2 bg-blue-50 text-blue-700 border border-blue-200 text-sm font-medium rounded-lg cursor-pointer whitespace-nowrap hover:bg-blue-100 transition-colors">
            Bulk Log Contact
        </button>
    </form>

    {{-- Quick filter tabs --}}
    <div class="flex gap-2 flex-wrap items-center {{ $filter === 'contacted' ? 'mb-2' : 'mb-6' }}">
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
    <div class="flex gap-1.5 mb-6 items-center flex-wrap">
        <span class="text-xs text-slate-400 font-medium">Period:</span>
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
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse" style="min-width:900px;">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-4 py-2.5 text-left text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Customer</th>
                    <th class="px-4 py-2.5 text-right text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Last 12m</th>
                    <th class="px-4 py-2.5 text-right text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Prev 12m</th>
                    <th class="px-4 py-2.5 text-right text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Change</th>
                    <th class="px-4 py-2.5 text-left text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Last Order</th>
                    <th class="px-4 py-2.5 text-left text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Frequency</th>
                    <th class="px-4 py-2.5 text-left text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Expected Next</th>
                    <th class="px-4 py-2.5 text-left text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Last Contact</th>
                    <th class="px-4 py-2.5"></th>
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
                    <tr class="crm-row border-b border-slate-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                @if($c->is_dropoff)
                                    <span title="Dropping off" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-50 shrink-0">
                                        <svg class="w-[11px] h-[11px] text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                                    </span>
                                @endif
                                <div>
                                    <a href="{{ route('crm.show', ['customerCode' => $c->customer_code, 'warehouse' => $warehouse]) }}"
                                       class="font-semibold text-slate-800 no-underline">
                                        {{ $c->customer ?: $c->customer_code }}
                                    </a>
                                    <div class="text-xs text-slate-400 mt-px">
                                        {{ $c->customer_code }}
                                        @if($c->key_account && $c->key_account->user_id)
                                            @php $ka = $c->key_account; @endphp
                                            &bull; <a href="{{ route('key-accounts.show', $ka->id) }}"
                                                style="color:{{ $ka->type === 'key' ? '#0369a1' : '#059669' }};text-decoration:none;font-weight:500;">
                                                {{ $ka->type === 'key' ? 'Key Account' : 'Growth Account' }}
                                            </a>
                                            <span class="text-slate-400">({{ $ka->user->name }})</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">
                            £{{ number_format($c->current_total, 0) }}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-500">
                            {{ $c->prev_total > 0 ? '£' . number_format($c->prev_total, 0) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($pct !== null && abs($pct) >= 1)
                                <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:600;color:{{ $pctColour }};background:{{ $pctBg }};">
                                    {{ $pct > 0 ? '+' : '' }}{{ number_format($pct, 0) }}%
                                </span>
                            @else
                                <span style="color:#cbd5e1;font-size:0.75rem;">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($c->last_order)
                                <span style="color:{{ $daysSince > 180 ? '#dc2626' : ($daysSince > 90 ? '#d97706' : '#64748b') }};" class="text-sm">
                                    {{ $c->last_order->format('d M Y') }}
                                </span>
                                <div class="text-xs text-slate-400">{{ $c->last_order->diffForHumans($asOf) }}</div>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-sm">
                            @if($c->avg_days)
                                every ~{{ $c->avg_days }}d
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
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
                                <span class="text-slate-300 text-xs">Not enough data</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php $lc = $c->key_account?->latestContact?->contacted_at ? \Carbon\Carbon::parse($c->key_account->latestContact->contacted_at) : null; @endphp
                            @if($lc)
                                <span style="font-size:0.875rem;color:#334155;font-weight:{{ $filter === 'contacted' ? '600' : '400' }};">{{ $lc->format('d M Y') }}</span>
                                <div class="text-xs text-slate-400">{{ $lc->diffForHumans() }}</div>
                            @else
                                <span class="text-slate-300 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('crm.show', ['customerCode' => $c->customer_code, 'warehouse' => $warehouse]) }}"
                               class="text-xs text-slate-400 no-underline whitespace-nowrap">View &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-slate-400">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Show more --}}
    @if($hasMore)
        <div class="mt-5 text-center">
            <a href="{{ route('crm.index', array_filter(['warehouse' => $warehouse, 'search' => $search, 'filter' => $filter, 'limit' => $limit + 100])) }}"
               class="inline-block px-6 py-2.5 bg-slate-100 text-slate-600 rounded-lg text-sm font-medium no-underline border border-slate-200 hover:bg-slate-200 transition-colors">
                Show more <span class="text-slate-400">(showing {{ $customers->count() }} of {{ $totalCount }})</span>
            </a>
        </div>
    @endif

    <div class="mt-3 text-xs text-slate-400 text-right">
        Showing {{ $customers->count() }} of {{ $totalCount }} customer{{ $totalCount !== 1 ? 's' : '' }}
        @if($warehouse) in {{ $warehouse }} @endif
        @if($filter) &bull; {{ $filter }} @endif
    </div>

</main>
<style>.crm-row:hover { background:#f8fafc; }</style>

{{-- Bulk Log Contact Modal --}}
<div id="bulk-modal" style="display:none;" class="fixed inset-0 z-[9999] items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/45" onclick="closeBulkModal()"></div>
    <div class="relative bg-white rounded-[14px] p-6 w-full max-w-[520px] shadow-2xl max-h-[90vh] overflow-y-auto">

        {{-- Step 1: Input --}}
        <div id="bulk-step-1">
            <h2 class="text-base font-bold text-slate-950 mb-1">Bulk Log Contact</h2>
            <p class="text-[0.8125rem] text-slate-500 mb-5">Paste account codes below — one per line, or comma-separated. A contact entry will be added for every matched account.</p>

            <div class="mb-3.5">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Account Codes <span class="text-red-600">*</span></label>
                <textarea id="bulk-codes" rows="8" placeholder="CUST001&#10;CUST002&#10;CUST003&#10;…"
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-[0.8125rem] font-mono resize-y focus:outline-none focus:border-blue-500"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3.5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Date <span class="text-red-600">*</span></label>
                    <input type="date" id="bulk-date" value="{{ date('Y-m-d') }}"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Note <span class="text-red-600">*</span></label>
                    <input type="text" id="bulk-note" placeholder="e.g. Eshot sent – Summer 2026" maxlength="2000"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div id="bulk-step1-error" style="display:none;" class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-[0.8125rem] text-red-600 mb-3.5"></div>

            <div class="flex gap-2">
                <button type="button" onclick="closeBulkModal()"
                    class="flex-1 py-2 border border-slate-200 bg-slate-50 text-slate-700 text-sm font-medium rounded-lg cursor-pointer hover:bg-slate-100 transition-colors">
                    Cancel
                </button>
                <button type="button" id="bulk-preview-btn" onclick="bulkPreview()"
                    class="flex-[2] py-2 bg-blue-700 text-white text-sm font-semibold rounded-lg border-0 cursor-pointer hover:bg-blue-800 transition-colors">
                    Preview
                </button>
            </div>
        </div>

        {{-- Step 2: Preview --}}
        <div id="bulk-step-2" style="display:none;">
            <h2 class="text-base font-bold text-slate-950 mb-4">Confirm Bulk Log</h2>

            <div id="bulk-matched-wrap" class="mb-4">
                <p id="bulk-match-summary" class="text-sm font-semibold text-slate-800 mb-2"></p>
                <div id="bulk-matched-list" class="max-h-40 overflow-y-auto bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-[0.8rem] leading-relaxed"></div>
            </div>

            <div id="bulk-notfound-wrap" style="display:none;" class="mb-4">
                <p class="text-[0.8125rem] font-semibold text-red-600 mb-2">Not found in system — will be skipped:</p>
                <div id="bulk-notfound-list" class="max-h-24 overflow-y-auto bg-red-50 border border-red-200 rounded-lg px-2.5 py-1.5 text-[0.8rem] font-mono text-red-800 leading-relaxed"></div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2.5 text-[0.8125rem] text-blue-700 mb-4">
                <span id="bulk-confirm-summary"></span>
            </div>

            <div id="bulk-step2-error" style="display:none;" class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-[0.8125rem] text-red-600 mb-3.5"></div>

            <div class="flex gap-2">
                <button type="button" onclick="bulkBack()"
                    class="flex-1 py-2 border border-slate-200 bg-slate-50 text-slate-700 text-sm font-medium rounded-lg cursor-pointer hover:bg-slate-100 transition-colors">
                    Back
                </button>
                <button type="button" id="bulk-confirm-btn" onclick="bulkConfirm()"
                    class="flex-[2] py-2 bg-green-600 text-white text-sm font-semibold rounded-lg border-0 cursor-pointer hover:bg-green-700 transition-colors">
                    Confirm &amp; Log
                </button>
            </div>
        </div>

        {{-- Step 3: Done --}}
        <div id="bulk-step-3" style="display:none;" class="text-center py-4">
            <div class="text-4xl mb-3">✓</div>
            <p id="bulk-done-msg" class="text-base font-semibold text-slate-800 mb-1"></p>
            <p class="text-[0.8125rem] text-slate-500 mb-6">Contact entries have been added.</p>
            <button type="button" onclick="closeBulkModal()"
                class="px-6 py-2 bg-slate-900 text-white text-sm font-semibold rounded-lg border-0 cursor-pointer hover:bg-slate-700 transition-colors">
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
