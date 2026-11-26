@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
@php
    $kpis = [
        ['Total Shipments', $s['total'], 'package', 'from-sky-500 to-sky-600'],
        ['In Transit', $s['in_transit'], 'navigation', 'from-blue-500 to-indigo-600'],
        ['Delivered', $s['delivered'], 'package-check', 'from-emerald-500 to-teal-600'],
        ['Delayed', $s['delayed'], 'timer', 'from-orange-400 to-amber-600'],
        ['Open Exceptions', $s['open_exceptions'], 'alert-triangle', 'from-rose-500 to-red-600'],
    ];
    $first = explode(' ', trim(auth()->user()->name))[0];
@endphp

{{-- Welcome hero --}}
<section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-navy via-midnight to-ocean p-6 text-white shadow-lg md:p-8">
    <div class="relative z-10 max-w-xl">
        <p class="text-sm font-medium text-aqua">{{ now()->format('l, d F Y') }}</p>
        <h2 class="mt-1 text-2xl font-bold md:text-3xl">Welcome back, {{ $first }} 👋</h2>
        <p class="mt-2 text-sm text-slate-300">You have <b class="text-white">{{ $s['in_transit'] }}</b> shipments in transit, <b class="text-white">{{ $s['delayed'] }}</b> delayed and <b class="text-white">{{ $s['open_exceptions'] }}</b> open exceptions.</p>
        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('tracking.index') }}" class="flex items-center gap-2 rounded-xl bg-aqua px-4 py-2.5 text-sm font-semibold text-navy transition hover:brightness-110"><i data-lucide="map-pin" class="h-4 w-4"></i>Track shipments</a>
            @can('create', \App\Models\Shipment::class)
                <a href="{{ route('shipments.create') }}" class="flex items-center gap-2 rounded-xl border border-white/30 px-4 py-2.5 text-sm font-semibold transition hover:bg-white/10"><i data-lucide="plus" class="h-4 w-4"></i>New shipment</a>
            @endcan
        </div>
    </div>
    <x-logistics-art class="pointer-events-none absolute -bottom-2 right-0 hidden w-[58%] max-w-[640px] opacity-95 md:block" />
</section>

{{-- KPI --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
    @foreach ($kpis as [$label, $value, $icon, $grad])
        <div class="group flex items-center gap-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_2px_14px_rgba(11,22,40,.06)] transition hover:-translate-y-0.5 hover:shadow-lg">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $grad }} text-white shadow-md"><i data-lucide="{{ $icon }}" class="h-6 w-6"></i></span>
            <div><div class="text-xs font-medium text-slate-500">{{ $label }}</div><div class="text-3xl font-bold text-navy">{{ number_format($value) }}</div></div>
        </div>
    @endforeach
</div>

<div class="grid gap-4 lg:grid-cols-12">
    <x-card title="Shipments by Status" subtitle="Overview of shipments by status" class="lg:col-span-7">
        <div class="h-64"><canvas id="statusChart"></canvas></div>
    </x-card>
    <x-card title="Carrier Performance" subtitle="On-time vs delayed shipments per carrier" class="lg:col-span-5">
        @if (count($s['carrier_chart']))
            <div class="h-64"><canvas id="carrierChart"></canvas></div>
            <p class="mt-2 text-center text-xs text-slate-500">Average delay rate: <b class="text-navy">{{ $s['delay_rate'] }}%</b></p>
        @else
            <p class="py-16 text-center text-sm text-slate-500">No carrier data yet.</p>
        @endif
    </x-card>
</div>

<div class="grid gap-4 lg:grid-cols-12">
    <x-card title="Transport Modes" subtitle="Shipments by sea, air and land freight" class="lg:col-span-7">
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($s['by_mode'] as $m)
                <a href="{{ auth()->user()->isStaff() ? route('transport.index', ['mode' => $m['mode']]) : route('shipments.index', ['mode' => $m['mode']]) }}" class="group rounded-2xl border border-slate-100 bg-mist p-4 transition hover:-translate-y-0.5 hover:border-ocean/40 hover:shadow-md">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $m['tone'] }}"><i data-lucide="{{ $m['icon'] }}" class="h-6 w-6"></i></span>
                    <div class="mt-3 text-sm font-semibold text-navy">{{ $m['label'] }}</div>
                    <div class="text-3xl font-bold text-navy">{{ $m['total'] }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ $m['active'] }} active · {{ $m['delayed'] }} delayed</div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full bg-gradient-to-r from-ocean to-aqua" style="width: {{ $s['total'] ? round($m['total'] / $s['total'] * 100) : 0 }}%"></div></div>
                </a>
            @endforeach
        </div>
    </x-card>

    <x-card title="Notifications" subtitle="Latest alerts" class="lg:col-span-5">
        <x-slot:actions><a href="{{ route('notifications.index') }}" class="text-sm text-ocean hover:underline">View all</a></x-slot:actions>
        @forelse ($s['notifications'] as $n)
            <div class="flex gap-3 border-b border-slate-100 py-2.5 last:border-0 {{ $n->read_at ? 'opacity-60' : '' }}">
                <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $n->read_at ? 'bg-slate-300' : 'bg-aqua' }}"></span>
                <div class="min-w-0"><div class="truncate text-sm font-medium">{{ $n->title }}</div><div class="line-clamp-1 text-xs text-slate-500">{{ $n->message }}</div><div class="text-[11px] text-slate-400">{{ $n->created_at->diffForHumans() }}</div></div>
            </div>
        @empty
            <p class="py-8 text-center text-sm text-slate-500">You're all caught up.</p>
        @endforelse
    </x-card>
