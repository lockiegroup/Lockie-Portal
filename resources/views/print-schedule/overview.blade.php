<x-layout title="A1 Schedule Overview — Lockie Portal">


    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        {{-- Back link --}}
        <div style="margin-bottom:1.5rem;">
            <a href="{{ route('print.index') }}" class="text-sm text-slate-400 hover:text-slate-600 transition-colors">&#8592; Print Schedule</a>
        </div>

        {{-- Page header --}}
        <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:2rem;">
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
                    style="background:#f1f5f9;color:#64748b;font-size:0.75rem;padding:5px 10px;border-radius:6px;border:1px solid #e2e8f0;display:inline-flex;align-items:center;gap:5px;text-decoration:none;white-space:nowrap;">
                    <svg style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Edit Notes
                </a>
            @endcan
        </div>

        {{-- ── Machine breakdown ── --}}
        <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.875rem;">Machine Workload</h2>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:2.5rem;">
            @foreach($machineStats as $key => $stat)
                @php
                    $hasJobs = $stat['job_count'] > 0;
                    $hasLate = $stat['late_count'] > 0;
                    $leadDays = $stat['lead_days'];
                    // Rough colour coding: green < 5 days, amber 5-10, red > 10
                    if (!$hasJobs) {
                        $leadColour = '#16a34a'; $leadBg = '#f0fdf4'; $leadBorder = '#bbf7d0';
                    } elseif ($leadDays <= 5) {
                        $leadColour = '#16a34a'; $leadBg = '#f0fdf4'; $leadBorder = '#bbf7d0';
                    } elseif ($leadDays <= 10) {
                        $leadColour = '#d97706'; $leadBg = '#fffbeb'; $leadBorder = '#fde68a';
                    } else {
                        $leadColour = '#dc2626'; $leadBg = '#fef2f2'; $leadBorder = '#fecaca';
                    }
                @endphp
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.25rem 1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.05);">

                    {{-- Machine name --}}
                    <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.625rem;">{{ $stat['label'] }}</p>

                    {{-- Lead time - big number --}}
                    <div style="display:flex;align-items:baseline;gap:6px;margin-bottom:0.875rem;">
                        <span style="font-size:2.25rem;font-weight:700;line-height:1;color:{{ $leadColour }};">
                            {{ $hasJobs ? number_format($leadDays, 1) : '0' }}
                        </span>
                        <span style="font-size:0.8125rem;color:#94a3b8;font-weight:500;">days</span>
                    </div>

                    {{-- Stats row --}}
                    <div style="display:flex;flex-direction:column;gap:4px;font-size:0.8125rem;">
                        <div style="display:flex;justify-content:space-between;color:#64748b;">
                            <span>Jobs in queue</span>
                            <span style="font-weight:600;color:#1e293b;">{{ $stat['job_count'] }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;color:#64748b;">
                            <span>Packs remaining</span>
                            <span style="font-weight:600;color:#1e293b;">{{ number_format($stat['remaining']) }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;color:#64748b;">
                            <span>Throughput</span>
                            <span style="font-weight:500;color:#94a3b8;">{{ number_format($stat['throughput']) }} / day</span>
                        </div>
                    </div>

                    {{-- Late badge --}}
                    @if($hasLate)
                        <div style="margin-top:0.875rem;background:#fef2f2;border:1px solid #fecaca;border-radius:0.5rem;padding:5px 10px;display:flex;align-items:center;gap:6px;">
                            <svg style="width:12px;height:12px;color:#dc2626;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <span style="font-size:0.75rem;color:#dc2626;font-weight:500;">
                                {{ $stat['late_count'] }} job{{ $stat['late_count'] !== 1 ? 's' : '' }} estimated late
                            </span>
                        </div>
                    @elseif($hasJobs)
                        <div style="margin-top:0.875rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:0.5rem;padding:5px 10px;display:flex;align-items:center;gap:6px;">
                            <svg style="width:12px;height:12px;color:#16a34a;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span style="font-size:0.75rem;color:#16a34a;font-weight:500;">All jobs on track</span>
                        </div>
                    @else
                        <div style="margin-top:0.875rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;padding:5px 10px;">
                            <span style="font-size:0.75rem;color:#94a3b8;">No jobs queued</span>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>

        {{-- ── Manual notes section ── --}}
        <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.875rem;">Notes &amp; Updates</h2>

        @if($dashboardNotes && trim($dashboardNotes) !== '')
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size:0.9375rem;color:#334155;line-height:1.7;white-space:pre-wrap;word-break:break-word;">{{ $dashboardNotes }}</p>
            </div>
        @else
            <div style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:0.875rem;padding:2rem;text-align:center;">
                <p style="font-size:0.875rem;color:#94a3b8;">No notes added yet.</p>
                @can('admin')
                    <a href="{{ route('admin.print-settings.index') }}"
                        style="display:inline-block;margin-top:0.5rem;font-size:0.8125rem;color:#64748b;text-decoration:underline;">
                        Add notes in Settings
                    </a>
                @endcan
            </div>
        @endif

    </main>

</x-layout>
