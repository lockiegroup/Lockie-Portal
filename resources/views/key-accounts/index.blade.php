<x-layout title="Key Accounts — Lockie Portal">
<main class="max-w-screen-xl mx-auto px-6 py-10">

    <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Key Accounts</h1>
            <p class="text-slate-500 mt-1">{{ $isAdmin ? 'All accounts' : 'Your assigned accounts' }}</p>
            <form method="POST" action="{{ route('key-accounts.sales.filter') }}" class="flex items-center gap-2 mt-2 flex-wrap">
                @csrf
                <span class="text-xs text-slate-500 font-medium">Sales period:</span>
                <input type="date" name="sales_from" value="{{ $filterFrom }}"
                       class="border border-slate-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500">
                <span class="text-xs text-slate-400">to</span>
                <input type="date" name="sales_to" value="{{ $filterTo }}"
                       class="border border-slate-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500">
                <button type="submit"
                        class="px-3 py-1 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-700 transition">
                    Apply
                </button>
            </form>
            @if($salesFrom ?? false)
            <p class="text-xs text-slate-400 mt-1">Data covers: <span class="text-slate-500 font-medium">{{ $salesFrom }} – {{ $salesTo }}</span></p>
            @endif
        </div>
        <div class="flex gap-3 flex-wrap">
            @can('key_accounts_admin')
            <a href="{{ route('admin.key-accounts.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                Manage Accounts
            </a>
            @endcan
            <a href="{{ route('imports.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Import Sales
            </a>
            {{-- Gift export --}}
            <a href="{{ route('key-accounts.gifts.export') }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export Gifts
            </a>
            {{-- Gift import --}}
            <button onclick="document.getElementById('gift-import-modal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Import Gifts
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $errors->first() }}</div>
    @endif

    @php
        $currentQ  = (int) ceil(now()->month / 3);
        $histYears = array_values(array_filter($dataYears, fn($y) => $y !== $currentYear));
    @endphp

    @if($accounts->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-400">
        No accounts assigned to you yet.
    </div>
    @else

    {{-- Group by salesperson when admin --}}
    @php
        $groups = $isAdmin
            ? $accounts->groupBy(fn($a) => $a->user?->name ?? 'Unassigned')
            : collect(['Your Accounts' => $accounts]);
    @endphp

    @foreach($groups as $personName => $personAccounts)
    @if($isAdmin)
    <h2 class="text-base font-semibold text-slate-700 mb-3 mt-6">{{ $personName }}</h2>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Account</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Type</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Last Contact</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Last Gift</th>
                        @foreach($histYears as $hy)
                        <th class="text-right px-4 py-3 font-semibold text-slate-600 whitespace-nowrap cursor-pointer select-none hover:text-slate-900" onclick="toggleYear({{ $hy }})">
                            {{ $hy }}&nbsp;<span class="year-chevron-{{ $hy }} text-slate-400 text-xs font-normal">▸</span>
                        </th>
                        <th class="text-right px-2 py-3 font-medium text-slate-400 text-xs whitespace-nowrap hidden" data-year-expand="{{ $hy }}">Q1</th>
                        <th class="text-right px-2 py-3 font-medium text-slate-400 text-xs whitespace-nowrap hidden" data-year-expand="{{ $hy }}">Q2</th>
                        <th class="text-right px-2 py-3 font-medium text-slate-400 text-xs whitespace-nowrap hidden" data-year-expand="{{ $hy }}">Q3</th>
                        <th class="text-right px-2 py-3 font-medium text-slate-400 text-xs whitespace-nowrap hidden" data-year-expand="{{ $hy }}">Q4</th>
                        @endforeach
                        <th class="text-right px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Q1</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Q2</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Q3</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Q4</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">{{ $currentYear }} Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($personAccounts as $account)
                    @php
                        $curr        = $salesByYear[$currentYear][$account->account_code] ?? ['total' => 0, 'q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
                        $lastContact = $account->contacts->first()?->contacted_at;
                        $lastGift    = $account->gifts->first()?->gifted_at;
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('key-accounts.show', $account) }}" class="font-semibold text-sky-700 hover:underline">{{ $account->account_code }}</a>
                            <div class="text-xs text-slate-500">{{ $account->name }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $account->type === 'key' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $account->type === 'key' ? 'Key' : 'Growth' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                            {{ $lastContact ? $lastContact->format('d M Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                            {{ $lastGift ? $lastGift->format('d M Y') : '—' }}
                        </td>
                        @foreach($histYears as $hy)
                        @php $hdata = $salesByYear[$hy][$account->account_code] ?? ['total' => 0, 'q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0]; @endphp
                        <td class="px-4 py-3 text-right text-slate-600 whitespace-nowrap">
                            {{ $hdata['total'] > 0 ? '£' . number_format($hdata['total'], 2) : '—' }}
                        </td>
                        @foreach(['q1','q2','q3','q4'] as $hq)
                        <td class="px-2 py-3 text-right text-slate-400 text-xs whitespace-nowrap hidden" data-year-expand="{{ $hy }}">
                            {{ $hdata[$hq] > 0 ? '£' . number_format($hdata[$hq], 2) : '—' }}
                        </td>
                        @endforeach
                        @endforeach
                        @foreach(['q1','q2','q3','q4'] as $qi => $q)
                        @php
                            $qNum = $qi + 1;
                            $val  = $curr[$q];
                            $isCurrent = $qNum === $currentQ;
                            $isPast    = $qNum < $currentQ;
                        @endphp
                        <td class="px-4 py-3 text-right whitespace-nowrap {{ $isCurrent ? 'bg-sky-50 font-semibold text-sky-700' : ($isPast && $val == 0 ? 'text-red-500' : 'text-slate-600') }}">
                            {{ $val > 0 ? '£' . number_format($val, 2) : ($isCurrent || !$isPast ? '—' : '£0') }}
                        </td>
                        @endforeach
                        <td class="px-4 py-3 text-right font-semibold text-slate-800 whitespace-nowrap {{ $curr['total'] > 0 ? 'bg-emerald-50 text-emerald-700' : '' }}">
                            {{ $curr['total'] > 0 ? '£' . number_format($curr['total'], 2) : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
    @endif

</main>

<script>
function toggleYear(year) {
    document.querySelectorAll('[data-year-expand="' + year + '"]').forEach(el => el.classList.toggle('hidden'));
    document.querySelectorAll('.year-chevron-' + year).forEach(el => {
        el.textContent = el.textContent.trim() === '▸' ? '▾' : '▸';
    });
}
</script>

{{-- Gift import modal --}}
<div id="gift-import-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-base font-semibold text-slate-800 mb-1">Import Gifts from Excel</h3>
        <p class="text-sm text-slate-500 mb-4">Columns: <strong>Account Code, Recipient, Date, Gift Description</strong> (header row required)</p>
        <form action="{{ route('key-accounts.gifts.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls" required
                class="block w-full text-sm text-slate-600 mb-4 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-900 file:text-white file:font-medium file:cursor-pointer">
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('gift-import-modal').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700">Import</button>
            </div>
        </form>
    </div>
</div>
</x-layout>