</div>

<div class="grid gap-4 lg:grid-cols-12">
    <x-card title="Recent Shipments" subtitle="Latest shipments in the system" class="lg:col-span-8">
        <x-slot:actions>
            <div class="flex gap-2">
                <a href="{{ route('shipments.index') }}" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-mist"><i data-lucide="filter" class="h-4 w-4"></i>Filter</a>
                <a href="{{ route('shipments.export') }}" download class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-mist"><i data-lucide="download" class="h-4 w-4"></i>Export</a>
            </div>
        </x-slot:actions>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead class="bg-mist text-xs uppercase tracking-wide text-slate-500"><tr>@foreach (['Shipment No.', 'Mode', 'Customer', 'Route', 'ETA', 'Status'] as $h)<th class="px-3 py-2.5 font-semibold">{{ $h }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($s['recent'] as $r)
                        <tr class="transition hover:bg-mist/70">
                            <td class="px-3 py-3"><a href="{{ route('shipments.show', $r) }}" class="font-semibold text-ocean hover:underline">{{ $r->shipment_number }}</a></td>
                            <td class="px-3 py-3"><x-mode-badge :mode="$r->mode()" /></td>
                            <td class="px-3 py-3">{{ $r->customer->company ?: $r->customer->name }}</td>
                            <td class="px-3 py-3">{{ $r->origin->city }} → {{ $r->destination->city }}</td>
                            <td class="px-3 py-3">{{ $r->estimated_arrival?->format('Y-m-d') }}</td>
                            <td class="px-3 py-3"><x-status-badge :status="$r->display_status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No shipments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card title="Recent Activity" subtitle="Latest tracking events" class="lg:col-span-4">
        <ol class="space-y-3">
            @forelse ($s['activity'] as $a)
                <li class="flex gap-3">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $a->is_exception ? 'bg-red-100 text-red-600' : 'bg-sky-100 text-ocean' }}"><i data-lucide="{{ $a->is_exception ? 'alert-triangle' : 'map-pin' }}" class="h-4 w-4"></i></span>
                    <div class="min-w-0">
                        <div class="truncate text-sm"><a href="{{ route('shipments.show', $a->shipment_id) }}" class="font-semibold text-navy hover:text-ocean">{{ $a->shipment->shipment_number }}</a> · {{ ucwords(strtolower(str_replace('_', ' ', $a->event_type))) }}</div>
                        <div class="truncate text-xs text-slate-500">{{ $a->location_name ?? 'No location' }} · {{ $a->occurred_at->diffForHumans() }}</div>
                    </div>
                </li>
            @empty
                <li class="py-8 text-center text-sm text-slate-500">No activity yet.</li>
            @endforelse
        </ol>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const statusData = {{ \Illuminate\Support\Js::from($s['status_chart']) }};
    const carriers = {{ \Illuminate\Support\Js::from($s['carrier_chart']) }};
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';

    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: { labels: statusData.labels, datasets: [{ data: statusData.values, borderRadius: 8, maxBarThickness: 46,
            backgroundColor: ['#94a3b8', '#8b5cf6', '#6366f1', '#176B87', '#2fb36f', '#f08a24', '#dc3b3b'] }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } }, x: { grid: { display: false }, ticks: { font: { size: 11 } } } } }
    });

    const el = document.getElementById('carrierChart');
    if (el) new Chart(el, {
        type: 'bar',
        data: { labels: carriers.map(c => c.name), datasets: [
            { label: 'On time', data: carriers.map(c => c.total - c.delayed), backgroundColor: '#29C7D8', borderRadius: 6, stack: 's' },
            { label: 'Delayed', data: carriers.map(c => c.delayed), backgroundColor: '#f08a24', borderRadius: 6, stack: 's' } ] },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
            scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } } } }
    });
})();
</script>
@endpush
