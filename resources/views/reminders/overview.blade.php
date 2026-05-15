<x-layout title="Reminders Overview — Lockie Portal">
<main class="max-w-screen-xl mx-auto px-6 py-10">

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Reminders Overview</h1>
            <p class="text-slate-500 mt-1 text-sm">Progress breakdown by month for {{ $year }}.</p>
        </div>

        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <form method="GET" action="{{ route('reminders.overview') }}" style="display:flex;align-items:center;gap:0.5rem;">
                <select name="year" onchange="this.form.submit()"
                    style="border:1px solid #e2e8f0;border-radius:8px;padding:6px 10px;font-size:0.875rem;color:#334155;background:#fff;cursor:pointer;">
                    @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('reminders.index') }}"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.4rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:0.8125rem;font-weight:600;text-decoration:none;">
                ← Back to Reminders
            </a>
        </div>
    </div>

    @if(empty($byMonth))
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:3rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
        No reminder data for {{ $year }}.
    </div>
    @else

    {{-- Status colour legend --}}
    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-bottom:1.25rem;align-items:center;">
        <span style="font-size:0.7rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-right:0.25rem;">Status:</span>
        @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
        @php $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#fff','text'=>'#334155']; @endphp
        <span style="display:inline-flex;align-items:center;padding:0.2rem 0.625rem;border-radius:9999px;font-size:0.7rem;font-weight:500;background:{{ $colours['bg'] }};color:{{ $colours['text'] }};outline:1px solid rgba(0,0,0,0.08);">
            {{ $label }}
        </span>
        @endforeach
    </div>

    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.625rem 1rem;text-align:left;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;left:0;background:#f8fafc;z-index:2;">Month</th>
                        <th style="padding:0.625rem 0.75rem;text-align:right;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e2e8f0;white-space:nowrap;">Total</th>
                        @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                        @php $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#fff','text'=>'#334155']; @endphp
                        <th style="padding:0.625rem 0.75rem;text-align:right;font-size:0.7rem;font-weight:700;color:{{ $colours['text'] }};text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e2e8f0;white-space:nowrap;background:{{ $colours['bg'] }};">
                            {{ $label }}
                        </th>
                        @endforeach
                        <th style="padding:0.625rem 0.75rem;text-align:center;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e2e8f0;white-space:nowrap;">% Ordered</th>
                        <th style="padding:0.625rem 0.75rem;border-bottom:1px solid #e2e8f0;"></th>
                    </tr>
                </thead>
                <tbody>
                    @php $totals = []; @endphp
                    @foreach(range(1, 12) as $m)
                    @php
                        $monthData = $byMonth[$m] ?? null;
                        if (!$monthData) continue;
                        $total      = array_sum($monthData);
                        $ordered    = $monthData['order_placed'] ?? 0;
                        $pct        = $total > 0 ? round($ordered / $total * 100) : 0;
                        foreach ($monthData as $s => $c) {
                            $totals[$s] = ($totals[$s] ?? 0) + $c;
                        }
                        $totals['__total'] = ($totals['__total'] ?? 0) + $total;
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="padding:0.625rem 1rem;font-weight:600;color:#334155;white-space:nowrap;position:sticky;left:0;background:#fff;z-index:1;">
                            {{ date('F', mktime(0,0,0,$m,1,$year)) }}
                        </td>
                        <td style="padding:0.625rem 0.75rem;text-align:right;font-weight:600;color:#334155;">{{ $total }}</td>
                        @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                        @php
                            $cnt = $monthData[$key] ?? 0;
                            $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#fff','text'=>'#334155'];
                        @endphp
                        <td style="padding:0.625rem 0.75rem;text-align:right;color:{{ $cnt > 0 ? $colours['text'] : '#cbd5e1' }};font-weight:{{ $cnt > 0 ? '600' : '400' }};">
                            {{ $cnt > 0 ? $cnt : '—' }}
                        </td>
                        @endforeach
                        <td style="padding:0.625rem 0.75rem;text-align:center;">
                            @if($total > 0)
                            <div style="display:flex;align-items:center;gap:0.5rem;justify-content:flex-end;">
                                <div style="width:60px;height:6px;border-radius:9999px;background:#e2e8f0;overflow:hidden;">
                                    <div style="height:100%;width:{{ $pct }}%;background:#16a34a;border-radius:9999px;"></div>
                                </div>
                                <span style="font-size:0.75rem;font-weight:600;color:#334155;min-width:32px;text-align:right;">{{ $pct }}%</span>
                            </div>
                            @endif
                        </td>
                        <td style="padding:0.5rem 0.75rem;text-align:right;">
                            <a href="{{ route('reminders.index', ['year' => $year, 'month' => $m]) }}"
                                style="font-size:0.75rem;font-weight:600;color:#0369a1;text-decoration:none;white-space:nowrap;">
                                View →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if(!empty($totals))
                <tfoot>
                    <tr style="background:#f8fafc;border-top:2px solid #e2e8f0;">
                        <td style="padding:0.625rem 1rem;font-weight:700;color:#334155;position:sticky;left:0;background:#f8fafc;">Total</td>
                        <td style="padding:0.625rem 0.75rem;text-align:right;font-weight:700;color:#334155;">{{ $totals['__total'] ?? 0 }}</td>
                        @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                        @php $cnt = $totals[$key] ?? 0; $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#fff','text'=>'#334155']; @endphp
                        <td style="padding:0.625rem 0.75rem;text-align:right;font-weight:700;color:{{ $cnt > 0 ? $colours['text'] : '#cbd5e1' }};">
                            {{ $cnt > 0 ? $cnt : '—' }}
                        </td>
                        @endforeach
                        @php
                            $grandTotal   = $totals['__total'] ?? 0;
                            $grandOrdered = $totals['order_placed'] ?? 0;
                            $grandPct     = $grandTotal > 0 ? round($grandOrdered / $grandTotal * 100) : 0;
                        @endphp
                        <td style="padding:0.625rem 0.75rem;text-align:center;">
                            <div style="display:flex;align-items:center;gap:0.5rem;justify-content:flex-end;">
                                <div style="width:60px;height:6px;border-radius:9999px;background:#e2e8f0;overflow:hidden;">
                                    <div style="height:100%;width:{{ $grandPct }}%;background:#16a34a;border-radius:9999px;"></div>
                                </div>
                                <span style="font-size:0.75rem;font-weight:700;color:#334155;min-width:32px;text-align:right;">{{ $grandPct }}%</span>
                            </div>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @endif

</main>
</x-layout>
