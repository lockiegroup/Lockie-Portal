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
<div style="display:flex;gap:0.25rem;border-bottom:1px solid #e2e8f0;margin-bottom:1.5rem;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;">
@foreach($tabs as $key => $tab)
@php $isActive = ($active ?? '') === $key; @endphp
<a href="{{ route($tab['route']) }}"
   style="padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;text-decoration:none;border-bottom:2px solid {{ $isActive ? '#6366f1' : 'transparent' }};color:{{ $isActive ? '#6366f1' : '#64748b' }};margin-bottom:-1px;">
    {{ $tab['label'] }}
</a>
@endforeach
</div>
