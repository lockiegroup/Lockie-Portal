<x-layout title="Dashboard — Lockie Portal">

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}</h1>
            <p class="text-slate-500 mt-1">Here's what's happening today.</p>
        </div>

        {{-- ── Key Actions ── --}}
        @if($myTasks !== null)
            <h2 class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-[0.08em] mb-3.5">My Key Actions</h2>

            @if($myTasks->isEmpty())
                <div class="bg-slate-50 border border-dashed border-slate-200 rounded-[0.875rem] p-8 text-center mb-10">
                    <p class="text-sm text-slate-400">No open tasks assigned to you.</p>
                </div>
            @else
                <div class="flex flex-col gap-2.5 mb-10">
                    @foreach($myTasks as $groupId => $tasks)
                        @php $group = $tasks->first()->group; @endphp
                        <div class="bg-white border border-slate-200 rounded-[0.875rem] px-6 py-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3.5">
                                <a href="{{ route('key-actions.show', $group) }}"
                                   class="text-sm font-semibold text-slate-800 no-underline hover:text-sky-600 transition-colors">
                                    {{ $group->name }}
                                </a>
                                <span class="text-xs text-slate-400 font-medium">{{ $tasks->count() }} task{{ $tasks->count() !== 1 ? 's' : '' }}</span>
                            </div>
                            @php
                                $labelDots = ['yellow' => '#ca8a04', 'red' => '#dc2626', 'green' => '#16a34a'];
                                $visible   = $tasks->take(3);
                                $remaining = $tasks->count() - $visible->count();
                            @endphp
                            <div class="flex flex-col gap-1">
                                @foreach($visible as $task)
                                    <div class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-[7px] bg-slate-50 border border-slate-100">
                                        <span style="width:7px;height:7px;border-radius:50%;flex-shrink:0;background:{{ $labelDots[$task->label] ?? '#e2e8f0' }};"></span>
                                        <span class="text-[0.8125rem] text-slate-700 flex-1">{{ $task->title }}</span>
                                    </div>
                                @endforeach
                                @if($remaining > 0)
                                    <a href="{{ route('key-actions.show', $group) }}" class="text-xs text-slate-500 px-2.5 py-1 no-underline hover:text-slate-700">
                                        + {{ $remaining }} more task{{ $remaining !== 1 ? 's' : '' }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- ── Print Schedule ── --}}
        @if($printStats !== null)
            <div class="flex items-center justify-between mb-3.5">
                <h2 class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-[0.08em]">Print Schedule</h2>
                <a href="{{ route('print.overview') }}" class="text-xs text-slate-500 no-underline hover:text-slate-700">View full overview &rarr;</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 mb-10">
                @foreach($printStats as $key => $stat)
                    @php
                        $hasJobs  = $stat['job_count'] > 0;
                        $hasLate  = $stat['late_count'] > 0;
                        $leadDays = $stat['lead_days'];
                        if (!$hasJobs)          { $leadColour = '#16a34a'; }
                        elseif ($leadDays <= 5) { $leadColour = '#16a34a'; }
                        elseif ($leadDays <= 10){ $leadColour = '#d97706'; }
                        else                    { $leadColour = '#dc2626'; }
                    @endphp
                    <div class="bg-white border border-slate-200 rounded-[0.875rem] px-5 py-[1.125rem] shadow-sm">
                        <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-[0.08em] mb-2">{{ $stat['label'] }}</p>
                        <div class="flex items-baseline gap-1 mb-2.5">
                            <span class="text-[1.875rem] font-bold leading-none" style="color:{{ $leadColour }};">{{ $hasJobs ? number_format($leadDays, 1) : '0' }}</span>
                            <span class="text-[0.8125rem] text-slate-400 font-medium">day lead</span>
                        </div>
                        <div class="text-[0.8125rem] text-slate-500">
                            <span class="font-semibold text-slate-800">{{ $stat['job_count'] }}</span> job{{ $stat['job_count'] !== 1 ? 's' : '' }}
                            @if($hasLate)
                                &bull; <span class="text-red-600 font-semibold">{{ $stat['late_count'] }} late</span>
                            @elseif($hasJobs)
                                &bull; <span class="text-green-600">all on track</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($printNotes && trim($printNotes) !== '')
                <div class="bg-white border border-slate-200 rounded-[0.875rem] px-5 py-[1.125rem] shadow-sm mt-3.5">
                    <p class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-[0.08em] mb-2">Notes &amp; Updates</p>
                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $printNotes }}</p>
                </div>
            @endif

        @endif

        {{-- Fallback if nothing to show --}}
        @if($myTasks === null && $printStats === null)
            <div class="bg-slate-50 border border-dashed border-slate-200 rounded-[0.875rem] p-12 text-center">
                <p class="text-[0.9375rem] text-slate-400">Nothing to show here yet — use the sidebar to navigate.</p>
            </div>
        @endif

    </main>

</x-layout>
