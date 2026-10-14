<x-layout title="Reports — Clocking">
<main style="max-width:900px;width:100%;margin:0 auto;padding:2rem 1.5rem;box-sizing:border-box;">

    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">Clocking</h1>
        @include('clocking._tabs', ['active' => 'reports'])
    </div>

    {{-- Date range picker --}}
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
            {{-- Quick presets --}}
            <div style="display:flex;gap:0.375rem;flex-wrap:wrap;align-items:center;">
                @php
                    $tz = config('app.timezone');
                    $presets = [
                        'This month'  => [now($tz)->startOfMonth()->format('Y-m-d'), now($tz)->endOfMonth()->format('Y-m-d')],
                        'Last month'  => [now($tz)->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'), now($tz)->subMonthNoOverflow()->endOfMonth()->format('Y-m-d')],
                        'This year'   => [now($tz)->startOfYear()->format('Y-m-d'), now($tz)->endOfYear()->format('Y-m-d')],
                    ];
                @endphp
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
    </div>

    {{-- Summary table --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;">
        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem;">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0;">Summary</h2>
            <span style="font-size:0.8125rem;color:#64748b;">
                {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
            </span>
        </div>

        @if($summary->isEmpty())
            <p style="color:#94a3b8;font-size:0.875rem;">No employees found.</p>
        @else
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;min-width:480px;">
            <thead>
                <tr style="border-bottom:1px solid #e2e8f0;color:#64748b;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">
                    <th style="text-align:left;padding:0.5rem 0.75rem 0.5rem 0;">Employee</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;">Category</th>
                    <th style="text-align:right;padding:0.5rem 0.75rem;">Days</th>
                    <th style="text-align:right;padding:0.5rem 0;">Total Hours</th>
                </tr>
            </thead>
            <tbody>
            @php $grandTotal = $summary->sum('total_mins'); @endphp
            @foreach($summary->sortBy('employee.name') as $row)
            @php
                $h = intdiv($row['total_mins'], 60);
                $m = $row['total_mins'] % 60;
                $pct = $grandTotal > 0 ? round($row['total_mins'] / $grandTotal * 100) : 0;
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:0.625rem 0.75rem 0.625rem 0;font-weight:600;color:#1e293b;">
                    {{ $row['employee']->name }}
                </td>
                <td style="padding:0.625rem 0.75rem;color:#64748b;">
                    @if($row['employee']->category)
                        <span style="display:inline-flex;align-items:center;gap:5px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $row['employee']->category->color }};flex-shrink:0;"></span>
                            {{ $row['employee']->category->name }}
                        </span>
                    @else
                        <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
                <td style="padding:0.625rem 0.75rem;text-align:right;color:#475569;">{{ $row['days'] }}</td>
                <td style="padding:0.625rem 0;text-align:right;">
                    @if($row['total_mins'] > 0)
                        <span style="font-weight:600;color:#1e293b;">{{ $h }}h {{ sprintf('%02d', $m) }}m</span>
                        <div style="margin-top:3px;background:#f1f5f9;border-radius:99px;height:4px;width:80px;margin-left:auto;">
                            <div style="background:#6366f1;border-radius:99px;height:4px;width:{{ $pct }}%;"></div>
                        </div>
                    @else
                        <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid #e2e8f0;">
                    <td colspan="2" style="padding:0.625rem 0.75rem 0.5rem 0;font-weight:700;color:#1e293b;font-size:0.8125rem;">Total</td>
                    <td style="padding:0.625rem 0.75rem;text-align:right;font-weight:700;color:#1e293b;">{{ $summary->sum('days') }}</td>
                    <td style="padding:0.625rem 0;text-align:right;font-weight:700;color:#1e293b;">
                        {{ intdiv($grandTotal, 60) }}h {{ sprintf('%02d', $grandTotal % 60) }}m
                    </td>
                </tr>
            </tfoot>
        </table>
        </div>
        @endif
    </div>

</main>
</x-layout>
