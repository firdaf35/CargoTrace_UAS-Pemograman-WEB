@extends('layouts.app')
@section('title', $shipment->shipment_number)
@section('heading', $shipment->shipment_number)

@section('content')
@php $user = auth()->user(); $isStaff = $user->isStaff(); @endphp

<div class="flex flex-wrap items-center gap-3">
    <x-status-badge :status="$shipment->display_status" class="!px-3 !py-1 !text-xs" />
    <span class="text-sm text-slate-600">{{ $shipment->origin->city }} → {{ $shipment->destination->city }} · {{ $shipment->carrier->name }}</span>
    <div class="ml-auto flex gap-2">
        @if ($canEdit)<a href="{{ route('shipments.edit', $shipment) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Edit</a>@endif
        @can('delete', $shipment)
            <form method="POST" action="{{ route('shipments.destroy', $shipment) }}" data-confirm="Delete this shipment?">@csrf @method('DELETE')
                <button class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Delete</button>
            </form>
        @endcan
    </div>
</div>

<x-card title="Shipment journey" subtitle="A stage is completed only when a matching event is recorded">
    <ol class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-8">
        @foreach ($journey as $st)
            <li class="rounded-xl border p-3 text-center transition {{ $st['done'] ? 'border-emerald-200 bg-emerald-50' : ($st['current'] ? 'border-aqua bg-sky-50 ring-1 ring-aqua' : 'border-slate-100 bg-mist opacity-70') }}">
                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full {{ $st['done'] ? 'bg-emerald-500 text-white' : ($st['current'] ? 'bg-aqua text-navy' : 'bg-slate-200 text-slate-400') }}"><i data-lucide="{{ $st['icon'] }}" class="h-5 w-5"></i></span>
                <div class="mt-2 text-xs font-semibold text-navy">{{ $st['label'] }}</div>
                <div class="text-[11px] text-slate-500">{{ $st['at']?->format('d M H:i') ?? ($st['current'] ? 'Next stage' : 'Pending') }}</div>
                @if ($st['location'])<div class="truncate text-[10px] text-slate-400">{{ $st['location'] }}</div>@endif
            </li>
        @endforeach
    </ol>
</x-card>

