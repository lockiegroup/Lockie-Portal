<x-layout title="Machine Log — Lockie Portal">

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        {{-- Header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:1.5rem;flex-wrap:wrap;">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Machine Run Log</h1>
                <p class="text-slate-500 text-sm mt-1">Operator activity, job durations, and idle time per machine.</p>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('print.machine-log') }}"
                style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <input type="date" name="date" value="{{ $date }}"
                    onchange="this.form.submit()"
                    class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500">
                <select name="machine" onchange="this.form.submit()"
                    class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white">
                    <option value="all" {{ $machine === 'all' ? 'selected' : '' }}>All machines</option>
                    @foreach($machines as $m)
                        <option value="{{ $m }}" {{ $machine === $m ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $m)) }}
                        </option>
                    @endforeach
                </select>
                <a href="{{ route('print.machine-log', ['date' => today()->subDay()->format('Y-m-d'), 'machine' => $machine]) }}"
                    class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2 rounded-lg border border-slate-200 hover:border-slate-300 bg-white transition-colors">
                    ← Yesterday
                </a>
                @if($date !== today()->format('Y-m-d'))
                    <a href="{{ route('print.machine-log', ['date' => today()->format('Y-m-d'), 'machine' => $machine]) }}"
                        class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2 rounded-lg border border-slate-200 hover:border-slate-300 bg-white transition-colors">
                        Today →
                    </a>
                @endif
            </form>
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
                // Summary stats
                $totalRuns = 0;
                $totalPacks = 0;
                $totalRunSeconds = 0;
                $totalGapSeconds = 0;
                foreach ($byMachine as $entries) {
                    foreach ($entries as $entry) {
                        $run = $entry['run'];
                        $totalRuns++;
                        $totalPacks += $run->packs_produced ?? 0;
                        if ($run->ended_at) {
                            $totalRunSeconds += $run->ended_at->diffInSeconds($run->started_at);
                        }
                        $totalGapSeconds += $entry['gap'] ?? 0;
                    }
                }
            @endphp

            {{-- Summary bar --}}
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:1.5rem;">
                @php
                    $summaryItems = [
                        ['label' => 'Total runs', 'value' => $totalRuns],
                        ['label' => 'Total packs', 'value' => number_format($totalPacks)],
                        ['label' => 'Machine time', 'value' => gmdate('H\h i\m', $totalRunSeconds)],
                        ['label' => 'Idle time', 'value' => gmdate('H\h i\m', $totalGapSeconds)],
                    ];
                @endphp
                @foreach($summaryItems as $item)
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 20px;text-align:center;min-width:110px;">
                        <div style="font-size:1.25rem;font-weight:700;color:#334155;">{{ $item['value'] }}</div>
                        <div style="font-size:0.75rem;color:#94a3b8;margin-top:2px;">{{ $item['label'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Per-machine sections --}}
            @foreach($byMachine as $machineName => $entries)
                @php
                    $machineLabel = ucwords(str_replace('_', ' ', $machineName));
                    $machineRuns  = collect($entries)->pluck('run');
                    $machinePacks = $machineRuns->sum('packs_produced');
                    $machineRunSecs = $machineRuns->filter(fn($r) => $r->ended_at)->sum(fn($r) => $r->ended_at->diffInSeconds($r->started_at));
                    $machineGapSecs = collect($entries)->sum('gap');
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
                                <span><strong style="color:#334155;">{{ number_format($machinePacks) }}</strong> packs</span>
                            @endif
                            <span>On: <strong style="color:#334155;">{{ gmdate('H\h i\m', $machineRunSecs) }}</strong></span>
                            @if($machineGapSecs > 0)
                                <span>Idle: <strong style="color:#ef4444;">{{ gmdate('H\h i\m', $machineGapSecs) }}</strong></span>
                            @endif
                        </div>
                    </div>

                    {{-- Runs --}}
                    <div style="padding:0;">
                        @foreach($entries as $entry)
                            @php $run = $entry['run']; @endphp

                            {{-- Gap row --}}
                            @if($entry['gap'] !== null)
                                @php
                                    $gapMins = (int) round($entry['gap'] / 60);
                                    $gapHrs  = floor($gapMins / 60);
                                    $gapMin  = $gapMins % 60;
                                    $gapStr  = $gapHrs > 0 ? "{$gapHrs}h {$gapMin}m" : "{$gapMins}m";
                                    $gapAlert = $entry['gap'] >= 1800; // warn if gap ≥ 30 min
                                @endphp
                                <div style="display:flex;align-items:center;gap:10px;padding:6px 20px;background:{{ $gapAlert ? '#fef2f2' : '#fafafa' }};border-top:1px dashed {{ $gapAlert ? '#fca5a5' : '#e2e8f0' }};">
                                    <svg style="width:13px;height:13px;color:{{ $gapAlert ? '#ef4444' : '#94a3b8' }};flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <span style="font-size:0.78rem;color:{{ $gapAlert ? '#b91c1c' : '#94a3b8' }};font-weight:{{ $gapAlert ? '600' : '400' }};">
                                        {{ $gapStr }} idle{{ $gapAlert ? ' — no activity' : '' }}
                                    </span>
                                </div>
                            @endif

                            {{-- Run row --}}
                            <div style="display:grid;grid-template-columns:140px 1fr 140px 80px 120px;align-items:center;gap:12px;padding:12px 20px;border-top:1px solid #f1f5f9;min-width:0;">

                                {{-- Time --}}
                                <div>
                                    <div style="font-size:0.85rem;font-weight:600;color:#334155;font-family:monospace;">
                                        {{ $run->started_at->format('H:i') }}
                                        @if($run->ended_at)
                                            – {{ $run->ended_at->format('H:i') }}
                                        @else
                                            – <span style="color:#16a34a;animation:pulse 1.5s infinite;">now</span>
                                        @endif
                                    </div>
                                    <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;">
                                        @if($run->ended_at)
                                            {{ gmdate('H\h i\m', $run->ended_at->diffInSeconds($run->started_at)) }}
                                        @else
                                            Running…
                                        @endif
                                    </div>
                                </div>

                                {{-- Job --}}
                                <div style="min-width:0;">
                                    <div style="font-size:0.85rem;font-weight:600;color:#334155;truncate;">
                                        {{ $run->printJob?->customer_name ?? '—' }}
                                    </div>
                                    @if($run->printJob?->product_code)
                                        <div style="font-size:0.72rem;color:#94a3b8;margin-top:1px;font-family:monospace;">
                                            {{ $run->printJob->product_code }}
                                            @if($run->printJob->order_number)
                                                · {{ $run->printJob->order_number }}
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                {{-- Operator --}}
                                <div style="font-size:0.85rem;color:#475569;">
                                    {{ $run->user?->name ?? '—' }}
                                </div>

                                {{-- Packs --}}
                                <div style="text-align:right;">
                                    @if($run->packs_produced !== null)
                                        <span style="font-size:0.9rem;font-weight:700;color:#334155;">{{ number_format($run->packs_produced) }}</span>
                                        <span style="font-size:0.7rem;color:#94a3b8;"> packs</span>
                                    @elseif($run->progress_packs !== null)
                                        <span style="font-size:0.85rem;font-weight:600;color:#64748b;">~{{ number_format($run->progress_packs) }}</span>
                                        <span style="font-size:0.7rem;color:#94a3b8;"> so far</span>
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </div>

                                {{-- Status --}}
                                <div>
                                    @if($run->end_reason === 'complete')
                                        <span style="font-size:0.72rem;font-weight:700;background:#dcfce7;color:#15803d;padding:3px 9px;border-radius:9999px;">Complete</span>
                                    @elseif($run->end_reason === 'pause')
                                        <span style="font-size:0.72rem;font-weight:700;background:#fef3c7;color:#92400e;padding:3px 9px;border-radius:9999px;">Paused</span>
                                        @if($run->pause_reason)
                                            <div style="font-size:0.7rem;color:#b45309;margin-top:3px;">{{ $run->pause_reason }}</div>
                                        @endif
                                    @elseif($run->end_reason === 'handover')
                                        <span style="font-size:0.72rem;font-weight:700;background:#ede9fe;color:#5b21b6;padding:3px 9px;border-radius:9999px;">Handover</span>
                                    @elseif($run->ended_at === null)
                                        <span style="font-size:0.72rem;font-weight:700;background:#dcfce7;color:#15803d;padding:3px 9px;border-radius:9999px;display:inline-flex;align-items:center;gap:5px;">
                                            <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;animation:psRun 1.5s infinite;"></span>
                                            Running
                                        </span>
                                    @else
                                        <span style="color:#94a3b8;font-size:0.75rem;">—</span>
                                    @endif
                                </div>

                            </div>

                        @endforeach
                    </div>

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
