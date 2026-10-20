<x-layout title="Machine Log — Lockie Portal">

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Machine Run Log</h1>
                <p class="text-slate-500 text-sm mt-1">Operator activity, job durations, and idle time per machine.</p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <form method="GET" action="{{ route('print.machine-log') }}" id="log-filter-form"
                    class="flex items-center gap-2 flex-wrap">

                    {{-- Date range --}}
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        onchange="this.form.submit()"
                        class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        title="From date">
                    <span class="text-slate-400 text-sm">→</span>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                        onchange="this.form.submit()"
                        class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        title="To date">

                    {{-- Machine --}}
                    <select name="machine" onchange="this.form.submit()"
                        class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white">
                        <option value="all" {{ $machine === 'all' ? 'selected' : '' }}>All machines</option>
                        @foreach($machines as $m)
                            <option value="{{ $m }}" {{ $machine === $m ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $m)) }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Operator --}}
                    <select name="operator" onchange="this.form.submit()"
                        class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white">
                        <option value="all" {{ $operator === 'all' ? 'selected' : '' }}>All operators</option>
                        @foreach($operators as $op)
                            <option value="{{ $op->id }}" {{ $operator == $op->id ? 'selected' : '' }}>
                                {{ $op->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Quick nav --}}
                    <a href="{{ route('print.machine-log', ['date_from' => \Carbon\Carbon::parse($dateFrom)->subDay()->format('Y-m-d'), 'date_to' => \Carbon\Carbon::parse($dateFrom)->subDay()->format('Y-m-d'), 'machine' => $machine, 'operator' => $operator]) }}"
                        class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2 rounded-lg border border-slate-200 bg-white transition-colors">
                        ← Yesterday
                    </a>
                    @if($dateFrom !== today()->format('Y-m-d') || $dateTo !== today()->format('Y-m-d'))
                        <a href="{{ route('print.machine-log', ['date_from' => today()->format('Y-m-d'), 'date_to' => today()->format('Y-m-d'), 'machine' => $machine, 'operator' => $operator]) }}"
                            class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2 rounded-lg border border-slate-200 bg-white transition-colors">
                            Today →
                        </a>
                    @endif
                </form>

                {{-- Analytics --}}
                <a href="{{ route('print.analytics') }}"
                    class="inline-flex items-center gap-1.5 text-sm px-3.5 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 no-underline whitespace-nowrap hover:bg-slate-100 transition-colors">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Analytics
                </a>

                {{-- Export CSV --}}
                <a href="{{ route('print.machine-log.export', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'machine' => $machine, 'operator' => $operator]) }}"
                    class="inline-flex items-center gap-1.5 text-sm px-3.5 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 no-underline whitespace-nowrap hover:bg-slate-100 transition-colors">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>

        @if(collect($byMachine)->isEmpty())
            <div class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl py-16 px-6 text-center text-slate-400">
                <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <p class="text-base font-semibold text-slate-500 mb-1">No runs recorded</p>
                <p class="text-sm">No machine activity was logged for this date.</p>
            </div>
        @else

            @php
                // Recompute gaps and named-break info from run timestamps directly in Blade.
                // This runs inside the freshly-compiled template, bypassing any server-side
                // PHP OPcache that may be serving a stale version of the controller.
                $byMachine = collect($byMachine)->map(function ($machineEntries) {
                    $fresh = [];
                    foreach ($machineEntries as $fi => $fe) {
                        $gap = null; $breakReason = null; $breakStart = null; $breakEnd = null;
                        if ($fi > 0) {
                            $pr = $machineEntries[$fi - 1]['run'];
                            if ($pr->ended_at) {
                                $g = abs((int) $fe['run']->started_at->diffInSeconds($pr->ended_at));
                                if ($g > 60) {
                                    $gap = $g;
                                    if ($fe['run']->print_job_id === $pr->print_job_id && ($pr->pause_type || $pr->pause_reason)) {
                                        $breakReason = match($pr->pause_type ?? null) {
                                            'dinner'       => 'Dinner',
                                            'away'         => 'Away',
                                            'end_of_shift' => 'End of Shift',
                                            'breakdown'    => 'Breakdown' . ($pr->pause_reason ? ': ' . $pr->pause_reason : ''),
                                            default        => $pr->pause_reason,
                                        };
                                        $breakStart  = $pr->ended_at;
                                        $breakEnd    = $fe['run']->started_at;
                                    }
                                }
                            }
                        }
                        $fresh[] = [
                            'run'          => $fe['run'],
                            'gap'          => $gap,
                            'break_reason' => $breakReason,
                            'break_start'  => $breakStart,
                            'break_end'    => $breakEnd,
                        ];
                    }
                    return $fresh;
                })->all();
            @endphp

            @php
                $totalRuns = 0;
                $totalPacks = 0;
                $totalRunSecs = 0;
                $totalGapSecs = 0;

                // Flatten all entries for global totals
                $allEntries = [];
                foreach ($byMachine as $entries) {
                    foreach ($entries as $entry) {
                        $allEntries[] = $entry;
                        $totalRuns++;
                        $run = $entry['run'];
                        if ($run->ended_at) {
                            $totalRunSecs += abs((int) $run->ended_at->diffInSeconds($run->started_at));
                        } else {
                            // Use progress_at so the rate reflects packs ÷ measured time, not packs ÷ elapsed-to-now
                            $rateEnd = $run->progress_at ?? now();
                            $totalRunSecs += abs((int) $rateEnd->diffInSeconds($run->started_at));
                        }
                        $totalGapSecs += $entry['gap'] ?? 0;
                    }
                }

                // Group runs by job and use the last cumulative packs_produced per group (not a sum).
                // Subtract any prior-period baseline so the summary only counts packs done in this date range.
                $summaryJobGroups = collect($allEntries)->groupBy(fn($e) => $e['run']->print_job_id ?? 'unknown');
                foreach ($summaryJobGroups as $jobKey => $groupEntries) {
                    $groupRuns   = collect($groupEntries)->pluck('run');
                    $baseline    = is_numeric($jobKey) ? ($jobBaselines[(int)$jobKey] ?? 0) : 0;
                    $activePacks = $groupRuns->whereNull('ended_at')->whereNotNull('progress_packs')->max('progress_packs');
                    if ($activePacks !== null) {
                        $totalPacks += max(0, $activePacks - $baseline);
                    } else {
                        $lastPacks = $groupRuns->filter(fn($r) => $r->packs_produced !== null && $r->ended_at !== null)
                            ->sortByDesc(fn($r) => $r->ended_at?->timestamp)->first()?->packs_produced ?? 0;
                        $totalPacks += max(0, $lastPacks - $baseline);
                    }
                }

                $avgRatePerHr = ($totalPacks > 0 && $totalRunSecs > 0)
                    ? (int) round($totalPacks / ($totalRunSecs / 3600))
                    : null;
                $avgRateStr = $avgRatePerHr !== null
                    ? ($avgRatePerHr >= 1000
                        ? number_format($avgRatePerHr / 1000, 1) . 'k/hr'
                        : number_format($avgRatePerHr) . '/hr')
                    : null;
                function fmtDur(int $secs): string {
                    $h = (int) floor($secs / 3600);
                    $m = (int) floor(($secs % 3600) / 60);
                    return ($h > 0 ? "{$h}h " : '') . "{$m}m";
                }
            @endphp

            @php
                // Load throughput targets and compute target packs/hour
                $throughputSettings = [
                    'auto' => [
                        200 => (int) \App\Models\PrintScheduleSetting::getValue('throughput_auto_200', 350),
                        300 => (int) \App\Models\PrintScheduleSetting::getValue('throughput_auto_300', 350),
                        370 => (int) \App\Models\PrintScheduleSetting::getValue('throughput_auto_370', 350),
                    ],
                    'baby' => [
                        200 => (int) \App\Models\PrintScheduleSetting::getValue('throughput_baby_200', 180),
                        300 => (int) \App\Models\PrintScheduleSetting::getValue('throughput_baby_300', 180),
                        370 => (int) \App\Models\PrintScheduleSetting::getValue('throughput_baby_370', 180),
                    ],
                    'coditherm' => (int) \App\Models\PrintScheduleSetting::getValue('throughput_coditherm', 300),
                    'laser'     => (int) \App\Models\PrintScheduleSetting::getValue('throughput_laser',     300),
                    'other'     => (int) \App\Models\PrintScheduleSetting::getValue('throughput_other',     200),
                ];
                [$wsh, $wsm] = array_map('intval', explode(':', \App\Models\PrintScheduleSetting::getValue('work_start', '08:00')));
                [$weh, $wem] = array_map('intval', explode(':', \App\Models\PrintScheduleSetting::getValue('work_end', '16:30')));
                $workHoursPerDay = (($weh * 60 + $wem) - ($wsh * 60 + $wsm) - (int) \App\Models\PrintScheduleSetting::getValue('break_minutes', '30')) / 60;

                $getTargetPPH = function(string $machineName, ?string $productCode) use ($throughputSettings, $workHoursPerDay): ?int {
                    if ($workHoursPerDay <= 0) return null;
                    $mn = strtolower($machineName);
                    $group = null;
                    foreach (array_keys($throughputSettings) as $prefix) {
                        if (str_starts_with($mn, $prefix)) { $group = $prefix; break; }
                    }
                    if (!$group) return null;
                    $entry = $throughputSettings[$group];
                    if (is_int($entry)) {
                        // Single flat rate (no size split)
                        $ppd = $entry;
                    } else {
                        preg_match('/(200|300|370)/', $productCode ?? '', $m);
                        $size = isset($m[1]) ? (int) $m[1] : 200;
                        $ppd  = $entry[$size] ?? null;
                    }
                    return $ppd ? (int) round($ppd / $workHoursPerDay) : null;
                };

                $rateColour = function(?int $actual, ?int $target): array {
                    if ($actual === null || $target === null || $target === 0) {
                        return ['bg' => '#eff6ff', 'text' => '#0284c7']; // blue (no target)
                    }
                    $pct = $actual / $target;
                    if ($pct >= 1.0)  return ['bg' => '#f0fdf4', 'text' => '#15803d']; // green
                    if ($pct >= 0.75) return ['bg' => '#fef3c7', 'text' => '#b45309']; // amber
                    return               ['bg' => '#fee2e2', 'text' => '#b91c1c'];     // red
                };
            @endphp

            {{-- Summary bar --}}
            <div class="flex flex-wrap gap-3 mb-6">
                @php
                $summaryCards = [
                    ['Total runs',   $totalRuns,                  null],
                    ['Total packs',  number_format($totalPacks),  null],
                    ['Machine time', fmtDur($totalRunSecs),       null],
                    ['Idle time',    fmtDur($totalGapSecs),       null],
                ];
                if ($avgRateStr) {
                    $summaryCards[] = ['Avg rate', '~' . $avgRateStr, '#0284c7'];
                }
            @endphp
            @foreach($summaryCards as [$label, $value, $colour])
                    <div class="bg-slate-50 border border-slate-200 rounded-xl px-5 py-3 text-center min-w-[110px]">
                        <div class="text-xl font-bold" style="color:{{ $colour ?? '#334155' }};">{{ $value }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">{{ $label }}</div>
                    </div>
                @endforeach
            </div>

            @foreach($byMachine as $machineName => $entries)
                @php
                    $machineLabel   = ucwords(str_replace('_', ' ', $machineName));
                    $machineRuns    = collect($entries)->pluck('run');
                    $machineRunSecs = $machineRuns->filter(fn($r) => $r->ended_at)->sum(fn($r) => abs((int) $r->ended_at->diffInSeconds($r->started_at)));
                    $machineGapSecs = collect($entries)->sum('gap');

                    // Group consecutive runs by job
                    $jobGroups = [];
                    $prevJobId = null;
                    foreach ($entries as $entry) {
                        $jobId = $entry['run']->print_job_id ?? 'unknown';
                        if ($jobId !== $prevJobId) {
                            $jobGroups[] = ['job' => $entry['run']->printJob, 'entries' => []];
                            $prevJobId = $jobId;
                        }
                        $jobGroups[count($jobGroups) - 1]['entries'][] = $entry;
                    }

                    // packs_produced is the cumulative total, so sum per job group (not per run)
                    $machinePacks = 0;
                    foreach ($jobGroups as $jg) {
                        $jgRuns = collect($jg['entries'])->pluck('run');
                        $ap = $jgRuns->whereNull('ended_at')->whereNotNull('progress_packs')->max('progress_packs');
                        if ($ap !== null) {
                            $machinePacks += $ap;
                        } else {
                            $machinePacks += $jgRuns->filter(fn($r) => $r->packs_produced !== null && $r->ended_at !== null)
                                ->sortByDesc(fn($r) => $r->ended_at?->timestamp)->first()?->packs_produced ?? 0;
                        }
                    }
                @endphp

                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden mb-6">

                    {{-- Machine header --}}
                    <div class="bg-slate-50 border-b border-slate-200 px-5 py-3.5 flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-center gap-2.5">
                            <span class="text-base font-bold text-slate-700">{{ $machineLabel }}</span>
                            <span class="text-xs font-semibold bg-slate-200 text-slate-500 px-2 py-px rounded-full">{{ count($entries) }} {{ Str::plural('run', count($entries)) }}</span>
                        </div>
                        <div class="flex gap-5 text-[0.8rem] text-slate-500">
                            @if($machinePacks > 0)
                                <span><strong class="text-slate-700">{{ number_format($machinePacks) }}</strong> packs done</span>
                            @endif
                            <span>Running: <strong class="text-slate-700">{{ fmtDur($machineRunSecs) }}</strong></span>
                            @if($machineGapSecs > 60)
                                <span>Idle: <strong class="text-red-500">{{ fmtDur($machineGapSecs) }}</strong></span>
                            @endif
                        </div>
                    </div>

                    {{-- Job groups — wrapped for horizontal scroll on narrow screens --}}
                    <div class="overflow-x-auto">
                    @foreach($jobGroups as $gi => $group)
                        @php
                            $job          = $group['job'];
                            $groupRuns    = collect($group['entries'])->pluck('run');
                            $groupRunSecs = $groupRuns->filter(fn($r) => $r->ended_at)->sum(fn($r) => abs((int)$r->ended_at->diffInSeconds($r->started_at)));
                            // Include active run duration (use progress_at so rate doesn't decay after last update)
                            $activeRunSecs = $groupRuns->whereNull('ended_at')->sum(fn($r) =>
                                $r->progress_at
                                    ? abs((int) $r->progress_at->diffInSeconds($r->started_at))
                                    : abs((int) now()->diffInSeconds($r->started_at)));
                            $groupTotalSecs = $groupRunSecs + $activeRunSecs;
                            // packs_produced is the cumulative total at each pause/end — use the most recent value.
                            // An active run's progress_packs is always the current cumulative total.
                            // Subtract any prior-period baseline so "Done this log" only counts packs in this date range.
                            $jobId = $group['entries'][0]['run']->print_job_id ?? null;
                            $priorBaseline = $jobId ? ($jobBaselines[$jobId] ?? 0) : 0;
                            $priorSecs     = $jobId ? ($jobPriorSecs[$jobId] ?? 0) : 0;
                            $activePacks = $groupRuns->whereNull('ended_at')->whereNotNull('progress_packs')->max('progress_packs');
                            $lastEndedPacks = $groupRuns->filter(fn($r) => $r->packs_produced !== null && $r->ended_at !== null)
                                ->sortByDesc(fn($r) => $r->id)->first()?->packs_produced ?? 0;
                            $currentBestPacks = $activePacks ?? ($lastEndedPacks > 0 ? $lastEndedPacks : null);
                            $groupPacks = max(0, ($activePacks ?? $lastEndedPacks) - $priorBaseline);
                            $groupHours = $groupTotalSecs > 0 ? $groupTotalSecs / 3600 : 0;
                            $groupRate = ($groupPacks && $groupHours >= 0.1) ? (int) round($groupPacks / $groupHours) : null;
                            $groupRateStr = $groupRate
                                ? ($groupRate >= 1000 ? number_format($groupRate / 1000, 1) . 'k/hr' : number_format($groupRate) . '/hr')
                                : null;
                            $groupTargetPPH = $getTargetPPH($machineName, $job?->product_code);
                            // packs_produced is cumulative (includes all prior days), so the job total
                            // IS the current best reading — not baseline+delta, which breaks after corrections.
                            $totalPacksAllDays = $currentBestPacks ?? $priorBaseline;
                            $totalSecsAllDays  = $priorSecs + $groupTotalSecs;
                        @endphp

                        {{-- Gap before this job group (gap from previous group's last run) --}}
                        @if($gi > 0)
                            @php
                                $firstEntryGap = $group['entries'][0]['gap'] ?? null;
                            @endphp
                            @if($firstEntryGap !== null && $firstEntryGap > 60)
                                @php $gapAlert = $firstEntryGap >= 300; @endphp
                                <div class="flex items-center gap-2 px-5 py-1.5 {{ $gapAlert ? 'bg-red-50' : 'bg-slate-50' }}" style="border-top:1px dashed {{ $gapAlert ? '#fca5a5' : '#e2e8f0' }};">
                                    <svg class="w-3 h-3 flex-shrink-0 {{ $gapAlert ? 'text-red-500' : 'text-slate-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <span class="text-xs {{ $gapAlert ? 'text-red-800 font-semibold' : 'text-slate-400' }}">
                                        {{ fmtDur($firstEntryGap) }} idle between jobs
                                    </span>
                                </div>
                            @endif
                        @endif

                        {{-- Job header --}}
                        <div class="px-5 pt-2.5 pb-2 min-w-[680px]" style="background:{{ $gi % 2 === 0 ? '#fff' : '#fafafa' }};border-top:{{ $gi > 0 ? '2px solid #e2e8f0' : 'none' }};">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <span class="text-[0.9rem] font-bold text-slate-800">
                                        {{ $job?->customer_name ?? 'Unknown Job' }}
                                    </span>
                                    @if($job?->product_code)
                                        <span class="text-[0.8rem] text-slate-400 font-mono ml-2">{{ $job->product_code }}</span>
                                    @endif
                                    @if($job?->order_number)
                                        <span class="text-xs text-slate-400 ml-1.5">· {{ $job->order_number }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4 text-[0.8rem] text-slate-500 flex-wrap justify-end">
                                    @if($job?->order_quantity)
                                        <span>Order: <strong class="text-slate-700">{{ number_format($job->order_quantity) }} packs</strong></span>
                                    @endif
                                    @if($groupPacks > 0)
                                        <span>Done this log: <strong class="text-green-700">{{ number_format($groupPacks) }} packs</strong></span>
                                    @endif
                                    <span>Time: <strong class="text-slate-700">{{ fmtDur($groupRunSecs) }}</strong></span>
                                    @if($groupRateStr)
                                        @php $gc = $rateColour($groupRate, $groupTargetPPH); @endphp
                                        <span class="font-bold text-[0.78rem] px-2 py-px rounded-md whitespace-nowrap" style="color:{{ $gc['text'] }};background:{{ $gc['bg'] }};">
                                            ~{{ $groupRateStr }}@if($groupTargetPPH) <span class="font-normal opacity-75">/ {{ $groupTargetPPH }}/hr target</span>@endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if($priorBaseline > 0 || $priorSecs > 0)
                                <div class="mt-1 text-[0.78rem] text-slate-400">
                                    Job total: <strong class="text-slate-500">{{ number_format($totalPacksAllDays) }} packs</strong> / <strong class="text-slate-500">{{ fmtDur($totalSecsAllDays) }}</strong>
                                    <span class="text-[0.72rem]">(inc. prior days)</span>
                                </div>
                            @endif
                        </div>

                        @foreach($group['entries'] as $ei => $entry)
                            @php
                                $run = $entry['run'];

                                // Baseline packs before this run (from prior runs in this date range, or prior-period baseline)
                                $prevCumPacks = collect($group['entries'])
                                    ->take($ei)
                                    ->pluck('run')
                                    ->filter(fn($r) => $r->packs_produced !== null)
                                    ->last()?->packs_produced ?? $priorBaseline;

                                // Build segments: one per progress log entry, plus a final open/closed segment
                                $segments      = [];
                                $segStart      = $run->started_at;
                                $segBasePacks  = $prevCumPacks;

                                foreach ($run->progressLogs as $log) {
                                    $segSecs  = abs((int) $log->logged_at->diffInSeconds($segStart));
                                    $segPacks = max(0, $log->packs_cumulative - $segBasePacks);
                                    $segHours = $segSecs > 0 ? $segSecs / 3600 : 0;
                                    $segments[] = [
                                        'start'  => $segStart,
                                        'end'    => $log->logged_at,
                                        'secs'   => $segSecs,
                                        'packs'  => $segPacks,
                                        'rate'   => ($segPacks > 0 && $segHours >= 0.1) ? (int) round($segPacks / $segHours) : null,
                                        'type'   => 'logged',
                                    ];
                                    $segStart     = $log->logged_at;
                                    $segBasePacks = $log->packs_cumulative;
                                }

                                // Final segment (running or ended)
                                $finalEnd  = $run->ended_at;
                                $finalSecs = $finalEnd
                                    ? abs((int) $finalEnd->diffInSeconds($segStart))
                                    : abs((int) now()->diffInSeconds($segStart));
                                if ($run->packs_produced !== null) {
                                    $finalPacks = max(0, $run->packs_produced - $segBasePacks);
                                } elseif ($run->progress_packs !== null && $run->progress_packs > $segBasePacks) {
                                    $finalPacks = max(0, $run->progress_packs - $segBasePacks);
                                } else {
                                    $finalPacks = null;
                                }
                                $finalHours = $finalSecs > 0 ? $finalSecs / 3600 : 0;
                                $segments[] = [
                                    'start'          => $segStart,
                                    'end'            => $finalEnd,
                                    'secs'           => $finalSecs,
                                    'packs'          => $finalPacks,
                                    'rate'           => ($finalPacks && $finalHours >= 0.1) ? (int) round($finalPacks / $finalHours) : null,
                                    'type'           => $run->ended_at ? ($run->end_reason ?? 'complete') : 'running',
                                    'pause_type'     => $run->pause_type,
                                    'pause_reason'   => $run->pause_reason,
                                    'fully_complete' => $run->fully_complete,
                                ];
                            @endphp

                            {{-- Named break between same-job runs (e.g. Dinner) --}}
                            @if($entry['break_reason'])
                                <div class="grid items-center gap-2 px-5 py-1.5 pl-9 bg-[#fafaf7] border-t border-b border-dashed border-slate-200 min-w-[680px]" style="grid-template-columns:150px 1fr;">
                                    <div>
                                        <div class="text-[0.72rem] font-semibold text-amber-700 mb-px">{{ $entry['break_start']->format('d M Y') }}</div>
                                        <div class="text-[0.82rem] font-semibold text-amber-800 font-mono">
                                            {{ $entry['break_start']->format('H:i') }} → {{ $entry['break_end']->format('H:i') }}
                                        </div>
                                        <div class="text-[0.72rem] text-amber-700 mt-px">{{ fmtDur($entry['gap']) }}</div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[0.72rem] font-bold bg-amber-100 text-amber-800 px-2.5 py-px rounded-full">{{ $entry['break_reason'] }}</span>
                                    </div>
                                </div>
                            @elseif($ei > 0 && ($entry['gap'] ?? 0) > 60)
                                {{-- Unnamed idle gap between same-job runs --}}
                                @php $gapAlert = $entry['gap'] >= 300; @endphp
                                <div class="flex items-center gap-2 py-1 px-10 {{ $gapAlert ? 'bg-red-50' : 'bg-slate-50' }}" style="border-top:1px dashed {{ $gapAlert ? '#fca5a5' : '#e2e8f0' }};">
                                    <svg class="w-3 h-3 flex-shrink-0 {{ $gapAlert ? 'text-red-500' : 'text-slate-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <span class="text-[0.72rem] {{ $gapAlert ? 'text-red-800 font-semibold' : 'text-slate-400' }}">
                                        {{ fmtDur($entry['gap']) }} idle
                                    </span>
                                </div>
                            @endif

                            @foreach($segments as $si => $seg)
                                @php
                                    $durStr  = fmtDur($seg['secs']);
                                    $rateStr = null;
                                    if ($seg['rate'] !== null) {
                                        $rateStr = $seg['rate'] >= 1000
                                            ? number_format($seg['rate'] / 1000, 1) . 'k/hr'
                                            : number_format($seg['rate']) . '/hr';
                                    }
                                    $isFirst = $si === 0;
                                @endphp

                                {{-- Thin divider between segments of the same run --}}
                                @if($si > 0)
                                    <div class="h-px bg-slate-100 ml-9"></div>
                                @endif

                                <div class="grid items-center gap-2 px-5 py-2.5 pl-9 min-w-[680px]"
                                     style="grid-template-columns:150px 120px 1fr 120px 100px 130px;border-top:{{ $si === 0 ? '1px solid #f1f5f9' : 'none' }};background:{{ $gi % 2 === 0 ? '#fff' : '#fafafa' }};">

                                    {{-- Time --}}
                                    <div>
                                        <div class="text-[0.72rem] font-semibold text-slate-500 mb-px">{{ $seg['start']->format('d M Y') }}</div>
                                        <div class="text-[0.82rem] font-semibold text-slate-700 font-mono">
                                            {{ $seg['start']->format('H:i') }}
                                            →
                                            @if($seg['end'])
                                                @if($seg['end']->format('Y-m-d') !== $seg['start']->format('Y-m-d'))
                                                    <span class="text-[0.72rem] font-semibold text-slate-500">{{ $seg['end']->format('d M') }}</span>
                                                @endif
                                                {{ $seg['end']->format('H:i') }}
                                            @else
                                                <span class="text-green-600">now</span>
                                            @endif
                                        </div>
                                        <div class="text-[0.72rem] text-slate-400 mt-px">{{ $durStr }}</div>
                                    </div>

                                    {{-- Operator (first segment of each run only) --}}
                                    <div class="text-[0.82rem] text-slate-600">
                                        @if($isFirst) {{ $run->user?->name ?? '—' }} @endif
                                    </div>

                                    {{-- Middle info --}}
                                    <div>
                                        @if($seg['type'] === 'running' && $run->progress_packs !== null && $run->progress_at !== null && count($run->progressLogs) === 0)
                                            {{-- Legacy: run has progress_packs but no history entries (pre-feature data) --}}
                                            <div class="text-xs text-slate-600">
                                                Last update <span class="font-mono text-slate-700">{{ $run->progress_at->format('H:i') }}</span>
                                            </div>
                                            <div class="text-[0.72rem] text-slate-500 mt-px">
                                                {{ number_format($run->progress_packs) }} packs logged
                                            </div>
                                        @elseif($seg['type'] === 'running')
                                            <span class="text-[0.72rem] text-slate-300">No updates logged</span>
                                        @endif
                                    </div>

                                    {{-- Rate --}}
                                    <div class="text-right">
                                        @if($rateStr)
                                            @php $sc = $rateColour($seg['rate'], $groupTargetPPH); @endphp
                                            <span class="text-[0.78rem] font-bold px-2 py-px rounded-md whitespace-nowrap" style="color:{{ $sc['text'] }};background:{{ $sc['bg'] }};">
                                                ~{{ $rateStr }}
                                            </span>
                                            @if($groupTargetPPH)
                                                <div class="text-[0.68rem] text-slate-400 mt-0.5 whitespace-nowrap">target {{ $groupTargetPPH }}/hr</div>
                                            @endif
                                        @elseif($groupTargetPPH)
                                            <div class="text-[0.68rem] text-slate-300 whitespace-nowrap">target {{ $groupTargetPPH }}/hr</div>
                                        @endif
                                    </div>

                                    {{-- Packs --}}
                                    <div class="text-right">
                                        @if($seg['packs'] !== null)
                                            <span class="text-[0.95rem] font-bold text-slate-700">{{ number_format($seg['packs']) }}</span>
                                            <span class="text-[0.7rem] text-slate-400"> packs</span>
                                        @else
                                            <span class="text-slate-300 text-[0.8rem]">—</span>
                                        @endif
                                    </div>

                                    {{-- Status --}}
                                    <div class="text-right">
                                        @if($seg['type'] === 'logged')
                                            <span class="text-[0.72rem] font-bold bg-green-50 text-green-700 px-2.5 py-px rounded-full">✓ Logged</span>
                                        @elseif($seg['type'] === 'complete')
                                            <span class="text-[0.72rem] font-bold bg-green-100 text-green-700 px-2.5 py-px rounded-full">
                                                @if($seg['fully_complete']) ✓ Complete @else Ended @endif
                                            </span>
                                            @if($seg['pause_reason'] && ($seg['packs'] === null || $seg['packs'] === 0))
                                                <div class="text-[0.7rem] text-slate-500 mt-0.5 text-right">{{ $seg['pause_reason'] }}</div>
                                            @endif
                                        @elseif($seg['type'] === 'pause')
                                            @php
                                                $pauseLabel = match($seg['pause_type'] ?? null) {
                                                    'dinner'       => 'Dinner',
                                                    'away'         => 'Away',
                                                    'end_of_shift' => 'End of Shift',
                                                    'breakdown'    => 'Breakdown',
                                                    default        => 'Paused',
                                                };
                                                $isBreakdown = ($seg['pause_type'] ?? null) === 'breakdown';
                                            @endphp
                                            <span class="text-[0.72rem] font-bold px-2.5 py-px rounded-full {{ $isBreakdown ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">{{ $pauseLabel }}</span>
                                            @php $nextBreak = $group['entries'][$ei + 1]['break_reason'] ?? null; @endphp
                                            @if($seg['pause_reason'] && !$nextBreak)
                                                <div class="text-[0.7rem] mt-0.5 text-right {{ $isBreakdown ? 'text-red-700' : 'text-amber-700' }}">{{ $seg['pause_reason'] }}</div>
                                            @endif
                                        @elseif($seg['type'] === 'handover')
                                            <span class="text-[0.72rem] font-bold bg-violet-100 text-violet-800 px-2.5 py-px rounded-full">Handover</span>
                                        @elseif($seg['type'] === 'running')
                                            <span class="text-[0.72rem] font-bold bg-green-100 text-green-700 px-2.5 py-px rounded-full inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-600 inline-block" style="animation:psRun 1.5s infinite;"></span>
                                                Running
                                            </span>
                                        @endif
                                        @if($seg['type'] !== 'logged' && $run->packs_corrected_from !== null)
                                            <div class="text-[0.68rem] text-amber-700 bg-amber-100 px-1.5 py-px rounded-full mt-1 whitespace-nowrap">
                                                ✎ Corrected: {{ $run->packs_corrected_from }} → {{ $run->packs_produced }}
                                            </div>
                                        @endif
                                    </div>

                                </div>
                            @endforeach
                        @endforeach

                    @endforeach
                    </div>{{-- end scroll wrapper --}}

                </div>
            @endforeach

        @endif

    </main>

    <style>
        @keyframes psRun {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.4; transform: scale(0.65); }
        }
    </style>

</x-layout>
