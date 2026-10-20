@php
$isClockingAdmin = auth()->user()->can('clocking_admin');
$tabs = array_filter([
    'dashboard'  => ['label' => 'Dashboard',   'route' => 'clocking.index'],
    'timesheets' => $isClockingAdmin ? ['label' => 'Timesheets',  'route' => 'clocking.timesheets.index'] : null,
    'reports'    => ['label' => 'Reports',     'route' => 'clocking.reports.index'],
    'employees'  => $isClockingAdmin ? ['label' => 'Employees',   'route' => 'clocking.employees.index'] : null,
    'settings'   => $isClockingAdmin ? ['label' => 'Settings',    'route' => 'clocking.settings.index']  : null,
]);
@endphp
<div class="flex gap-1 border-b border-slate-200 mb-6 overflow-x-auto" style="-webkit-overflow-scrolling:touch;scrollbar-width:none;">
@foreach($tabs as $key => $tab)
@php $isActive = ($active ?? '') === $key; @endphp
<a href="{{ route($tab['route']) }}"
   class="px-4 py-2 text-sm font-semibold no-underline -mb-px border-b-2 whitespace-nowrap {{ $isActive ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
    {{ $tab['label'] }}
</a>
@endforeach
</div>
