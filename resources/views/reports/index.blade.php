@extends('layouts.app')
@section('title', 'Reports')
@section('heading', 'Reports')

@section('content')
<div class="grid gap-4 sm:grid-cols-3">
    <x-card><div class="text-sm text-slate-500">Overall delay rate</div><div class="text-3xl font-semibold">{{ $s['delay_rate'] }}%</div><div class="text-xs text-slate-400">{{ $s['delayed'] }} of {{ $s['total'] }} shipments (threshold {{ config('cargotrace.delay_threshold_hours') }}h)</div></x-card>
    <x-card><div class="text-sm text-slate-500">Open exception rate</div><div class="text-3xl font-semibold">{{ $s['exception_rate'] }}%</div><div class="text-xs text-slate-400">{{ $s['open_exceptions'] }} open / investigating</div></x-card>
    <x-card><div class="text-sm text-slate-500">Group KPI focus</div><div class="text-xl font-semibold">{{ config('cargotrace.group.kpi') }}</div><div class="text-xs text-slate-400">{{ config('cargotrace.group.name') }}</div></x-card>
</div>

<x-card title="Carrier performance" subtitle="Calculated from database records">
    <div class="overflow-x-auto">
    <table class="w-full min-w-[760px] text-left text-sm">
        <thead class="bg-slate-100 text-slate-600"><tr>
            @foreach (['Carrier', 'Shipments', 'Delivered', 'Delayed', 'Delay rate', 'On-time delivery rate', 'Avg delay (h)', 'Exceptions', 'Avg resolution (h)'] as $h)<th class="px-3 py-2 font-medium">{{ $h }}</th>@endforeach
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($rows as $r)
                <tr>
                    <td class="px-3 py-2.5 font-medium">{{ $r['carrier'] }}</td><td class="px-3 py-2.5">{{ $r['total'] }}</td><td class="px-3 py-2.5">{{ $r['delivered'] }}</td>
                    <td class="px-3 py-2.5">{{ $r['delayed'] }}</td><td class="px-3 py-2.5">{{ $r['delay_rate'] }}%</td>
                    <td class="px-3 py-2.5">{{ $r['on_time_rate'] !== null ? $r['on_time_rate'].'%' : '—' }}</td>
                    <td class="px-3 py-2.5">{{ $r['avg_delay_hours'] }}</td><td class="px-3 py-2.5">{{ $r['exceptions'] }}</td>
                    <td class="px-3 py-2.5">{{ $r['avg_resolution_hours'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</x-card>
@endsection
