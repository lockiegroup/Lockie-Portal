<x-layout title="{{ $customer }} — CRM — Lockie Portal">
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    {{-- Back --}}
    <div class="mb-6">
        <a href="{{ route('crm.index', ['warehouse' => $warehouse]) }}"
           class="text-sm text-slate-400 no-underline">&#8592; Customer Insights</a>
    </div>

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $customer ?: $customerCode }}</h1>
            <p class="text-sm text-slate-400 mt-0.5">
                {{ $customerCode }}
                @if($keyAccount)
                    &bull; <a href="{{ route('key-accounts.index') }}" class="text-indigo-500 no-underline">View Key Accounts &rarr;</a>
                @endif
            </p>
            @if($keyAccount && $keyAccount->user_id)
            <div class="flex items-center gap-2.5 mt-2.5">
                <span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:0.8125rem;font-weight:600;
                             background:{{ $keyAccount->type === 'key' ? '#e0f2fe' : '#d1fae5' }};
                             color:{{ $keyAccount->type === 'key' ? '#0369a1' : '#059669' }};">
                    {{ $keyAccount->type === 'key' ? 'Key Account' : 'Growth Account' }}
                </span>
                <span class="text-sm text-slate-500">
                    Account manager: <strong class="text-slate-700">{{ $keyAccount->user->name }}</strong>
                </span>
            </div>
            @endif
        </div>

        {{-- Warehouse filter --}}
        @if($warehouses->count() > 1)
        <form method="GET" class="flex gap-2 items-center">
            <select name="warehouse" onchange="this.form.submit()"
                class="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $w)
                    <option value="{{ $w }}" {{ $warehouse === $w ? 'selected' : '' }}>{{ $w }}</option>
                @endforeach
            </select>
        </form>
        @endif
    </div>

    {{-- KPI strip --}}
    @php
        $pct = $totalPrev12 > 0 ? (($total12m - $totalPrev12) / $totalPrev12) * 100 : null;
        $pctColour = ($pct !== null && $pct >= 5) ? '#16a34a' : (($pct !== null && $pct <= -5) ? '#dc2626' : '#64748b');
        $lastOrderDate = $lastOrder ? \Carbon\Carbon::parse($lastOrder) : null;
        $daysSince = $lastOrderDate ? $lastOrderDate->diffInDays(now()) : null;
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-10">

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Last 12 Months</p>
            <p class="text-[1.625rem] font-bold text-slate-800 leading-none">£{{ number_format($total12m, 0) }}</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Prev 12 Months</p>
            <p class="text-[1.625rem] font-bold text-slate-500 leading-none">
                {{ $totalPrev12 > 0 ? '£' . number_format($totalPrev12, 0) : '—' }}
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Year-on-Year</p>
            <p style="font-size:1.625rem;font-weight:700;color:{{ $pctColour }};line-height:1;">
                @if($pct !== null)
                    {{ $pct > 0 ? '+' : '' }}{{ number_format($pct, 1) }}%
                @else
                    —
                @endif
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Last Order</p>
            @if($lastOrderDate)
                <p style="font-size:1rem;font-weight:600;color:{{ $daysSince > 180 ? '#dc2626' : ($daysSince > 90 ? '#d97706' : '#1e293b') }};line-height:1.3;">
                    {{ $lastOrderDate->format('d M Y') }}
                </p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $lastOrderDate->diffForHumans() }}</p>
            @else
                <p class="text-base text-slate-400">—</p>
            @endif
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            @php
                $nextDaysUntil = $expectedNext ? now()->diffInDays($expectedNext, false) : null;
                $nextColour = ($nextDaysUntil !== null && $nextDaysUntil < 0) ? '#dc2626'
                    : (($nextDaysUntil !== null && $nextDaysUntil <= 14) ? '#d97706' : '#1e293b');
            @endphp
            <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Expected Next Order</p>
            @if($expectedNext)
                <p style="font-size:1rem;font-weight:600;color:{{ $nextColour }};line-height:1.3;">
                    {{ $expectedNext->format('d M Y') }}
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                    @if($nextDaysUntil < 0)
                        {{ abs((int) $nextDaysUntil) }}d overdue
                    @else
                        in {{ (int) $nextDaysUntil }}d
                    @endif
                    &bull; orders every ~{{ $avgDays }}d
                </p>
            @else
                <p class="text-sm text-slate-400">Not enough data</p>
            @endif
        </div>

    </div>

    {{-- Yearly breakdown --}}
    @php
        $byYearNet   = $byYear;
        $hasCredits  = collect($byYearGross)->contains(fn($g, $yr) =>
            isset($byYearNet[$yr]) && round($g['total'], 2) !== round($byYearNet[$yr]['total'], 2)
        );
    @endphp
    <div class="flex items-center justify-between mb-3.5 flex-wrap gap-2">
        <h2 class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Annual Spend</h2>
        @if($hasCredits)
        <div class="flex bg-slate-100 rounded-full p-0.5 gap-0.5 text-xs font-semibold">
            <button id="spend-net-btn" onclick="setSpendView('net')"
                class="px-3 py-1 border-0 rounded-full cursor-pointer bg-slate-900 text-white">Credits included</button>
            <button id="spend-gross-btn" onclick="setSpendView('gross')"
                class="px-3 py-1 border-0 rounded-full cursor-pointer bg-transparent text-slate-500">Credits excluded</button>
        </div>
        @endif
    </div>
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-10">
        <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse" style="min-width:420px;">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-4 py-2.5 text-left text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Year</th>
                    <th class="px-4 py-2.5 text-right text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Q1</th>
                    <th class="px-4 py-2.5 text-right text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Q2</th>
                    <th class="px-4 py-2.5 text-right text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Q3</th>
                    <th class="px-4 py-2.5 text-right text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Q4</th>
                    <th class="px-4 py-2.5 text-right text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Total</th>
                </tr>
            </thead>
            <tbody id="spend-tbody">
                @foreach($byYear as $year => $qs)
                    <tr class="border-b border-slate-50">
                        <td class="px-4 py-2.5 font-semibold text-slate-800">{{ $year }}</td>
                        @foreach(['q1','q2','q3','q4'] as $q)
                            <td style="padding:10px 16px;text-align:right;color:{{ isset($qs[$q]) && $qs[$q] > 0 ? '#334155' : '#cbd5e1' }};">
                                {{ isset($qs[$q]) && $qs[$q] > 0 ? '£' . number_format($qs[$q], 0) : '—' }}
                            </td>
                        @endforeach
                        <td class="px-4 py-2.5 text-right font-semibold text-slate-800">
                            £{{ number_format($qs['total'] ?? 0, 0) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @if($hasCredits)
    <script>
        var spendData = {
            net:   @json($byYear),
            gross: @json($byYearGross)
        };
        function fmt(v) { return v > 0 ? '£' + Math.round(v).toLocaleString('en-GB') : '—'; }
        function fmtColour(v) { return v > 0 ? '#334155' : '#cbd5e1'; }
        function setSpendView(mode) {
            var data = spendData[mode];
            var rows = '';
            Object.keys(data).sort(function(a,b){return b-a;}).forEach(function(yr) {
                var qs = data[yr];
                rows += '<tr class="border-b border-slate-50">';
                rows += '<td class="px-4 py-2.5 font-semibold text-slate-800">' + yr + '</td>';
                ['q1','q2','q3','q4'].forEach(function(q) {
                    var v = qs[q] || 0;
                    rows += '<td style="padding:10px 16px;text-align:right;color:' + fmtColour(v) + ';">' + fmt(v) + '</td>';
                });
                var t = qs['total'] || 0;
                rows += '<td class="px-4 py-2.5 text-right font-semibold text-slate-800">£' + Math.round(t).toLocaleString('en-GB') + '</td>';
                rows += '</tr>';
            });
            document.getElementById('spend-tbody').innerHTML = rows;
            document.getElementById('spend-net-btn').style.background   = mode === 'net'   ? '#1e293b' : 'transparent';
            document.getElementById('spend-net-btn').style.color        = mode === 'net'   ? '#fff'    : '#64748b';
            document.getElementById('spend-gross-btn').style.background = mode === 'gross' ? '#1e293b' : 'transparent';
            document.getElementById('spend-gross-btn').style.color      = mode === 'gross' ? '#fff'    : '#64748b';
        }
    </script>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-10">

        {{-- Top products --}}
        <div>
            <h2 class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-3.5">Top Products</h2>
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                <table class="w-full text-[0.8125rem] border-collapse">
                    <tbody>
                        @foreach($topProducts as $p)
                            <tr class="border-b border-slate-50">
                                <td class="px-3.5 py-2.5">
                                    <p class="font-semibold text-slate-800">{{ $p['product_code'] }}</p>
                                </td>
                                <td class="px-3.5 py-2.5 text-right font-semibold text-slate-700">
                                    £{{ number_format($p['total'], 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent orders --}}
        <div>
            <h2 class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide mb-3.5">Recent Orders</h2>
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                <table class="w-full text-[0.8125rem] border-collapse">
                    <tbody>
                        @foreach($recentOrders as $o)
                            <tr class="border-b border-slate-50">
                                <td class="px-3.5 py-2.5">
                                    <p class="font-semibold text-slate-800">{{ $o['order_no'] ?: '—' }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ \Carbon\Carbon::parse($o['date'])->format('d M Y') }}
                                        @if($o['warehouse']) &bull; {{ $o['warehouse'] }} @endif
                                    </p>
                                </td>
                                <td class="px-3.5 py-2.5 text-right font-semibold text-slate-700">
                                    £{{ number_format($o['total'], 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Key / Growth Account sections --}}
    @php
        // Always use CRM-owned routes so notes/contacts work regardless of key_accounts module access
        $notesAction   = route('crm.notes.update', $customerCode);
        $contactAction = route('crm.contacts.store', $customerCode);
    @endphp

    @if(session('crm_success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-3.5 py-2.5 mb-5">
            {{ session('crm_success') }}
        </div>
    @endif

        {{-- Notes --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 mb-5 shadow-sm">
            <h2 class="text-[0.9375rem] font-bold text-slate-800 mb-3.5">Notes</h2>
            <form action="{{ $notesAction }}" method="POST">
                @csrf @method('PATCH')
                <textarea name="notes" rows="3" placeholder="Any notes about this account…"
                    class="w-full px-3.5 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 resize-none overflow-y-hidden focus:outline-none focus:ring-2 focus:ring-sky-500"
                    oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'">{{ old('notes', $keyAccount?->notes) }}</textarea>
                <div class="mt-2 flex justify-end">
                    <button type="submit"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg border-0 cursor-pointer hover:bg-slate-700 transition-colors">
                        Save Notes
                    </button>
                </div>
            </form>
        </div>

        {{-- Log contact --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 mb-5 shadow-sm">
            <h2 class="text-[0.9375rem] font-bold text-slate-800 mb-3.5">Log Contact</h2>
            <form action="{{ $contactAction }}" method="POST">
                @csrf
                @if($errors->has('contacted_at') || $errors->has('note'))
                    <div class="bg-red-50 border border-red-200 text-red-600 text-[0.8125rem] rounded-lg px-3 py-2 mb-3">
                        {{ $errors->first() }}
                    </div>
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-[180px_1fr] gap-3 items-start">
                    <div>
                        <label class="block text-[0.8125rem] font-medium text-slate-500 mb-1.5">Date</label>
                        <input type="date" name="contacted_at" value="{{ old('contacted_at', now()->format('Y-m-d')) }}" required
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-[0.8125rem] font-medium text-slate-500 mb-1.5">Note</label>
                        <textarea name="note" rows="2" required placeholder="What was discussed or actioned…"
                            oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm resize-none overflow-y-hidden focus:outline-none focus:ring-2 focus:ring-sky-500">{{ old('note') }}</textarea>
                    </div>
                </div>
                <div class="mt-2.5 flex justify-end">
                    <button type="submit"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg border-0 cursor-pointer hover:bg-slate-700 transition-colors">
                        Log Contact
                    </button>
                </div>
            </form>
        </div>

        {{-- Contact history --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 mb-5 shadow-sm">
            <h2 class="text-[0.9375rem] font-bold text-slate-800 mb-3.5">Contact History</h2>
            @if(!$keyAccount || $keyAccount->contacts->isEmpty())
                <p class="text-sm text-slate-400">No contacts logged yet.</p>
            @else
                <div class="flex flex-col gap-0">
                    @foreach($keyAccount->contacts as $contact)
                        <div class="flex items-start gap-4 py-2.5 border-b border-slate-50">
                            <span class="text-xs text-slate-400 whitespace-nowrap pt-0.5 w-20 shrink-0">
                                {{ $contact->contacted_at->format('d M Y') }}
                            </span>
                            <span class="text-sm text-slate-700 flex-1 whitespace-pre-wrap">{!! e($contact->note) !!}</span>
                            <span class="text-xs text-slate-400 whitespace-nowrap shrink-0">{{ $contact->user?->name ?? '—' }}</span>
                            <form action="{{ route('crm.contacts.destroy', [$customerCode, $contact]) }}" method="POST"
                                onsubmit="return confirm('Remove this contact entry?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="bg-transparent border-0 text-slate-300 text-lg cursor-pointer leading-none p-0 hover:text-red-500 transition-colors">&times;</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Gift history (only exists if there's a Key Account record with gifts) --}}
        @if($keyAccount && $keyAccount->gifts->isNotEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-6 mb-5 shadow-sm">
            <h2 class="text-[0.9375rem] font-bold text-slate-800 mb-3.5">Gift History</h2>
            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="text-left pb-2 text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Date</th>
                        <th class="text-left pb-2 pl-4 text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Recipient</th>
                        <th class="text-left pb-2 pl-4 text-[0.7rem] font-bold text-slate-400 uppercase tracking-wide">Gift</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($keyAccount->gifts as $gift)
                        <tr class="border-b border-slate-50">
                            <td class="py-2 text-slate-500 whitespace-nowrap">{{ $gift->gifted_at->format('d M Y') }}</td>
                            <td class="py-2 pl-4 text-slate-700">{{ $gift->recipient }}</td>
                            <td class="py-2 pl-4 text-slate-700">{{ $gift->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif

</main>
<script>
    document.querySelectorAll('textarea[name="notes"]').forEach(t => {
        t.style.height = 'auto';
        t.style.height = t.scrollHeight + 'px';
    });
</script>
</x-layout>
