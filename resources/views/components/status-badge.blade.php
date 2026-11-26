@props(['status'])
@php
    $s = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $map = [
        'DELAYED' => 'bg-red-600', 'IN_TRANSIT' => 'bg-sky-600', 'DELIVERED' => 'bg-emerald-600',
        'PICKED_UP' => 'bg-indigo-600', 'BOOKED' => 'bg-violet-600', 'CREATED' => 'bg-slate-500', 'CANCELLED' => 'bg-gray-500',
        'OPEN' => 'bg-red-600', 'INVESTIGATING' => 'bg-amber-500', 'RESOLVED' => 'bg-emerald-600',
        'LOW' => 'bg-sky-500', 'MEDIUM' => 'bg-amber-500', 'HIGH' => 'bg-red-600',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-block whitespace-nowrap rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white '.($map[$s] ?? 'bg-slate-500')]) }}>{{ str_replace('_', ' ', $s) }}</span>
