@php
$map = [
    'good'        => ['Good Stock',   'bg-green-100 text-green-700'],
    'required'    => ['Stock Required','bg-amber-100 text-amber-700'],
    'overstocked' => ['Overstocked',   'bg-red-100 text-red-600'],
    'out'         => ['Out of Stock',  'bg-red-200 text-red-800'],
    'unknown'     => ['—',             'bg-slate-100 text-slate-400'],
];
[$label, $cls] = $map[$status] ?? $map['unknown'];
@endphp
<span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $cls }}">{{ $label }}</span>