<div class="grid gap-4 lg:grid-cols-3">
    <x-card title="Shipment details" class="lg:col-span-1">
        <dl class="space-y-2 text-sm">
            @foreach ([
                'Customer' => ($shipment->customer->company ?: $shipment->customer->name),
                'Cargo' => $shipment->cargo_type,
                'Weight' => number_format($shipment->weight_kg, 2).' kg',
                'Volume' => $shipment->volume_cbm !== null ? number_format($shipment->volume_cbm, 2).' CBM' : '—',
                'Origin' => $shipment->origin->name.', '.$shipment->origin->city,
                'Destination' => $shipment->destination->name.', '.$shipment->destination->city,
                'Expected departure' => $shipment->expected_departure->format('Y-m-d H:i'),
                'Status' => $shipment->status->label(),
            ] as $k => $v)
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $k }}</dt><dd class="text-right font-medium">{{ $v }}</dd></div>
            @endforeach
        </dl>
        <div class="mt-3"><x-mode-badge :mode="$shipment->mode()" /></div>
        <dl class="mt-2 space-y-2 text-sm">
            @foreach ($shipment->assetDetails() as $k => $v)<div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $k }}</dt><dd class="text-right font-medium">{{ $v ?: '—' }}</dd></div>@endforeach
        </dl>
        @if ($shipment->items->count())
            <h3 class="mb-1 mt-4 text-sm font-semibold">Items</h3>
            <ul class="text-sm text-slate-600">@foreach ($shipment->items as $it)<li>{{ $it->quantity }} × {{ $it->description }} @if ($it->weight_kg)({{ $it->weight_kg }} kg)@endif</li>@endforeach</ul>
        @endif
    </x-card>

    <x-card title="ETA & delay" subtitle="Deterministic rule — values used are shown below" class="lg:col-span-1">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Expected arrival</dt><dd class="font-medium">{{ $shipment->expected_arrival->format('Y-m-d H:i') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Estimated arrival (ETA)</dt><dd class="font-medium">{{ $shipment->estimated_arrival?->format('Y-m-d H:i') ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Actual arrival</dt><dd class="font-medium">{{ $shipment->actual_arrival?->format('Y-m-d H:i') ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Delay</dt><dd class="font-medium">{{ $shipment->delay_hours }}h @if ($shipment->delay_severity)<x-status-badge :status="$shipment->delay_severity" />@endif</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Flagged delayed</dt><dd class="font-medium">{{ $shipment->is_delayed ? 'Yes' : 'No' }} <span class="text-xs text-slate-400">(threshold {{ config('cargotrace.delay_threshold_hours') }}h)</span></dd></div>
        </dl>
        <p class="mt-3 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ $shipment->delay_reason }}</p>

        <div class="mt-4 rounded-lg border border-slate-200 p-3 text-sm">
            <div class="mb-1 font-semibold">Weather at {{ $weatherPlace }}</div>
            @if ($weather)
                <div>{{ $weather['condition'] }} · {{ $weather['temperature'] }}°C · wind {{ $weather['wind_speed'] }} km/h</div>
            @else
                <div class="text-slate-500">Weather data is currently unavailable.</div>
            @endif
            <div class="mt-1 text-[11px] text-slate-400">Informational only (Open-Meteo). Not used to predict delays.</div>
        </div>
    </x-card>

    <x-card title="Route map" subtitle="Origin, destination and tracking points" class="lg:col-span-1">
        <div id="shipMap" class="h-72 rounded-xl"></div>
    </x-card>
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <x-card title="Timeline" subtitle="Newest first · exceptions highlighted" class="lg:col-span-2">
        <ol class="relative space-y-4 border-l-2 border-slate-200 pl-6">
            @foreach ($shipment->trackingEvents->sortByDesc(fn ($e) => $e->occurred_at->getTimestamp().str_pad($e->id, 10, '0', STR_PAD_LEFT)) as $e)
                @php
                    $dot = $e->is_exception ? 'bg-red-500' : ($e->status_after === 'DELIVERED' && $e->event_type === 'DELIVERED' ? 'bg-emerald-500' : 'bg-sky-600');
                @endphp
                <li class="relative">
                    <span class="absolute -left-[33px] top-1 h-4 w-4 rounded-full border-2 border-white {{ $dot }}"></span>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold {{ $e->is_exception ? 'text-red-600' : 'text-slate-900' }}">{{ str_replace('_', ' ', $e->event_type) }}</span>
                        @if ($e->is_exception)<span class="rounded bg-red-600 px-2 py-0.5 text-[10px] font-bold uppercase text-white">exception</span>@endif
                        <span class="text-xs text-slate-500">{{ $e->occurred_at->format('Y-m-d H:i') }}</span>
                    </div>
                    @if ($e->location_name)<div class="text-sm text-slate-600">📍 {{ $e->location_name }}</div>@endif
                    @if ($e->description)<div class="text-sm text-slate-600">{{ $e->description }}</div>@endif
                    @if ($e->creator && $isStaff)<div class="text-xs text-slate-400">by {{ $e->creator->name }}</div>@endif
                </li>
            @endforeach
        </ol>
    </x-card>

    <div class="space-y-4">
        @if ($isStaff && count($eventTypes))
            <x-card id="add-event" title="Add tracking event" subtitle="Appended to history; invalid status jumps are rejected.">
                <form method="POST" action="{{ route('tracking.store', $shipment) }}" class="space-y-3">
                    @csrf
                    <x-select name="event_type" label="Event type" :options="collect($eventTypes)->mapWithKeys(fn ($t) => [$t => str_replace('_', ' ', $t)])->all()" placeholder="Select..." required />
                    <x-field name="occurred_at" label="Occurred at" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" />
                    <x-select name="location_id" label="Location (optional)" :options="$locations->mapWithKeys(fn ($l) => [$l->id => $l->name.' ('.$l->city.')'])->all()" placeholder="— none —" />
                    <div class="grid grid-cols-2 gap-2">
                        <x-field name="latitude" label="Latitude" type="number" step="any" />
                        <x-field name="longitude" label="Longitude" type="number" step="any" />
                    </div>
                    <x-field name="description" label="Description" maxlength="1000" />
                    <x-field name="estimated_arrival" label="Update ETA (optional)" type="datetime-local" />
                    <button class="w-full rounded-lg bg-sky-700 py-2 text-sm font-medium text-white hover:bg-sky-800">Add event</button>
                </form>
            </x-card>
        @endif

        @can('manageExceptions', $shipment)
            <x-card title="Report exception">
                <form method="POST" action="{{ route('exceptions.store', $shipment) }}" class="space-y-3">
                    @csrf
                    <x-select name="type" label="Type" :options="collect(\App\Enums\ExceptionType::values())->mapWithKeys(fn ($t) => [$t => str_replace('_', ' ', $t)])->all()" placeholder="Select..." required />
                    <x-select name="severity" label="Severity" :options="['LOW' => 'LOW', 'MEDIUM' => 'MEDIUM', 'HIGH' => 'HIGH']" placeholder="Select..." required />
                    <x-field name="description" label="Description" maxlength="1000" />
                    <button class="w-full rounded-lg bg-red-600 py-2 text-sm font-medium text-white hover:bg-red-700">Report exception</button>
                </form>
            </x-card>
        @endcan
    </div>
</div>

<x-card title="Exceptions">
    @forelse ($shipment->exceptions as $ex)
        <div class="border-b border-slate-100 py-3 last:border-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-semibold">{{ $ex->type->label() }}</span>
                <x-status-badge :status="$ex->severity" /> <x-status-badge :status="$ex->status" />
                <span class="text-xs text-slate-500">detected {{ $ex->detected_at->format('Y-m-d H:i') }}@if ($ex->resolved_at) · resolved {{ $ex->resolved_at->format('Y-m-d H:i') }} ({{ $ex->resolutionHours() }}h) @endif</span>
            </div>
            @if ($ex->description)<p class="text-sm text-slate-600">{{ $ex->description }}</p>@endif
            @if ($ex->resolution_notes)<p class="text-sm italic text-slate-500">Resolution: {{ $ex->resolution_notes }}</p>@endif
            @include('exceptions._update', ['ex' => $ex])
        </div>
    @empty
        <p class="text-sm text-slate-500">No exceptions reported for this shipment.</p>
    @endforelse
</x-card>
@endsection

@push('scripts')
<script>
(() => {
    const data = {{ \Illuminate\Support\Js::from($map) }};
    const map = L.map('shipMap').setView([10, 80], 2);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors' }).addTo(map);
    const pop = (title, lines) => { const d = document.createElement('div'); const b = document.createElement('b'); b.textContent = title; d.append(b); lines.filter(Boolean).forEach(l => { d.append(document.createElement('br'), document.createTextNode(l)); }); return d; };
    const bounds = [];
    if (data.origin) { L.circleMarker([data.origin.lat, data.origin.lng], { radius: 8, color: '#2fb36f', fillColor: '#2fb36f', fillOpacity: 1 }).addTo(map).bindPopup(pop('Origin', [data.origin.name, data.origin.city])); bounds.push([data.origin.lat, data.origin.lng]); }
    if (data.destination) { L.circleMarker([data.destination.lat, data.destination.lng], { radius: 8, color: '#f08a24', fillColor: '#f08a24', fillOpacity: 1 }).addTo(map).bindPopup(pop('Destination', [data.destination.name, data.destination.city])); bounds.push([data.destination.lat, data.destination.lng]); }
    const path = data.events.filter(e => !e.exception).map(e => [e.lat, e.lng]);
    if (path.length > 1) L.polyline(path, { color: '#1f7fb0', weight: 3 }).addTo(map);
    data.events.forEach((e, i) => {
        const last = i === data.events.length - 1;
        L.circleMarker([e.lat, e.lng], { radius: last ? 9 : 5, color: e.exception ? '#dc3b3b' : '#1f7fb0', fillColor: e.exception ? '#dc3b3b' : '#1f7fb0', fillOpacity: last ? 1 : .6 })
            .addTo(map).bindPopup(pop((last ? 'Current · ' : '') + String(e.status || e.type).replace('_', ' '), [e.time, e.location]));
        bounds.push([e.lat, e.lng]);
    });
    if (bounds.length) map.fitBounds(bounds, { padding: [24, 24], maxZoom: 6 });
})();
</script>
@endpush
