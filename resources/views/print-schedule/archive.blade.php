<x-layout title="Print Archive — Lockie Portal">

    <nav class="bg-slate-900 shadow-lg">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" class="h-12 w-auto">
            </a>
            <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-white text-sm transition-colors">&#8592; Dashboard</a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

        <div style="margin-bottom:1.5rem;">
            <a href="{{ route('print.index') }}" class="text-sm text-slate-400 hover:text-slate-600 transition-colors">&#8592; Print Schedule</a>
        </div>

        <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:1.5rem;">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Print Archive</h1>
                <p class="text-sm text-slate-500 mt-1">Search completed jobs for historical print data and order details.</p>
            </div>
            <span class="text-xs text-slate-400">{{ number_format($jobs->total()) }} completed job{{ $jobs->total() !== 1 ? 's' : '' }}</span>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('print.archive') }}" style="margin-bottom:1.5rem;position:relative;">
            <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:#94a3b8;pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" name="q" value="{{ $search }}"
                placeholder="Search order numbers, customer names, refs, product codes, print data…"
                autocomplete="off"
                style="width:100%;padding:10px 120px 10px 40px;border:1px solid #e2e8f0;border-radius:0.75rem;font-size:0.875rem;color:#1e293b;background:#fff;outline:none;box-sizing:border-box;"
                onfocus="this.style.borderColor='#e11d48';this.style.boxShadow='0 0 0 3px rgba(225,29,72,0.1)'"
                onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
            <div style="position:absolute;right:8px;top:50%;transform:translateY(-50%);display:flex;gap:6px;">
                @if($search)
                    <a href="{{ route('print.archive') }}"
                        style="font-size:0.75rem;color:#64748b;padding:5px 10px;border-radius:6px;border:1px solid #e2e8f0;background:#f8fafc;text-decoration:none;white-space:nowrap;">
                        Clear
                    </a>
                @endif
                <button type="submit"
                    style="background:#1e293b;color:#fff;font-size:0.75rem;padding:5px 14px;border-radius:6px;border:none;cursor:pointer;white-space:nowrap;">
                    Search
                </button>
            </div>
        </form>

        @if($search)
            <p class="text-sm text-slate-500 mb-4">
                {{ $jobs->total() }} result{{ $jobs->total() !== 1 ? 's' : '' }} for <span class="font-medium text-slate-700">&ldquo;{{ $search }}&rdquo;</span>
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
                    <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                        <thead>
                            <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;white-space:nowrap;">Completed</th>
                                <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;white-space:nowrap;">Order</th>
                                <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;white-space:nowrap;">Customer</th>
                                <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;white-space:nowrap;">Product</th>
                                <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;">Print Data</th>
                                <th style="padding:10px 16px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Packs</th>
                                <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;white-space:nowrap;">Despatched</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                                <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                                    <td style="padding:10px 16px;color:#94a3b8;white-space:nowrap;vertical-align:top;">
                                        {{ $job->archived_at->format('d M Y') }}
                                        @if($job->archive_reason === 'deleted')
                                            <br><span style="font-size:0.65rem;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:4px;padding:1px 5px;white-space:nowrap;">Deleted from Unleashed</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 16px;vertical-align:top;white-space:nowrap;">
                                        <span class="font-mono text-xs text-slate-500">{{ $job->order_number }}</span>
                                        @if($job->board && $job->board !== 'unplanned')
                                            <br><span style="font-size:0.7rem;color:#94a3b8;">{{ \App\Models\PrintJob::BOARDS[$job->board] ?? $job->board }}</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 16px;vertical-align:top;">
                                        <span class="text-slate-800 font-medium">{{ $job->customer_name }}</span>
                                        @if($job->customer_ref)
                                            <br><span class="font-mono text-xs text-slate-400">{{ $job->customer_ref }}</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 16px;vertical-align:top;white-space:nowrap;">
                                        @if($job->product_code)
                                            <span class="font-mono text-xs text-slate-600 font-medium">{{ $job->product_code }}</span>
                                        @endif
                                        @if($job->product_description)
                                            <br><span class="text-xs text-slate-400">{{ $job->product_description }}</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 16px;vertical-align:top;max-width:320px;">
                                        @if($job->line_comment)
                                            <span class="font-mono text-xs text-blue-800" style="white-space:pre-wrap;word-break:break-word;">{{ $job->line_comment }}</span>
                                        @else
                                            <span class="text-xs text-slate-300 italic">—</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 16px;text-align:right;vertical-align:top;white-space:nowrap;">
                                        <span class="text-slate-700 font-medium">{{ number_format($job->order_quantity) }}</span>
                                    </td>
                                    <td style="padding:10px 16px;vertical-align:top;white-space:nowrap;">
                                        @if($job->despatched_at)
                                            <span class="text-xs text-slate-600">{{ $job->despatched_at->format('d M Y') }}</span>
                                        @elseif($job->required_date)
                                            <span class="text-xs {{ $job->date_changed ? 'text-amber-600 font-medium' : 'text-slate-400 italic' }}">
                                                {{ $job->required_date->format('d M Y') }}
                                                <span style="font-size:0.65rem;color:#94a3b8;">(planned)</span>
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-300 italic">—</span>
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
                <div style="margin-top:1.5rem;display:flex;justify-content:center;gap:6px;flex-wrap:wrap;">
                    @if($jobs->onFirstPage())
                        <span style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#cbd5e1;font-size:0.8125rem;">&#8592; Prev</span>
                    @else
                        <a href="{{ $jobs->previousPageUrl() }}"
                            style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#475569;font-size:0.8125rem;text-decoration:none;background:#fff;">&#8592; Prev</a>
                    @endif

                    <span style="padding:6px 12px;font-size:0.8125rem;color:#64748b;">
                        Page {{ $jobs->currentPage() }} of {{ $jobs->lastPage() }}
                    </span>

                    @if($jobs->hasMorePages())
                        <a href="{{ $jobs->nextPageUrl() }}"
                            style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#475569;font-size:0.8125rem;text-decoration:none;background:#fff;">Next &#8594;</a>
                    @else
                        <span style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#cbd5e1;font-size:0.8125rem;">Next &#8594;</span>
                    @endif
                </div>
            @endif
        @endif

    </main>

</x-layout>
