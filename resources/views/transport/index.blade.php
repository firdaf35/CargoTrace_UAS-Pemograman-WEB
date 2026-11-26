@extends('layouts.app')
@section('title', 'Transport Management')
@section('heading', 'Transport Management')

@section('content')
@php
    $tabs = [['', 'All Transport', 'layers'], ['sea', 'Sea Freight', 'ship'], ['air', 'Air Freight', 'plane'], ['land', 'Land Freight', 'truck']];
@endphp

<div class="grid gap-4 sm:grid-cols-3">
    @foreach ($stats as $st)
        @php $m = $st['mode']; @endphp
        <a href="{{ route('transport.index', ['mode' => $m->value]) }}" class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_2px_14px_rgba(11,22,40,.06)] transition hover:-translate-y-0.5 hover:shadow-lg">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl {{ $m->tone() }}"><i data-lucide="{{ $m->icon() }}" class="h-6 w-6"></i></span>
            <div class="mt-3 text-sm font-semibold text-slate-500">{{ $m->label() }}</div>
            <div class="text-3xl font-bold text-navy">{{ $st['total'] }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ $st['active'] }} active · {{ $st['delayed'] }} delayed</div>
            <span class="absolute -bottom-3 -right-2 text-7xl opacity-10 transition group-hover:opacity-20">{{ $m->emoji() }}</span>
        </a>
    @endforeach
</div>

<x-card>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            @foreach ($tabs as [$val, $text, $icon])
                <a href="{{ route('transport.index', array_filter(['mode' => $val, 'q' => $q])) }}" class="flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition {{ ($mode ?? '') === $val ? 'bg-ocean text-white shadow' : 'bg-mist text-slate-600 hover:bg-slate-200' }}">
                    <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>{{ $text }}
                </a>
            @endforeach
        </div>
        <form method="GET" class="flex items-center gap-2">
            @if ($mode)<input type="hidden" name="mode" value="{{ $mode }}">@endif
            <input name="q" value="{{ $q }}" maxlength="100" placeholder="Search vessel, flight, vehicle, container..." class="w-72 max-w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-ocean focus:outline-none">
            <button class="rounded-xl bg-ocean px-4 py-2 text-sm font-medium text-white hover:bg-sky-800">Search</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[960px] text-left text-sm">
            <thead class="bg-mist text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-3 py-2.5">Shipment</th>
                    <th class="px-3 py-2.5">Mode</th>
                    <th class="px-3 py-2.5">{{ match ($mode) { 'sea' => 'Vessel / Container', 'air' => 'Flight / Airline', 'land' => 'Vehicle / Driver', default => 'Vessel / Flight / Vehicle' } }}</th>
                    <th class="px-3 py-2.5">{{ match ($mode) { 'sea' => 'Origin → Destination Port', 'air' => 'Origin → Destination Airport', default => 'Origin → Destination' } }}</th>
                    <th class="px-3 py-2.5">Departure</th>
                    <th class="px-3 py-2.5">ETA</th>
                    <th class="px-3 py-2.5">Last reported location</th>
                    <th class="px-3 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($shipments as $s)
                    @php $last = $s->latestLocatedEvent(); @endphp
                    <tr class="transition hover:bg-mist/70">
                        <td class="px-3 py-3"><a href="{{ route('shipments.show', $s) }}" class="font-semibold text-ocean hover:underline">{{ $s->shipment_number }}</a><div class="text-xs text-slate-400">{{ $s->carrier->name }}</div></td>
                        <td class="px-3 py-3"><x-mode-badge :mode="$s->mode()" /></td>
                        <td class="px-3 py-3">
                            @foreach ($s->assetDetails() as $k => $v)<div class="text-xs"><span class="text-slate-400">{{ $k }}:</span> <span class="font-medium">{{ $v ?: '—' }}</span></div>@endforeach
                        </td>
                        <td class="px-3 py-3">{{ $s->origin->name }}<div class="text-xs text-slate-500">→ {{ $s->destination->name }} ({{ $s->destination->country }})</div></td>
                        <td class="whitespace-nowrap px-3 py-3">{{ $s->expected_departure->format('Y-m-d H:i') }}</td>
                        <td class="whitespace-nowrap px-3 py-3">{{ ($s->actual_arrival ?? $s->estimated_arrival)?->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-3">{{ $last?->location_name ?? '—' }}@if ($last)<div class="text-xs text-slate-400">{{ $last->occurred_at->format('Y-m-d H:i') }}</div>@endif</td>
                        <td class="px-3 py-3"><x-status-badge :status="$s->display_status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-3 py-12 text-center text-slate-500"><div class="mb-2 text-3xl">📦</div>No shipments found for this transport mode.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $shipments->links() }}</div>
</x-card>
@endsection
