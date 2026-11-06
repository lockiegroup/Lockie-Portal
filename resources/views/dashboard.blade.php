<x-layout title="Dashboard — Lockie Portal">
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    {{-- ── Greeting ── --}}
    @php
        $hour      = now()->hour;
        $greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $firstName = explode(' ', auth()->user()->name)[0];
    @endphp
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-slate-800">{{ $greeting }}, {{ $firstName }}.</h1>
        <p class="text-slate-400 mt-0.5 text-sm">{{ now()->format('l j F Y') }}</p>
    </div>

    {{-- ── Stat tiles ── --}}
    @php
        $tiles = [];

        if ($salesStats) {
            $change    = $salesStats['mtd_change'];
            $changeStr = $change !== null ? (($change >= 0 ? '+' : '') . number_format($change, 1) . '%') : null;
            $tiles[] = [
                'label'    => 'Revenue MTD',
                'value'    => '£' . number_format($salesStats['mtd'], 0),
                'sub'      => $changeStr ? $changeStr . ' vs last month' : null,
                'subColor' => $change !== null ? ($change >= 0 ? 'text-green-600' : 'text-red-500') : 'text-slate-400',
                'href'     => route('sales'),
                'icon'     => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            ];
            $tiles[] = [
                'label'    => 'Revenue YTD',
                'value'    => '£' . number_format($salesStats['ytd'], 0),
                'sub'      => now()->format('Y'),
                'subColor' => 'text-slate-400',
                'href'     => route('sales'),
                'icon'     => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
            ];
        }

        if ($orderStats) {
            $tiles[] = [
                'label'    => 'Open Orders',
                'value'    => number_format($orderStats['open']),
                'sub'      => 'on the board',
                'subColor' => 'text-slate-400',
                'href'     => route('order-board.index'),
                'icon'     => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
            ];
        }

        if ($myTasks !== null) {
            $totalTasks = $myTasks->flatten()->count();
            $tiles[] = [
                'label'    => 'My Tasks',
                'value'    => $totalTasks,
                'sub'      => $overdueCount > 0 ? $overdueCount . ' overdue' : 'all on track',
                'subColor' => $overdueCount > 0 ? 'text-red-500 font-semibold' : 'text-green-600',
                'href'     => route('key-actions.index'),
                'icon'     => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
            ];
        }

        if ($reminderStats) {
            $tiles[] = [
                'label'    => 'Reminders',
                'value'    => number_format($reminderStats['pending']),
                'sub'      => 'pending calls',
                'subColor' => $reminderStats['pending'] > 0 ? 'text-amber-500' : 'text-slate-400',
                'href'     => route('reminders.index'),
                'icon'     => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
            ];
        }
    @endphp

    @if(count($tiles))
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 mb-8">
        @foreach($tiles as $tile)
        <a href="{{ $tile['href'] }}" class="bg-white border border-slate-200 rounded-xl px-5 py-4 shadow-sm hover:shadow-md hover:border-slate-300 transition-all no-underline group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[0.68rem] font-bold text-slate-400 uppercase tracking-[0.08em]">{{ $tile['label'] }}</span>
                <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-400 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $tile['icon'] !!}</svg>
            </div>
            <div class="text-[1.625rem] font-bold text-slate-800 leading-none mb-1.5">{{ $tile['value'] }}</div>
            @if($tile['sub'])
            <div class="text-xs {{ $tile['subColor'] }}">{{ $tile['sub'] }}</div>
            @endif
        </a>
        @endforeach
    </div>
    @endif

    {{-- ── Two-column section: tasks + print ── --}}
    @php $hasTasks = $myTasks !== null && $myTasks->isNotEmpty(); @endphp

    <div class="{{ ($hasTasks && $printStats) ? 'grid grid-cols-1 lg:grid-cols-2 gap-6' : '' }}">

        {{-- My Tasks --}}
        @if($myTasks !== null)
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-[0.08em]">My Open Tasks</h2>
                <a href="{{ route('key-actions.index') }}" class="text-xs text-slate-400 hover:text-slate-600 transition-colors no-underline">View all →</a>
            </div>

            @if($myTasks->isEmpty())
            <div class="bg-white border border-slate-200 rounded-xl p-8 text-center shadow-sm">
                <svg class="w-8 h-8 text-green-400 mx-auto mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                <p class="text-sm text-slate-400">All clear — no open tasks.</p>
            </div>
            @else
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                @php
                    $labelDots = ['yellow' => '#ca8a04', 'red' => '#dc2626', 'green' => '#16a34a'];
                    $allTasks  = $myTasks->flatten()->sortByDesc(fn($t) => $t->due_date && $t->due_date->isPast() ? 1 : 0);
                @endphp
                <div class="divide-y divide-slate-50">
                    @foreach($allTasks->take(6) as $task)
                    @php $isOverdue = !$task->completed && $task->due_date && $task->due_date->isPast(); @endphp
                    <a href="{{ route('key-actions.show', $task->group) }}" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 transition-colors no-underline group">
                        <span class="mt-1.5 w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $labelDots[$task->label] ?? '#e2e8f0' }};"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-[0.8125rem] text-slate-700 group-hover:text-slate-900 transition-colors leading-snug">{{ $task->title }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $task->group->name }}</p>
                        </div>
                        @if($task->due_date)
                        <span class="text-xs flex-shrink-0 {{ $isOverdue ? 'text-red-500 font-semibold' : 'text-slate-400' }}">
                            {{ $isOverdue ? 'Overdue' : $task->due_date->format('d M') }}
                        </span>
                        @endif
                    </a>
                    @endforeach
                </div>
                @php $remaining = $allTasks->count() - 6; @endphp
                @if($remaining > 0)
                <div class="px-4 py-2.5 border-t border-slate-50">
                    <a href="{{ route('key-actions.index') }}" class="text-xs text-slate-400 hover:text-slate-600 no-underline">+ {{ $remaining }} more task{{ $remaining !== 1 ? 's' : '' }}</a>
                </div>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- Print Schedule --}}
        @if($printStats !== null)
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-[0.7rem] font-bold text-slate-400 uppercase tracking-[0.08em]">Print Schedule</h2>
                <a href="{{ route('print.overview') }}" class="text-xs text-slate-400 hover:text-slate-600 transition-colors no-underline">View full →</a>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach($printStats as $key => $stat)
                @php
                    $ld = $stat['lead_days'];
                    if (!$stat['job_count'])      { $col = '#16a34a'; $bg = '#f0fdf4'; $border = '#bbf7d0'; }
                    elseif ($ld <= 5)             { $col = '#16a34a'; $bg = '#f0fdf4'; $border = '#bbf7d0'; }
                    elseif ($ld <= 10)            { $col = '#d97706'; $bg = '#fffbeb'; $border = '#fde68a'; }
                    else                          { $col = '#dc2626'; $bg = '#fef2f2'; $border = '#fecaca'; }
                @endphp
                <a href="{{ route('print.index') }}" class="rounded-xl px-4 py-3.5 no-underline hover:opacity-90 transition-opacity"
                    style="background:{{ $bg }};border:1px solid {{ $border }};">
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.08em] mb-1.5" style="color:{{ $col }};opacity:0.7;">{{ $stat['label'] }}</p>
                    <div class="flex items-baseline gap-1 mb-1">
                        <span class="text-2xl font-bold leading-none" style="color:{{ $col }};">{{ $stat['job_count'] > 0 ? number_format($ld, 1) : '0' }}</span>
                        <span class="text-xs font-medium" style="color:{{ $col }};opacity:0.7;">day lead</span>
                    </div>
                    <p class="text-xs" style="color:{{ $col }};opacity:0.75;">
                        {{ $stat['job_count'] }} job{{ $stat['job_count'] !== 1 ? 's' : '' }}
                        @if($stat['late_count'] > 0) · {{ $stat['late_count'] }} late @endif
                    </p>
                </a>
                @endforeach
            </div>
            @if($printNotes && trim($printNotes) !== '')
            <div class="mt-3 bg-white border border-slate-200 rounded-xl px-4 py-3.5 shadow-sm">
                <p class="text-[0.68rem] font-bold text-slate-400 uppercase tracking-[0.08em] mb-1.5">Notes</p>
                <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap">{{ $printNotes }}</p>
            </div>
            @endif
        </div>
        @endif

    </div>

    {{-- Fallback --}}
    @if(!count($tiles) && $myTasks === null && $printStats === null)
    <div class="bg-white border border-slate-200 rounded-xl p-14 text-center shadow-sm">
        <p class="text-slate-400 text-sm">Use the sidebar to navigate to your tools.</p>
    </div>
    @endif

</main>
</x-layout>
