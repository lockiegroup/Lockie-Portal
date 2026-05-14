<x-layout title="Dashboard — Lockie Portal">

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        <div style="margin-bottom:2rem;">
            <h1 class="text-2xl font-bold text-slate-800">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}</h1>
            <p class="text-slate-500 mt-1">Here's what's happening today.</p>
        </div>

        {{-- ── Key Actions ── --}}
        @if($myTasks !== null)
            <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.875rem;">My Key Actions</h2>

            @if($myTasks->isEmpty())
                <div style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:0.875rem;padding:2rem;text-align:center;margin-bottom:2.5rem;">
                    <p style="font-size:0.875rem;color:#94a3b8;">No open tasks assigned to you.</p>
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:2.5rem;">
                    @foreach($myTasks as $groupId => $tasks)
                        @php $group = $tasks->first()->group; @endphp
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.25rem 1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.875rem;">
                                <a href="{{ route('key-actions.show', $group) }}"
                                   style="font-size:0.875rem;font-weight:600;color:#1e293b;text-decoration:none;">
                                    {{ $group->name }}
                                </a>
                                <span style="font-size:0.75rem;color:#94a3b8;font-weight:500;">{{ $tasks->count() }} task{{ $tasks->count() !== 1 ? 's' : '' }}</span>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:6px;">
                                @foreach($tasks as $task)
                                    @php
                                        $labelColours = [
                                            'yellow' => ['bg' => '#fefce8', 'border' => '#fde68a', 'dot' => '#ca8a04'],
                                            'red'    => ['bg' => '#fef2f2', 'border' => '#fecaca', 'dot' => '#dc2626'],
                                            'green'  => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'dot' => '#16a34a'],
                                        ];
                                        $lc = $labelColours[$task->label] ?? null;
                                    @endphp
                                    <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;background:#f8fafc;border:1px solid #f1f5f9;">
                                        @if($lc)
                                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $lc['dot'] }};flex-shrink:0;"></span>
                                        @else
                                            <span style="width:8px;height:8px;border-radius:50%;background:#e2e8f0;flex-shrink:0;"></span>
                                        @endif
                                        <span style="font-size:0.875rem;color:#334155;flex:1;">{{ $task->title }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- ── Print Schedule ── --}}
        @if($printStats !== null)
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.875rem;">
                <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Print Schedule</h2>
                <a href="{{ route('print.overview') }}" style="font-size:0.75rem;color:#64748b;text-decoration:none;">View full overview &rarr;</a>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:2.5rem;">
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
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.5rem;">{{ $stat['label'] }}</p>
                        <div style="display:flex;align-items:baseline;gap:5px;margin-bottom:0.625rem;">
                            <span style="font-size:1.875rem;font-weight:700;line-height:1;color:{{ $leadColour }};">{{ $hasJobs ? number_format($leadDays, 1) : '0' }}</span>
                            <span style="font-size:0.8125rem;color:#94a3b8;font-weight:500;">day lead</span>
                        </div>
                        <div style="font-size:0.8125rem;color:#64748b;">
                            <span style="font-weight:600;color:#1e293b;">{{ $stat['job_count'] }}</span> job{{ $stat['job_count'] !== 1 ? 's' : '' }}
                            @if($hasLate)
                                &bull; <span style="color:#dc2626;font-weight:600;">{{ $stat['late_count'] }} late</span>
                            @elseif($hasJobs)
                                &bull; <span style="color:#16a34a;">all on track</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Fallback if nothing to show --}}
        @if($myTasks === null && $printStats === null)
            <div style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:0.875rem;padding:3rem;text-align:center;">
                <p style="font-size:0.9375rem;color:#94a3b8;">Nothing to show here yet — use the sidebar to navigate.</p>
            </div>
        @endif

    </main>

</x-layout>
