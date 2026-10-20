<x-layout title="Reports — Clocking">
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800 mb-4">Clocking</h1>
        @include('clocking._tabs', ['active' => 'reports'])
    </div>

    {{-- Controls --}}
    @php
        $tz = config('app.timezone');
        $activeEmp = request('employee_id');
        $activeFlag = request('flag', '');
        $exportParams = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
        if ($activeEmp)  $exportParams['employee_id'] = $activeEmp;
        if ($activeFlag) $exportParams['flag'] = $activeFlag;
    @endphp
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <form method="GET" action="{{ route('clocking.reports.index') }}" class="flex gap-3 flex-wrap items-end">
            <div>
                <label class="block text-xs text-slate-500 mb-1">From</label>
                <input type="date" name="from" value="{{ $from->format('Y-m-d') }}"
                    class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">To</label>
                <input type="date" name="to" value="{{ $to->format('Y-m-d') }}"
                    class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            @if($allEmployees->isNotEmpty())
            <div>
                <label class="block text-xs text-slate-500 mb-1">Employee</label>
                <select name="employee_id" onchange="this.form.submit()"
                    class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="">All employees</option>
                    @foreach($allEmployees as $emp)
                    <option value="{{ $emp->id }}" @selected($activeEmp == $emp->id)>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs text-slate-500 mb-1">Show</label>
                <select name="flag" onchange="this.form.submit()"
                    class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value=""      @selected($activeFlag === '')>All days</option>
                    <option value="late"  @selected($activeFlag === 'late')>Late arrivals</option>
                    <option value="early" @selected($activeFlag === 'early')>Left early</option>
                    <option value="any"   @selected($activeFlag === 'any')>Late or left early</option>
                </select>
            </div>
            <button type="submit"
                class="px-4 py-2 bg-indigo-500 text-white text-[0.8125rem] font-semibold rounded-lg hover:bg-indigo-600 transition-colors cursor-pointer border-0">
                Apply
            </button>
            @php
                $presets = [
                    'This week'  => [now($tz)->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'), now($tz)->format('Y-m-d')],
                    'This month' => [now($tz)->startOfMonth()->format('Y-m-d'), now($tz)->endOfMonth()->format('Y-m-d')],
                    'Last month' => [now($tz)->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'), now($tz)->subMonthNoOverflow()->endOfMonth()->format('Y-m-d')],
                    'This year'  => [now($tz)->startOfYear()->format('Y-m-d'), now($tz)->endOfYear()->format('Y-m-d')],
                ];
            @endphp
            <div class="flex gap-1.5 flex-wrap items-center">
                @foreach($presets as $label => [$pFrom, $pTo])
                @php $presetParams = ['from' => $pFrom, 'to' => $pTo];
                     if ($activeEmp)  $presetParams['employee_id'] = $activeEmp;
                     if ($activeFlag) $presetParams['flag'] = $activeFlag; @endphp
                <a href="{{ route('clocking.reports.index', $presetParams) }}"
                   class="px-2.5 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-600 no-underline whitespace-nowrap hover:bg-slate-200 transition-colors">
                    {{ $label }}
                </a>
                @endforeach
            </div>
            <a href="{{ route('clocking.reports.export', $exportParams) }}"
               class="px-3.5 py-2 bg-slate-900 text-white text-[0.8125rem] font-semibold rounded-lg no-underline whitespace-nowrap hover:bg-slate-700 transition-colors sm:ml-auto">
                ↓ Export CSV
            </a>
        </form>
        @if($defaultBreakMins > 0)
        <p class="mt-3 text-xs text-slate-400">
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
    <div class="bg-white rounded-xl border border-slate-200 mb-4 overflow-hidden">

        {{-- Employee header --}}
        <div class="flex items-center gap-3 px-5 py-4 bg-slate-50 border-b border-slate-200 cursor-pointer"
             onclick="toggleEmployee({{ $loop->index }})">
            @if($row['employee']->category)
            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $row['employee']->category->color }}"></span>
            @endif
            <span class="font-bold text-slate-800 flex-1 text-[0.9375rem]">{{ $row['employee']->name }}</span>
            @if($row['employee']->category)
            <span class="text-xs text-slate-400">{{ $row['employee']->category->name }}</span>
            @endif
            <span class="text-[0.8125rem] text-slate-600">{{ $row['days_worked'] }} day{{ $row['days_worked'] != 1 ? 's' : '' }}</span>
            <span class="font-bold text-indigo-600 text-[0.9375rem] min-w-[70px] text-right">
                {{ $totalH }}h {{ sprintf('%02d', $totalM) }}m
            </span>
            <span id="chevron-{{ $loop->index }}" class="text-slate-400 text-xs transition-transform">▼</span>
        </div>

        {{-- Day table --}}
        <div id="emp-{{ $loop->index }}" class="overflow-x-auto">
        @if(empty($row['days']))
            <p class="px-5 py-4 text-slate-400 text-sm">No data for this period.</p>
        @else
        <table class="w-full border-collapse text-[0.8125rem]" style="min-width:600px;">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/50">
                    <th class="text-left py-2 pl-5 pr-3 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">Date</th>
                    <th class="text-left py-2 px-3 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">Scheduled</th>
                    <th class="text-left py-2 px-3 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">Clock In</th>
                    <th class="text-left py-2 px-3 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">Clock Out</th>
                    <th class="text-right py-2 px-3 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">Break</th>
                    <th class="text-right py-2 px-3 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">Hours</th>
                    <th class="text-left py-2 pl-3 pr-5 text-[0.7rem] text-slate-500 uppercase tracking-wide font-semibold">Notes</th>
                </tr>
            </thead>
            <tbody>
            @foreach($row['days'] as $day)
            @php
                $bh = intdiv($day['billable_mins'], 60);
                $bm = $day['billable_mins'] % 60;
                $rowBg = $day['no_show'] ? '#fff8f8' : ($day['is_absent'] ? '#fffbf0' : '#fff');
            @endphp
            <tr class="border-b border-slate-50" style="background:{{ $rowBg }}">
                <td class="py-2 pl-5 pr-3 text-slate-800 whitespace-nowrap">
                    <span class="font-semibold">{{ $day['date']->format('d M') }}</span>
                    <span class="text-slate-400 ml-1">{{ $day['date']->format('D') }}</span>
                </td>
                <td class="py-2 px-3 text-slate-500 whitespace-nowrap">
                    @if(!empty($day['schedule']['start']))
                        {{ $day['schedule']['start'] }}@if(!empty($day['schedule']['end'])) – {{ $day['schedule']['end'] }}@endif
                    @else
                        <span class="text-slate-200">—</span>
                    @endif
                </td>
                <td class="py-2 px-3 whitespace-nowrap">
                    @if($day['first_in'])
                        <span class="text-slate-800 font-medium">{{ $day['first_in']->format('H:i') }}</span>
                    @elseif($day['is_absent'])
                        <span class="text-amber-600 text-[0.7rem] font-semibold uppercase">Absent</span>
                    @elseif($day['no_show'])
                        <span class="text-red-500 text-[0.7rem] font-semibold uppercase">No show</span>
                    @else
                        <span class="text-slate-300">—</span>
                    @endif
                </td>
                <td class="py-2 px-3 whitespace-nowrap">
                    @if($day['last_out'])
                        <span class="text-slate-800 font-medium">{{ $day['last_out']->format('H:i') }}</span>
                    @elseif($day['first_in'])
                        <span class="text-amber-500 text-[0.7rem] font-semibold">Still in</span>
                    @else
                        <span class="text-slate-300">—</span>
                    @endif
                </td>
                <td class="py-2 px-3 text-right text-slate-400">
                    {{ $day['break_mins'] > 0 ? $day['break_mins'].'m' : '—' }}
                </td>
                <td class="py-2 px-3 text-right whitespace-nowrap">
                    @if($day['billable_mins'] > 0)
                        <span class="font-semibold text-slate-800">{{ $bh }}h {{ sprintf('%02d', $bm) }}m</span>
                    @else
                        <span class="text-slate-300">—</span>
                    @endif
                </td>
                <td class="py-2 pl-3 pr-5">
                    @if($day['late_by'])
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[0.68rem] font-semibold bg-red-100 text-red-700 whitespace-nowrap mr-1">Late {{ $day['late_by'] }}m</span>
                    @endif
                    @if($day['early_by'])
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[0.68rem] font-semibold bg-amber-100 text-amber-800 whitespace-nowrap">Left early {{ $day['early_by'] }}m</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-slate-200 bg-slate-50">
                    <td colspan="5" class="py-2 pl-5 pr-3 font-bold text-slate-600 text-xs">Total</td>
                    <td class="py-2 px-3 text-right font-bold text-indigo-600">
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
    <p class="text-slate-400 text-sm">No active employees found.</p>
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
