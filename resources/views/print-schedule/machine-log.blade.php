<x-layout title="Machine Log — Lockie Portal">

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        {{-- Header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:1.5rem;flex-wrap:wrap;">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Machine Run Log</h1>
                <p class="text-slate-500 text-sm mt-1">Operator activity, job durations, and idle time per machine.</p>
            </div>

            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <form method="GET" action="{{ route('print.machine-log') }}" id="log-filter-form"
                    style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

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
                    style="display:inline-flex;align-items:center;gap:6px;font-size:0.875rem;padding:8px 14px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;color:#475569;text-decoration:none;white-space:nowrap;transition:background 0.15s;"
                    onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                    <svg style="width:14px;height:14px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Analytics
                </a>

                {{-- Export CSV --}}
                <a href="{{ route('print.machine-log.export', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'machine' => $machine, 'operator' => $operator]) }}"
                    style="display:inline-flex;align-items:center;gap:6px;font-size:0.875rem;padding:8px 14px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;color:#475569;text-decoration:none;white-space:nowrap;transition:background 0.15s;"
                    onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                    <svg style="width:14px;height:14px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>

        @if(collect($byMachine)->isEmpty())
            <div style="background:#f8fafc;border:2px dashed #e2e8f0;border-radius:16px;padding:64px 24px;text-align:center;color:#94a3b8;">
                <svg style="width:40px;height:40px;margin:0 auto 12px;color:#cbd5e1;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <p style="font-size:1rem;font-weight:600;color:#64748b;margin-bottom:4px;">No runs recorded</p>
                <p style="font-size:0.875rem;">No machine activity was logged for this date.</p>
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

            {{-- Summary bar --}}
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:1.5rem;">
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
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 20px;text-align:center;min-width:110px;">
                        <div style="font-size:1.25rem;font-weight:700;color:{{ $colour ?? '#334155' }};">{{ $value }}</div>
                        <div style="font-size:0.75rem;color:#94a3b8;margin-top:2px;">{{ $label }}</div>
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

                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;margin-bottom:1.5rem;">

                    {{-- Machine header --}}
                    <div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:1rem;font-weight:700;color:#334155;">{{ $machineLabel }}</span>
                            <span style="font-size:0.75rem;font-weight:600;background:#e2e8f0;color:#64748b;padding:2px 8px;border-radius:9999px;">{{ count($entries) }} {{ Str::plural('run', count($entries)) }}</span>
                        </div>
                        <div style="display:flex;gap:20px;font-size:0.8rem;color:#64748b;">
                            @if($machinePacks > 0)
                                <span><strong style="color:#334155;">{{ number_format($machinePacks) }}</strong> packs done</span>
                            @endif
                            <span>Running: <strong style="color:#334155;">{{ fmtDur($machineRunSecs) }}</strong></span>
                            @if($machineGapSecs > 60)
                                <span>Idle: <strong style="color:#ef4444;">{{ fmtDur($machineGapSecs) }}</strong></span>
                            @endif
                        </div>
                    </div>

                    {{-- Job groups — wrapped for horizontal scroll on narrow screens --}}
                    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
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
                                <div style="display:flex;align-items:center;gap:8px;padding:5px 20px;background:{{ $gapAlert ? '#fef2f2' : '#fafafa' }};border-top:1px dashed {{ $gapAlert ? '#fca5a5' : '#e2e8f0' }};">
                                    <svg style="width:12px;height:12px;color:{{ $gapAlert ? '#ef4444' : '#94a3b8' }};flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <span style="font-size:0.75rem;color:{{ $gapAlert ? '#b91c1c' : '#94a3b8' }};font-weight:{{ $gapAlert ? '600' : '400' }};">
                                        {{ fmtDur($firstEntryGap) }} idle between jobs
                                    </span>
                                </div>
                            @endif
                        @endif

                        {{-- Job header --}}
                        <div style="padding:10px 20px 8px;background:{{ $gi % 2 === 0 ? '#fff' : '#fafafa' }};border-top:{{ $gi > 0 ? '2px solid #e2e8f0' : 'none' }};display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;min-width:680px;">
                            <div>
                                <span style="font-size:0.9rem;font-weight:700;color:#1e293b;">
                                    {{ $job?->customer_name ?? 'Unknown Job' }}
                                </span>
                                @if($job?->product_code)
                                    <span style="font-size:0.8rem;color:#94a3b8;font-family:monospace;margin-left:8px;">{{ $job->product_code }}</span>
                                @endif
                                @if($job?->order_number)
                                    <span style="font-size:0.75rem;color:#94a3b8;margin-left:6px;">· {{ $job->order_number }}</span>
                                @endif
                            </div>
                            <div style="display:flex;align-items:center;gap:16px;font-size:0.8rem;color:#64748b;flex-wrap:wrap;">
                                @if($job?->order_quantity)
                                    <span>Order: <strong style="color:#334155;">{{ number_format($job->order_quantity) }} packs</strong></span>
                                @endif
                                @if($groupPacks > 0)
                                    <span>Done this log: <strong style="color:#15803d;">{{ number_format($groupPacks) }} packs</strong></span>
                                @endif
                                <span>Time: <strong style="color:#334155;">{{ fmtDur($groupRunSecs) }}</strong></span>
                                @if($groupRateStr)
                                    <span style="font-weight:700;color:#0284c7;background:#eff6ff;padding:2px 8px;border-radius:6px;font-size:0.78rem;">~{{ $groupRateStr }}</span>
                                @endif
                                @if($priorBaseline > 0 || $priorSecs > 0)
                                    <span style="border-left:1px solid #e2e8f0;padding-left:16px;color:#94a3b8;">
                                        Job total: <strong style="color:#64748b;">{{ number_format($totalPacksAllDays) }} packs</strong> / <strong style="color:#64748b;">{{ fmtDur($totalSecsAllDays) }}</strong>
                                        <span style="font-size:0.72rem;"> (inc. prior days)</span>
                                    </span>
                                @endif
                            </div>
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
                                <div style="display:grid;grid-template-columns:150px 1fr;align-items:center;gap:8px;padding:7px 20px 7px 36px;background:#fafaf7;border-top:1px dashed #e2e8f0;border-bottom:1px dashed #e2e8f0;min-width:680px;">
                                    <div>
                                        <div style="font-size:0.82rem;font-weight:600;color:#92400e;font-family:monospace;">
                                            {{ $entry['break_start']->format('H:i') }} → {{ $entry['break_end']->format('H:i') }}
                                        </div>
                                        <div style="font-size:0.72rem;color:#b45309;margin-top:1px;">{{ fmtDur($entry['gap']) }}</div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:7px;">
                                        <span style="font-size:0.72rem;font-weight:700;background:#fef3c7;color:#92400e;padding:3px 9px;border-radius:9999px;">{{ $entry['break_reason'] }}</span>
                                    </div>
                                </div>
                            @elseif($ei > 0 && ($entry['gap'] ?? 0) > 60)
                                {{-- Unnamed idle gap between same-job runs --}}
                                @php $gapAlert = $entry['gap'] >= 300; @endphp
                                <div style="display:flex;align-items:center;gap:8px;padding:4px 40px;background:{{ $gapAlert ? '#fef2f2' : '#fafafa' }};border-top:1px dashed {{ $gapAlert ? '#fca5a5' : '#e2e8f0' }};">
                                    <svg style="width:11px;height:11px;color:{{ $gapAlert ? '#ef4444' : '#94a3b8' }};flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <span style="font-size:0.72rem;color:{{ $gapAlert ? '#b91c1c' : '#94a3b8' }};font-weight:{{ $gapAlert ? '600' : '400' }};">
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
                                    <div style="height:1px;background:#f1f5f9;margin-left:36px;"></div>
                                @endif

                                <div style="display:grid;grid-template-columns:150px 120px 1fr 120px 100px 130px;align-items:center;gap:8px;padding:9px 20px 9px 36px;border-top:{{ $si === 0 ? '1px solid #f1f5f9' : 'none' }};background:{{ $gi % 2 === 0 ? '#fff' : '#fafafa' }};min-width:680px;">

                                    {{-- Time --}}
                                    <div>
                                        <div style="font-size:0.82rem;font-weight:600;color:#334155;font-family:monospace;">
                                            {{ $seg['start']->format('H:i') }}
                                            →
                                            @if($seg['end'])
                                                {{ $seg['end']->format('H:i') }}
                                            @else
                                                <span style="color:#16a34a;">now</span>
                                            @endif
                                        </div>
                                        <div style="font-size:0.72rem;color:#94a3b8;margin-top:1px;">{{ $durStr }}</div>
                                    </div>

                                    {{-- Operator (first segment of each run only) --}}
                                    <div style="font-size:0.82rem;color:#475569;">
                                        @if($isFirst) {{ $run->user?->name ?? '—' }} @endif
                                    </div>

                                    {{-- Middle info --}}
                                    <div>
                                        @if($seg['type'] === 'running' && $run->progress_packs !== null && $run->progress_at !== null && count($run->progressLogs) === 0)
                                            {{-- Legacy: run has progress_packs but no history entries (pre-feature data) --}}
                                            <div style="font-size:0.75rem;color:#475569;">
                                                Last update <span style="font-family:monospace;color:#334155;">{{ $run->progress_at->format('H:i') }}</span>
                                            </div>
                                            <div style="font-size:0.72rem;color:#64748b;margin-top:1px;">
                                                {{ number_format($run->progress_packs) }} packs logged
                                            </div>
                                        @elseif($seg['type'] === 'running')
                                            <span style="font-size:0.72rem;color:#cbd5e1;">No updates logged</span>
                                        @endif
                                    </div>

                                    {{-- Rate --}}
                                    <div style="text-align:right;">
                                        @if($rateStr)
                                            <span style="font-size:0.78rem;font-weight:700;color:#0284c7;background:#eff6ff;padding:3px 8px;border-radius:6px;white-space:nowrap;">
                                                ~{{ $rateStr }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Packs --}}
                                    <div style="text-align:right;">
                                        @if($seg['packs'] !== null)
                                            <span style="font-size:0.95rem;font-weight:700;color:#334155;">{{ number_format($seg['packs']) }}</span>
                                            <span style="font-size:0.7rem;color:#94a3b8;"> packs</span>
                                        @else
                                            <span style="color:#cbd5e1;font-size:0.8rem;">—</span>
                                        @endif
                                    </div>

                                    {{-- Status --}}
                                    <div style="text-align:right;">
                                        @if($seg['type'] === 'logged')
                                            <span style="font-size:0.72rem;font-weight:700;background:#f0fdf4;color:#15803d;padding:3px 9px;border-radius:9999px;">✓ Logged</span>
                                        @elseif($seg['type'] === 'complete')
                                            <span style="font-size:0.72rem;font-weight:700;background:#dcfce7;color:#15803d;padding:3px 9px;border-radius:9999px;">
                                                @if($seg['fully_complete']) ✓ Complete @else Ended @endif
                                            </span>
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
                                            <span style="font-size:0.72rem;font-weight:700;background:{{ $isBreakdown ? '#fee2e2' : '#fef3c7' }};color:{{ $isBreakdown ? '#b91c1c' : '#92400e' }};padding:3px 9px;border-radius:9999px;">{{ $pauseLabel }}</span>
                                            @php $nextBreak = $group['entries'][$ei + 1]['break_reason'] ?? null; @endphp
                                            @if($seg['pause_reason'] && !$nextBreak)
                                                <div style="font-size:0.7rem;color:{{ $isBreakdown ? '#b91c1c' : '#b45309' }};margin-top:3px;text-align:right;">{{ $seg['pause_reason'] }}</div>
                                            @endif
                                        @elseif($seg['type'] === 'handover')
                                            <span style="font-size:0.72rem;font-weight:700;background:#ede9fe;color:#5b21b6;padding:3px 9px;border-radius:9999px;">Handover</span>
                                        @elseif($seg['type'] === 'running')
                                            <span style="font-size:0.72rem;font-weight:700;background:#dcfce7;color:#15803d;padding:3px 9px;border-radius:9999px;display:inline-flex;align-items:center;gap:5px;">
                                                <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;animation:psRun 1.5s infinite;display:inline-block;"></span>
                                                Running
                                            </span>
                                        @endif
                                        @if($seg['type'] !== 'logged' && $run->packs_corrected_from !== null)
                                            <div style="font-size:0.68rem;color:#b45309;background:#fef3c7;padding:2px 7px;border-radius:9999px;margin-top:4px;white-space:nowrap;">
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
