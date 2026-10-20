<x-layout title="Reminders Overview — Lockie Portal">
<main class="max-w-screen-xl mx-auto px-6 py-10">

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Reminders Overview</h1>
            <p class="text-slate-500 mt-1 text-sm">Progress breakdown by month for {{ $year }}.</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <form method="GET" action="{{ route('reminders.overview') }}" class="flex items-center gap-2">
                <select name="year" onchange="this.form.submit()"
                    class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-sm text-slate-700 bg-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-sky-500">
                    @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('reminders.index') }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-[0.8125rem] font-semibold no-underline hover:bg-slate-50 transition-colors">
                ← Back to Reminders
            </a>
        </div>
    </div>

    @if(empty($byMonth))
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-12 text-center text-slate-400 text-sm">
        No reminder data for {{ $year }}.
    </div>
    @else

    {{-- Status colour legend --}}
    <div class="flex flex-wrap gap-1.5 mb-5 items-center">
        <span class="text-[0.7rem] font-semibold text-slate-400 uppercase tracking-wide mr-1">Status:</span>
        @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
        @php $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#fff','text'=>'#334155']; @endphp
        <span style="background:{{ $colours['bg'] }};color:{{ $colours['text'] }};" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[0.7rem] font-medium outline outline-1 outline-black/[0.08]">
            {{ $label }}
        </span>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[0.8125rem] border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-4 py-2.5 text-left text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide border-b border-slate-200 whitespace-nowrap sticky left-0 bg-slate-50 z-[2]">Month</th>
                        <th class="px-3 py-2.5 text-right text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide border-b border-slate-200 whitespace-nowrap">Total</th>
                        @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                        @php $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#fff','text'=>'#334155']; @endphp
                        <th style="background:{{ $colours['bg'] }};color:{{ $colours['text'] }};" class="px-3 py-2.5 text-right text-[0.7rem] font-bold uppercase tracking-wide border-b border-slate-200 whitespace-nowrap">
                            {{ $label }}
                        </th>
                        @endforeach
                        <th class="px-3 py-2.5 text-center text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide border-b border-slate-200 whitespace-nowrap">% Ordered</th>
                        <th class="px-3 py-2.5 border-b border-slate-200"></th>
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
                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-2.5 font-semibold text-slate-700 whitespace-nowrap sticky left-0 bg-white z-[1]">
                            {{ date('F', mktime(0,0,0,$m,1,$year)) }}
                        </td>
                        <td class="px-3 py-2.5 text-right font-semibold text-slate-700">{{ $total }}</td>
                        @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                        @php
                            $cnt = $monthData[$key] ?? 0;
                            $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#fff','text'=>'#334155'];
                        @endphp
                        <td style="color:{{ $cnt > 0 ? $colours['text'] : '#cbd5e1' }};" class="px-3 py-2.5 text-right {{ $cnt > 0 ? 'font-semibold' : '' }}">
                            {{ $cnt > 0 ? $cnt : '—' }}
                        </td>
                        @endforeach
                        <td class="px-3 py-2.5 text-center">
                            @if($total > 0)
                            <div class="flex items-center gap-2 justify-end">
                                <div class="w-15 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                    <div style="width:{{ $pct }}%;" class="h-full bg-green-600 rounded-full"></div>
                                </div>
                                <span class="text-xs font-semibold text-slate-700 min-w-8 text-right">{{ $pct }}%</span>
                            </div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right">
                            <a href="{{ route('reminders.index', ['year' => $year, 'month' => $m]) }}"
                                class="text-xs font-semibold text-sky-700 no-underline whitespace-nowrap hover:text-sky-900 transition-colors">
                                View →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if(!empty($totals))
                <tfoot>
                    <tr class="bg-slate-50 border-t-2 border-slate-200">
                        <td class="px-4 py-2.5 font-bold text-slate-700 sticky left-0 bg-slate-50">Total</td>
                        <td class="px-3 py-2.5 text-right font-bold text-slate-700">{{ $totals['__total'] ?? 0 }}</td>
                        @foreach(\App\Models\ReminderEntry::STATUSES as $key => $label)
                        @php $cnt = $totals[$key] ?? 0; $colours = \App\Models\ReminderEntry::STATUS_COLOURS[$key] ?? ['bg'=>'#fff','text'=>'#334155']; @endphp
                        <td style="color:{{ $cnt > 0 ? $colours['text'] : '#cbd5e1' }};" class="px-3 py-2.5 text-right font-bold">
                            {{ $cnt > 0 ? $cnt : '—' }}
                        </td>
                        @endforeach
                        @php
                            $grandTotal   = $totals['__total'] ?? 0;
                            $grandOrdered = $totals['order_placed'] ?? 0;
                            $grandPct     = $grandTotal > 0 ? round($grandOrdered / $grandTotal * 100) : 0;
                        @endphp
                        <td class="px-3 py-2.5 text-center">
                            <div class="flex items-center gap-2 justify-end">
                                <div class="w-15 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                    <div style="width:{{ $grandPct }}%;" class="h-full bg-green-600 rounded-full"></div>
                                </div>
                                <span class="text-xs font-bold text-slate-700 min-w-8 text-right">{{ $grandPct }}%</span>
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
