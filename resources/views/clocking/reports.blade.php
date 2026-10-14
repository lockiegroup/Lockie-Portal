<x-layout title="Reports — Clocking">
<main style="max-width:1000px;width:100%;margin:0 auto;padding:2rem 1.5rem;box-sizing:border-box;">

    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">Clocking</h1>
        @include('clocking._tabs', ['active' => 'reports'])
    </div>

    {{-- Controls --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <form method="GET" action="{{ route('clocking.reports.index') }}" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label style="font-size:0.75rem;color:#64748b;display:block;margin-bottom:3px;">From</label>
                <input type="date" name="from" value="{{ $from->format('Y-m-d') }}"
                    style="padding:0.4rem 0.6rem;border:1px solid #e2e8f0;border-radius:6px;font-size:0.875rem;">
            </div>
            <div>
                <label style="font-size:0.75rem;color:#64748b;display:block;margin-bottom:3px;">To</label>
                <input type="date" name="to" value="{{ $to->format('Y-m-d') }}"
                    style="padding:0.4rem 0.6rem;border:1px solid #e2e8f0;border-radius:6px;font-size:0.875rem;">
            </div>
            <button type="submit"
                style="padding:0.4rem 1rem;background:#6366f1;color:#fff;border:none;border-radius:6px;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                Apply
            </button>
            @php
                $tz = config('app.timezone');
                $presets = [
                    'This month' => [now($tz)->startOfMonth()->format('Y-m-d'), now($tz)->endOfMonth()->format('Y-m-d')],
                    'Last month' => [now($tz)->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'), now($tz)->subMonthNoOverflow()->endOfMonth()->format('Y-m-d')],
                    'This year'  => [now($tz)->startOfYear()->format('Y-m-d'), now($tz)->endOfYear()->format('Y-m-d')],
                ];
            @endphp
            <div style="display:flex;gap:0.375rem;flex-wrap:wrap;align-items:center;">
                @foreach($presets as $label => [$pFrom, $pTo])
                <a href="{{ route('clocking.reports.index', ['from' => $pFrom, 'to' => $pTo]) }}"
                   style="padding:0.35rem 0.625rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:0.75rem;color:#475569;text-decoration:none;white-space:nowrap;">
                    {{ $label }}
                </a>
                @endforeach
            </div>
            <a href="{{ route('clocking.reports.export', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}"
               style="padding:0.4rem 0.875rem;background:#1e293b;color:#fff;border-radius:6px;font-size:0.8125rem;font-weight:600;text-decoration:none;white-space:nowrap;margin-left:auto;">
                ↓ Export CSV
            </a>
        </form>
        @if($defaultBreakMins > 0)
        <p style="margin:0.75rem 0 0;font-size:0.75rem;color:#94a3b8;">
            Default break deduction: {{ $defaultBreakMins }} min per day. Per-employee overrides apply where set.
        </p>
        @endif
    </div>

    {{-- Per-employee detail --}}
    @forelse($report as $row)
    @php
        $totalH = intdiv($row['total_mins'], 60);
        $totalM = $row['total_mins'] % 60;
    @endphp
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;margin-bottom:1rem;overflow:hidden;">

        {{-- Employee header --}}
        <div style="display:flex;align-items:center;gap:0.75rem;padding:1rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;cursor:pointer;"
             onclick="toggleEmployee({{ $loop->index }})">
            @if($row['employee']->category)
            <span style="width:10px;height:10px;border-radius:50%;background:{{ $row['employee']->category->color }};flex-shrink:0;"></span>
            @endif
            <span style="font-weight:700;color:#1e293b;flex:1;font-size:0.9375rem;">{{ $row['employee']->name }}</span>
            @if($row['employee']->category)
            <span style="font-size:0.75rem;color:#94a3b8;">{{ $row['employee']->category->name }}</span>
            @endif
            <span style="font-size:0.8125rem;color:#475569;">{{ $row['days_worked'] }} day{{ $row['days_worked'] != 1 ? 's' : '' }}</span>
            <span style="font-weight:700;color:#6366f1;font-size:0.9375rem;min-width:70px;text-align:right;">
                {{ $totalH }}h {{ sprintf('%02d', $totalM) }}m
            </span>
            <span id="chevron-{{ $loop->index }}" style="color:#94a3b8;font-size:0.75rem;transition:transform 0.15s;">▼</span>
        </div>

        {{-- Day table --}}
        <div id="emp-{{ $loop->index }}" style="overflow-x:auto;">
        @if(empty($row['days']))
            <p style="padding:1rem 1.25rem;color:#94a3b8;font-size:0.875rem;margin:0;">No data for this period.</p>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;min-width:600px;">
            <thead>
                <tr style="border-bottom:1px solid #e2e8f0;color:#64748b;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;background:#fafafa;">
                    <th style="text-align:left;padding:0.5rem 0.75rem 0.5rem 1.25rem;font-weight:600;">Date</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-weight:600;">Scheduled</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-weight:600;">Clock In</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-weight:600;">Clock Out</th>
                    <th style="text-align:right;padding:0.5rem 0.75rem;font-weight:600;">Break</th>
                    <th style="text-align:right;padding:0.5rem 0.75rem;font-weight:600;">Hours</th>
                    <th style="text-align:left;padding:0.5rem 1.25rem 0.5rem 0.75rem;font-weight:600;">Notes</th>
                </tr>
            </thead>
            <tbody>
            @foreach($row['days'] as $day)
            @php
                $bh = intdiv($day['billable_mins'], 60);
                $bm = $day['billable_mins'] % 60;
                $rowBg = $day['no_show'] ? '#fff8f8' : ($day['is_absent'] ? '#fffbf0' : '#fff');
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;background:{{ $rowBg }};">
                <td style="padding:0.5rem 0.75rem 0.5rem 1.25rem;color:#1e293b;white-space:nowrap;">
                    <span style="font-weight:600;">{{ $day['date']->format('d M') }}</span>
                    <span style="color:#94a3b8;margin-left:4px;">{{ $day['date']->format('D') }}</span>
                </td>
                <td style="padding:0.5rem 0.75rem;color:#64748b;white-space:nowrap;">
                    @if(!empty($day['schedule']['start']))
                        {{ $day['schedule']['start'] }}@if(!empty($day['schedule']['end'])) – {{ $day['schedule']['end'] }}@endif
                    @else
                        <span style="color:#e2e8f0;">—</span>
                    @endif
                </td>
                <td style="padding:0.5rem 0.75rem;white-space:nowrap;">
                    @if($day['first_in'])
                        <span style="color:#1e293b;font-weight:500;">{{ $day['first_in']->format('H:i') }}</span>
                    @elseif($day['is_absent'])
                        <span style="color:#d97706;font-size:0.7rem;font-weight:600;text-transform:uppercase;">Absent</span>
                    @elseif($day['no_show'])
                        <span style="color:#ef4444;font-size:0.7rem;font-weight:600;text-transform:uppercase;">No show</span>
                    @else
                        <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
                <td style="padding:0.5rem 0.75rem;white-space:nowrap;">
                    @if($day['last_out'])
                        <span style="color:#1e293b;font-weight:500;">{{ $day['last_out']->format('H:i') }}</span>
                    @elseif($day['first_in'])
                        <span style="color:#f59e0b;font-size:0.7rem;font-weight:600;">Still in</span>
                    @else
                        <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
                <td style="padding:0.5rem 0.75rem;text-align:right;color:#94a3b8;">
                    {{ $day['break_mins'] > 0 ? $day['break_mins'].'m' : '—' }}
                </td>
                <td style="padding:0.5rem 0.75rem;text-align:right;white-space:nowrap;">
                    @if($day['billable_mins'] > 0)
                        <span style="font-weight:600;color:#1e293b;">{{ $bh }}h {{ sprintf('%02d', $bm) }}m</span>
                    @else
                        <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
                <td style="padding:0.5rem 1.25rem 0.5rem 0.75rem;">
                    @if($day['late_by'])
                        <span style="background:#fee2e2;color:#b91c1c;padding:1px 6px;border-radius:99px;font-size:0.68rem;font-weight:600;white-space:nowrap;margin-right:3px;">Late {{ $day['late_by'] }}m</span>
                    @endif
                    @if($day['early_by'])
                        <span style="background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:99px;font-size:0.68rem;font-weight:600;white-space:nowrap;">Left early {{ $day['early_by'] }}m</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid #e2e8f0;background:#f8fafc;">
                    <td colspan="5" style="padding:0.5rem 0.75rem 0.5rem 1.25rem;font-weight:700;color:#475569;font-size:0.75rem;">Total</td>
                    <td style="padding:0.5rem 0.75rem;text-align:right;font-weight:700;color:#6366f1;">
                        {{ $totalH }}h {{ sprintf('%02d', $totalM) }}m
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        @endif
        </div>
    </div>
    @empty
    <p style="color:#94a3b8;font-size:0.875rem;">No active employees found.</p>
    @endforelse

</main>

<script>
function toggleEmployee(idx) {
    const el  = document.getElementById('emp-' + idx);
    const ch  = document.getElementById('chevron-' + idx);
    const open = el.style.display !== 'none';
    el.style.display  = open ? 'none' : '';
    ch.style.transform = open ? 'rotate(-90deg)' : '';
}
</script>
</x-layout>
