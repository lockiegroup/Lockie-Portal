<x-layout title="Print Archive — Lockie Portal">

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

        <div class="mb-6">
            <a href="{{ route('print.index') }}" class="text-sm text-slate-400 hover:text-slate-600 transition-colors">&#8592; Print Schedule</a>
        </div>

        <div class="flex items-end justify-between flex-wrap gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Print Archive</h1>
                <p class="text-sm text-slate-500 mt-1">Search completed jobs for historical print data and order details.</p>
            </div>
            <span class="text-xs text-slate-400">{{ number_format($jobs->total()) }} completed job{{ $jobs->total() !== 1 ? 's' : '' }}</span>
        </div>

        {{-- Search + filters --}}
        <form method="GET" action="{{ route('print.archive') }}" class="mb-6">
            <div class="relative mb-2.5">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" name="q" value="{{ $search }}"
                    placeholder="Search order numbers, customer names, refs, product codes, print data…"
                    autocomplete="off"
                    class="w-full pl-10 pr-32 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 bg-white outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-100">
                <div class="absolute right-2 top-1/2 -translate-y-1/2 flex gap-1.5">
                    @if($search || $dateFrom || $dateTo || $machine)
                        <a href="{{ route('print.archive') }}"
                            class="text-xs text-slate-500 px-2.5 py-1 rounded-md border border-slate-200 bg-slate-50 no-underline whitespace-nowrap">
                            Clear
                        </a>
                    @endif
                    <button type="submit"
                        class="bg-slate-900 text-white text-xs px-3.5 py-1 rounded-md border-0 cursor-pointer whitespace-nowrap">
                        Search
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-xs text-slate-400 whitespace-nowrap">Archive date:</span>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                    class="text-sm border border-slate-200 rounded-lg px-2.5 py-1 text-slate-700 bg-white outline-none focus:border-rose-500"
                    title="Archived from">
                <span class="text-xs text-slate-400">→</span>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                    class="text-sm border border-slate-200 rounded-lg px-2.5 py-1 text-slate-700 bg-white outline-none focus:border-rose-500"
                    title="Archived to">
                <span class="text-xs text-slate-400 whitespace-nowrap ml-1">Machine:</span>
                <select name="machine"
                    class="text-sm border border-slate-200 rounded-lg px-2.5 py-1 text-slate-700 bg-white outline-none">
                    <option value="">All machines</option>
                    @foreach($machines as $m)
                        <option value="{{ $m }}" {{ $machine === $m ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $m)) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        @if($search || $dateFrom || $dateTo || $machine)
            <p class="text-sm text-slate-500 mb-4">
                {{ $jobs->total() }} result{{ $jobs->total() !== 1 ? 's' : '' }}
                @if($search) for <span class="font-medium text-slate-700">&ldquo;{{ $search }}&rdquo;</span>@endif
                @if($dateFrom || $dateTo)
                    <span class="text-slate-400">archived
                    @if($dateFrom) from <span class="font-medium text-slate-600">{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</span>@endif
                    @if($dateTo) to <span class="font-medium text-slate-600">{{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</span>@endif
                    </span>
                @endif
                @if($machine)
                    · machine: <span class="font-medium text-slate-700">{{ ucwords(str_replace('_', ' ', $machine)) }}</span>
                @endif
            </p>
        @endif

        @if($jobs->isEmpty())
            <div class="text-center py-16 text-slate-400">
                <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <p class="text-sm font-medium">{{ $search ? 'No results found' : 'No archived jobs yet' }}</p>
                @if($search)
                    <p class="text-xs mt-1">Try different keywords</p>
                @endif
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-[0.8125rem] border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-500 whitespace-nowrap">Order</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-500 whitespace-nowrap">Customer</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-500 whitespace-nowrap">Product</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-500">Print Data</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-500 whitespace-nowrap">Delivery</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-slate-500 whitespace-nowrap">Packs</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-500 whitespace-nowrap">Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                                <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-2.5 align-top whitespace-nowrap">
                                        <span class="font-mono text-xs text-slate-500">{{ $job->order_number }}</span>
                                        @if($job->board && $job->board !== 'unplanned')
                                            <br><span class="text-[0.7rem] text-slate-400">{{ \App\Models\PrintJob::BOARDS[$job->board] ?? $job->board }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 align-top">
                                        <span class="text-slate-800 font-medium">{{ $job->customer_name }}</span>
                                        @if($job->customer_ref)
                                            <br><span class="font-mono text-xs text-slate-400">{{ $job->customer_ref }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 align-top whitespace-nowrap">
                                        @if($job->product_code)
                                            <span class="font-mono text-xs text-slate-600 font-medium">{{ $job->product_code }}</span>
                                        @endif
                                        @if($job->product_description)
                                            <br><span class="text-xs text-slate-400">{{ $job->product_description }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 align-top max-w-xs">
                                        @if($job->line_comment)
                                            <span class="font-mono text-xs text-blue-800 whitespace-pre-wrap break-words">{{ $job->line_comment }}</span>
                                        @else
                                            <span class="text-xs text-slate-300 italic">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 align-top whitespace-nowrap">
                                        @if($job->delivery_name || $job->delivery_address)
                                            <details class="cursor-pointer">
                                                <summary class="text-xs text-slate-500 list-none flex items-center gap-1">
                                                    <svg class="w-3 h-3 flex-shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                                    {{ $job->delivery_name ?? $job->delivery_city }}
                                                </summary>
                                                @if($job->delivery_address)
                                                    <p class="text-[0.7rem] text-slate-500 mt-1 ml-4 whitespace-normal max-w-[200px] leading-snug">{{ $job->delivery_address }}</p>
                                                @endif
                                            </details>
                                        @else
                                            <span class="text-xs text-slate-300 italic">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right align-top whitespace-nowrap">
                                        <span class="text-slate-700 font-medium">{{ number_format($job->order_quantity) }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-400 whitespace-nowrap align-top">
                                        @if($job->archive_reason === 'deleted')
                                            <span class="text-[0.65rem] bg-red-50 text-red-600 border border-red-200 rounded px-1.5 py-0.5 whitespace-nowrap">Deleted from Unleashed</span>
                                        @else
                                            {{ $job->archived_at->format('d M Y') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            @if($jobs->hasPages())
                <div class="mt-6 flex justify-center gap-1.5 flex-wrap">
                    @if($jobs->onFirstPage())
                        <span class="px-3 py-1.5 rounded-md border border-slate-200 text-slate-300 text-sm">&#8592; Prev</span>
                    @else
                        <a href="{{ $jobs->previousPageUrl() }}"
                            class="px-3 py-1.5 rounded-md border border-slate-200 text-slate-500 text-sm no-underline bg-white hover:bg-slate-50">&#8592; Prev</a>
                    @endif
                    <span class="px-3 py-1.5 text-sm text-slate-500">
                        Page {{ $jobs->currentPage() }} of {{ $jobs->lastPage() }}
                    </span>
                    @if($jobs->hasMorePages())
                        <a href="{{ $jobs->nextPageUrl() }}"
                            class="px-3 py-1.5 rounded-md border border-slate-200 text-slate-500 text-sm no-underline bg-white hover:bg-slate-50">Next &#8594;</a>
                    @else
                        <span class="px-3 py-1.5 rounded-md border border-slate-200 text-slate-300 text-sm">Next &#8594;</span>
                    @endif
                </div>
            @endif
        @endif

    </main>

</x-layout>
