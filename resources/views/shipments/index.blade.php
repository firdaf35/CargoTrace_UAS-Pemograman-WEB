@extends('layouts.app')
@section('title', 'Shipments')
@section('heading', 'Shipments')

@section('content')
@php
    $sortUrl = fn ($col) => request()->fullUrlWithQuery(['sort' => $col, 'dir' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc', 'page' => null]);
    $arrow = fn ($col) => $sort === $col ? ($dir === 'asc' ? '↑' : '↓') : '';
    $carrierOpts = $carriers->pluck('name', 'id')->all();
    $locOpts = $locations->mapWithKeys(fn ($l) => [$l->id => $l->name.' ('.$l->city.')'])->all();
    $statusOpts = collect($statuses)->mapWithKeys(fn ($s) => [$s => str_replace('_', ' ', $s)])->all();
@endphp

<x-card>
    <form method="GET" action="{{ route('shipments.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-field name="q" label="Shipment / reference / customer" :value="$filters['q'] ?? null" maxlength="100" />
        <x-select name="status" label="Status" :options="$statusOpts" :value="$filters['status'] ?? null" placeholder="All statuses" />
        <x-select name="mode" label="Transport" :options="['sea' => 'Sea Freight', 'air' => 'Air Freight', 'land' => 'Land Freight']" :value="$filters['mode'] ?? null" placeholder="All Transport" />
        <x-select name="carrier_id" label="Carrier" :options="$carrierOpts" :value="$filters['carrier_id'] ?? null" placeholder="All carriers" />
        <x-select name="delayed" label="Delay" :options="['1' => 'Delayed only', '0' => 'Not delayed']" :value="$filters['delayed'] ?? null" placeholder="Any" />
        <x-select name="origin_id" label="Origin" :options="$locOpts" :value="$filters['origin_id'] ?? null" placeholder="Any origin" />
        <x-select name="destination_id" label="Destination" :options="$locOpts" :value="$filters['destination_id'] ?? null" placeholder="Any destination" />
        <x-field name="from" label="Expected arrival from" type="date" :value="$filters['from'] ?? null" />
        <x-field name="to" label="Expected arrival to" type="date" :value="$filters['to'] ?? null" />
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
            <button class="rounded-lg bg-sky-700 px-4 py-2 text-sm font-medium text-white hover:bg-sky-800">Apply filters</button>
            <a href="{{ route('shipments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Reset</a>
            <a href="{{ route('shipments.export', request()->query()) }}" download class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Export CSV</a>
            @can('create', \App\Models\Shipment::class)
                <a href="{{ route('shipments.create') }}" class="ml-auto rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">+ New shipment</a>
            @endcan
        </div>
    </form>
</x-card>

<x-card>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-slate-100 text-slate-600">
                <tr>
                    <th class="px-3 py-2"><a href="{{ $sortUrl('shipment_number') }}">Shipment No. {{ $arrow('shipment_number') }}</a></th>
                    <th class="px-3 py-2">Mode</th>
                    <th class="px-3 py-2">Customer</th>
                    <th class="px-3 py-2">Carrier</th>
                    <th class="px-3 py-2">Route</th>
                    <th class="px-3 py-2"><a href="{{ $sortUrl('expected_arrival') }}">Expected {{ $arrow('expected_arrival') }}</a></th>
                    <th class="px-3 py-2">ETA</th>
                    <th class="px-3 py-2"><a href="{{ $sortUrl('delay_hours') }}">Delay {{ $arrow('delay_hours') }}</a></th>
                    <th class="px-3 py-2"><a href="{{ $sortUrl('status') }}">Status {{ $arrow('status') }}</a></th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($shipments as $s)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2.5"><a href="{{ route('shipments.show', $s) }}" class="font-medium text-sky-700 hover:underline">{{ $s->shipment_number }}</a></td>
                        <td class="px-3 py-2.5"><x-mode-badge :mode="$s->mode()" /></td>
                        <td class="px-3 py-2.5">{{ $s->customer->company ?: $s->customer->name }}</td>
                        <td class="px-3 py-2.5">{{ $s->carrier->name }}</td>
                        <td class="px-3 py-2.5">{{ $s->origin->city }} → {{ $s->destination->city }}</td>
                        <td class="px-3 py-2.5">{{ $s->expected_arrival->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2.5">{{ $s->estimated_arrival?->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2.5">{{ $s->delay_hours > 0 ? $s->delay_hours.'h' : '—' }} @if ($s->delay_severity)<x-status-badge :status="$s->delay_severity" />@endif</td>
                        <td class="px-3 py-2.5"><x-status-badge :status="$s->display_status" /></td>
                        <td class="whitespace-nowrap px-3 py-2.5 text-right text-xs">
                            <a href="{{ route('shipments.show', $s) }}" class="text-ocean hover:underline">View</a>
                            @can('update', $s)
                                <a href="{{ route('shipments.edit', $s) }}" class="ml-2 text-ocean hover:underline">Edit</a>
                                <a href="{{ route('shipments.show', $s) }}#add-event" class="ml-2 text-ocean hover:underline">Update status</a>
                            @endcan
                            @can('delete', $s)
                                <form method="POST" action="{{ route('shipments.destroy', $s) }}" class="ml-2 inline" data-confirm="Delete shipment {{ $s->shipment_number }}? This cannot be undone.">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-3 py-10 text-center text-slate-500">No shipments match your filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $shipments->links() }}</div>
</x-card>
@endsection
