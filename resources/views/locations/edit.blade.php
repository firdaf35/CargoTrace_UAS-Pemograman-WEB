@extends('layouts.app')
@section('title', 'Edit location')
@section('heading', 'Edit location')

@section('content')
<x-card title="Edit {{ $location->name }}" class="max-w-xl">
    <form method="POST" action="{{ route('locations.update', $location) }}" class="space-y-3">
        @csrf @method('PUT')
        <x-field name="name" label="Name" :value="$location->name" maxlength="150" required />
        <x-field name="city" label="City" :value="$location->city" maxlength="100" required />
        <x-field name="country" label="Country" :value="$location->country" maxlength="100" required />
        <x-field name="country_code" label="Country code (ISO-2, for flags)" :value="$location->country_code" maxlength="2" />
        <x-select name="type" label="Type" :options="collect($types)->mapWithKeys(fn ($t) => [$t => $t])->all()" :value="$location->type" />
        <div class="grid grid-cols-2 gap-3">
            <x-field name="latitude" label="Latitude" type="number" step="any" :value="$location->latitude" />
            <x-field name="longitude" label="Longitude" type="number" step="any" :value="$location->longitude" />
        </div>
        <div class="flex gap-2"><button class="rounded-lg bg-sky-700 px-5 py-2 text-sm text-white hover:bg-sky-800">Save</button><a href="{{ route('locations.index') }}" class="rounded-lg border border-slate-300 px-5 py-2 text-sm">Cancel</a></div>
    </form>
</x-card>
@endsection
