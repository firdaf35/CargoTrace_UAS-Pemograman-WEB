@extends('layouts.app')
@section('title', 'Locations')
@section('heading', 'Locations')

@section('content')
<x-card title="Add location" subtitle="Leave latitude/longitude empty to geocode automatically (OpenStreetMap Nominatim).">
    <form method="POST" action="{{ route('locations.store') }}" class="grid gap-3 md:grid-cols-6">
        @csrf
        <x-field name="name" label="Name" maxlength="150" required class="md:col-span-2" />
        <x-field name="city" label="City" maxlength="100" required />
        <x-field name="country" label="Country" maxlength="100" required />
        <x-field name="country_code" label="Code (ISO-2)" maxlength="2" />
        <x-select name="type" label="Type" :options="collect($types)->mapWithKeys(fn ($t) => [$t => $t])->all()" value="PORT" />
        <div class="flex items-end"><button class="w-full rounded-lg bg-sky-700 py-2 text-sm font-medium text-white hover:bg-sky-800">Add</button></div>
        <x-field name="latitude" label="Latitude (optional)" type="number" step="any" />
        <x-field name="longitude" label="Longitude (optional)" type="number" step="any" />
    </form>
</x-card>

<x-card>
    <div class="overflow-x-auto">
    <table class="w-full min-w-[640px] text-left text-sm">
        <thead class="bg-slate-100 text-slate-600"><tr>@foreach (['Name', 'City', 'Country', 'Type', 'Coordinates', ''] as $h)<th class="px-3 py-2 font-medium">{{ $h }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($locations as $l)
                <tr>
                    <td class="px-3 py-2.5 font-medium">{{ $l->name }}</td><td class="px-3 py-2.5">{{ $l->city }}</td><td class="px-3 py-2.5">{{ $l->country }}</td>
                    <td class="px-3 py-2.5">{{ $l->type }}</td><td class="px-3 py-2.5 text-xs text-slate-500">{{ $l->latitude }}, {{ $l->longitude }}</td>
                    <td class="px-3 py-2.5 text-right">
                        <a href="{{ route('locations.edit', $l) }}" class="text-sky-700 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('locations.destroy', $l) }}" class="ml-2 inline" data-confirm="Delete location?">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="mt-4">{{ $locations->links() }}</div>
</x-card>
@endsection
