<x-layout title="Sales Figures — Lockie Portal">
    <nav class="bg-slate-900 shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <svg class="w-7 h-7 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <span class="text-white font-bold text-lg tracking-tight">Lockie Portal</span>
                </div>
                <div class="hidden sm:flex items-center gap-1">
                    <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-white text-sm px-3 py-1 rounded-lg transition-colors">Dashboard</a>
                    <span class="text-sky-400 font-medium text-sm px-3 py-1">Sales Figures</span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}" class="text-slate-400 hover:text-white text-sm transition-colors">Manage Users</a>
                @endif
                <span class="text-slate-500 text-sm hidden sm:block">{{ auth()->user()->email }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="text-slate-400 hover:text-white text-sm font-medium transition-colors">Sign out</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-10">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Sales Figures</h1>
            <p class="text-slate-500 mt-1">Sales orders, invoices and credit notes by warehouse.</p>
        </div>

        {{-- Date Range Form --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
            <div class="flex flex-wrap gap-2 mb-5">
                <span class="text-slate-500 text-sm self-center mr-1">Quick:</span>
                @foreach([
                    'this-week'    => 'This Week',
                    'this-month'   => 'This Month',
                    'last-month'   => 'Last Month',
                    'this-quarter' => 'This Quarter',
                    'this-year'    => 'This Year',
                ] as $key => $label)
                    <button type="button" onclick="setPreset('{{ $key }}')"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-900 hover:text-white hover:border-slate-900 transition-colors">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <form method="GET" action="{{ route('sales') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="from">From</label>
                    <input type="date" id="from" name="from" value="{{ $from }}"
                        class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="to">To</label>
                    <input type="date" id="to" name="to" value="{{ $to }}"
                        class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition text-sm">
                </div>
                <button type="submit" id="submit-btn"
                    class="px-6 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
                    View Report
                </button>
            </form>
        </div>

        @if($error)
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-6 py-4 mb-8 text-sm">
                <strong class="font-semibold">Unleashed API error:</strong> {{ $error }}
            </div>
        @else

        {{-- Totals --}}
        @php
            $totals = [];
            foreach (['sales' => $salesByWarehouse, 'credits' => $creditsByWarehouse, 'invoices' => $invoicesByWarehouse] as $key => $group) {
                $totals[$key] = ['count' => 0, 'sub' => 0.0, 'tax' => 0.0, 'total' => 0.0];
                foreach ($group as $d) {
                    $totals[$key]['count'] += $d['count'];
                    $totals[$key]['sub']   += $d['sub'];
                    $totals[$key]['tax']   += $d['tax'];
                    $totals[$key]['total'] += $d['total'];
                }
            }
        @endphp

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <p class="text-slate-500 text-sm font-medium">Sales Enquiry</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">£{{ number_format($totals['sales']['total'], 2) }}</p>
                <p class="text-slate-400 text-sm mt-1">{{ number_format($totals['sales']['count']) }} orders &middot; ex VAT £{{ number_format($totals['sales']['sub'], 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <p class="text-slate-500 text-sm font-medium">Credit Enquiry</p>
                <p class="text-2xl font-bold text-red-500 mt-1">£{{ number_format($totals['credits']['total'], 2) }}</p>
                <p class="text-slate-400 text-sm mt-1">{{ number_format($totals['credits']['count']) }} credits &middot; ex VAT £{{ number_format($totals['credits']['sub'], 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <p class="text-slate-500 text-sm font-medium">Invoice Enquiry</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">£{{ number_format($totals['invoices']['total'], 2) }}</p>
                <p class="text-slate-400 text-sm mt-1">{{ number_format($totals['invoices']['count']) }} invoices &middot; ex VAT £{{ number_format($totals['invoices']['sub'], 2) }}</p>
            </div>
        </div>

        {{-- Tables --}}
        @php
            $sections = [
                [
                    'title'  => 'Sales Enquiry by Warehouse',
                    'note'   => 'All non-cancelled orders by order date',
                    'color'  => 'bg-sky-500',
                    'data'   => $salesByWarehouse,
                    'totals' => $totals['sales'],
                ],
                [
                    'title'  => 'Credit Enquiry by Warehouse',
                    'note'   => 'All credit notes including free credits',
                    'color'  => 'bg-red-500',
                    'data'   => $creditsByWarehouse,
                    'totals' => $totals['credits'],
                ],
                [
                    'title'  => 'Invoice Enquiry by Warehouse',
                    'note'   => 'Completed invoices by invoice date',
                    'color'  => 'bg-emerald-500',
                    'data'   => $invoicesByWarehouse,
                    'totals' => $totals['invoices'],
                ],
            ];
        @endphp

        @foreach($sections as $section)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="w-2.5 h-2.5 rounded-full {{ $section['color'] }}"></div>
                <h2 class="font-semibold text-slate-800">{{ $section['title'] }}</h2>
                <span class="text-slate-400 text-xs ml-2">{!! $section['note'] !!}</span>
                <span class="text-slate-400 text-sm ml-auto">{{ $from }} — {{ $to }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wide">
                            <th class="px-6 py-3 text-left font-medium">Warehouse</th>
                            <th class="px-6 py-3 text-right font-medium">Count</th>
                            <th class="px-6 py-3 text-right font-medium">Sub-Total</th>
                            <th class="px-6 py-3 text-right font-medium">VAT</th>
                            <th class="px-6 py-3 text-right font-medium">Total inc VAT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($section['data'] as $warehouse => $d)
                        <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $warehouse }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">{{ number_format($d['count']) }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">£{{ number_format($d['sub'], 2) }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">£{{ number_format($d['tax'], 2) }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-800">£{{ number_format($d['total'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">No records found for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($section['data']))
                    <tfoot>
                        <tr class="bg-slate-50 border-t-2 border-slate-200 font-semibold text-slate-800">
                            <td class="px-6 py-4">Total</td>
                            <td class="px-6 py-4 text-right">{{ number_format($section['totals']['count']) }}</td>
                            <td class="px-6 py-4 text-right">£{{ number_format($section['totals']['sub'], 2) }}</td>
                            <td class="px-6 py-4 text-right">£{{ number_format($section['totals']['tax'], 2) }}</td>
                            <td class="px-6 py-4 text-right">£{{ number_format($section['totals']['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @endforeach

        @endif

    </main>

    <script>
        function setPreset(preset) {
            const today = new Date();
            let from, to = new Date();

            switch (preset) {
                case 'this-week': {
                    const day = today.getDay() || 7;
                    from = new Date(today);
                    from.setDate(today.getDate() - day + 1);
                    break;
                }
                case 'this-month':
                    from = new Date(today.getFullYear(), today.getMonth(), 1);
                    break;
                case 'last-month':
                    from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    to   = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
                case 'this-quarter': {
                    const q = Math.floor(today.getMonth() / 3);
                    from = new Date(today.getFullYear(), q * 3, 1);
                    break;
                }
                case 'this-year':
                    from = new Date(today.getFullYear(), 0, 1);
                    break;
            }

            document.getElementById('from').value = fmt(from);
            document.getElementById('to').value   = fmt(to);
        }

        function fmt(d) {
            return d.toISOString().split('T')[0];
        }

        document.querySelector('form').addEventListener('submit', () => {
            const btn = document.getElementById('submit-btn');
            btn.textContent = 'Loading…';
            btn.disabled = true;
        });
    </script>
</x-layout>
