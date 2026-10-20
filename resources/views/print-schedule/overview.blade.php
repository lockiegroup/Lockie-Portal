<x-layout title="A1 Schedule Overview — Lockie Portal">

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        <div class="mb-6">
            <a href="{{ route('print.index') }}" class="text-sm text-slate-400 hover:text-slate-600 transition-colors">&#8592; Print Schedule</a>
        </div>

        <div class="flex items-end justify-between flex-wrap gap-3 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">A1 Schedule Overview</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Live machine workload and lead times.
                    @if($lastSync)
                        <span class="text-slate-400">&bull; Synced {{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}</span>
                    @endif
                </p>
            </div>
            @can('admin')
                <a href="{{ route('admin.print-settings.index') }}"
                    class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-500 text-xs px-2.5 py-1.5 rounded-md border border-slate-200 no-underline whitespace-nowrap">
                    <svg class="w-3 h-3 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Edit Notes
                </a>
            @endcan
        </div>

        <h2 class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-widest mb-3.5">Machine Workload</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
            @foreach($machineStats as $key => $stat)
                @php
                    $hasJobs = $stat['job_count'] > 0;
                    $hasLate = $stat['late_count'] > 0;
                    $leadDays = $stat['lead_days'];
                    if (!$hasJobs || $leadDays <= 5) {
                        $leadCls = 'text-green-600'; $alertCls = 'bg-green-50 border-green-200 text-green-600';
                    } elseif ($leadDays <= 10) {
                        $leadCls = 'text-amber-600'; $alertCls = 'bg-amber-50 border-amber-200 text-amber-700';
                    } else {
                        $leadCls = 'text-red-600'; $alertCls = 'bg-red-50 border-red-200 text-red-600';
                    }
                @endphp
                <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 shadow-sm">

                    <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-widest mb-2.5">{{ $stat['label'] }}</p>

                    <div class="flex items-baseline gap-1.5 mb-3.5">
                        <span class="text-4xl font-bold leading-none {{ $leadCls }}">
                            {{ $hasJobs ? number_format($leadDays, 1) : '0' }}
                        </span>
                        <span class="text-[0.8125rem] text-slate-400 font-medium">days</span>
                    </div>

                    <div class="flex flex-col gap-1 text-sm">
                        <div class="flex justify-between text-slate-500">
                            <span>Jobs in queue</span>
                            <span class="font-semibold text-slate-800">{{ $stat['job_count'] }}</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>Packs remaining</span>
                            <span class="font-semibold text-slate-800">{{ number_format($stat['remaining']) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>Throughput</span>
                            <span class="font-medium text-slate-400">{{ number_format($stat['throughput']) }} / day</span>
                        </div>
                    </div>

                    @if($hasLate)
                        <div class="mt-3.5 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1.5 flex items-center gap-1.5">
                            <svg class="w-3 h-3 text-red-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <span class="text-xs text-red-600 font-medium">
                                {{ $stat['late_count'] }} job{{ $stat['late_count'] !== 1 ? 's' : '' }} estimated late
                            </span>
                        </div>
                    @elseif($hasJobs)
                        <div class="mt-3.5 bg-green-50 border border-green-200 rounded-lg px-2.5 py-1.5 flex items-center gap-1.5">
                            <svg class="w-3 h-3 text-green-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span class="text-xs text-green-600 font-medium">All jobs on track</span>
                        </div>
                    @else
                        <div class="mt-3.5 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5">
                            <span class="text-xs text-slate-400">No jobs queued</span>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>

        <h2 class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-widest mb-3.5">Notes &amp; Updates</h2>

        @if($dashboardNotes && trim($dashboardNotes) !== '')
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <p class="text-[0.9375rem] text-slate-700 leading-relaxed whitespace-pre-wrap break-words">{{ $dashboardNotes }}</p>
            </div>
        @else
            <div class="bg-slate-50 border border-dashed border-slate-200 rounded-xl p-8 text-center">
                <p class="text-sm text-slate-400">No notes added yet.</p>
                @can('admin')
                    <a href="{{ route('admin.print-settings.index') }}"
                        class="inline-block mt-2 text-[0.8125rem] text-slate-500 underline">
                        Add notes in Settings
                    </a>
                @endcan
            </div>
        @endif

    </main>

</x-layout>
