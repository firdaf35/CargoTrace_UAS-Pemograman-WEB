@extends('layouts.app')
@section('title', 'New shipment')
@section('heading', 'New shipment')

@section('content')
<x-card title="Create shipment" subtitle="Status starts as CREATED and can only advance via validated tracking events.">
    <form method="POST" action="{{ route('shipments.store') }}" class="space-y-6">
        @csrf
        @include('shipments._form')

        <div>
            <h3 class="mb-2 text-sm font-semibold text-slate-700">Cargo items (optional)</h3>
            <div class="space-y-2">
                @for ($i = 0; $i < 3; $i++)
                    <div class="grid gap-2 sm:grid-cols-6">
                        <input name="items[{{ $i }}][description]" value="{{ old("items.$i.description") }}" placeholder="Description" maxlength="255" class="rounded-lg border border-slate-300 px-3 py-2 text-sm sm:col-span-3">
                        <input name="items[{{ $i }}][quantity]" value="{{ old("items.$i.quantity", 1) }}" type="number" min="1" placeholder="Qty" class="rounded-lg border border-slate-300 px-3 py-2 text-sm sm:col-span-1">
                        <input name="items[{{ $i }}][weight_kg]" value="{{ old("items.$i.weight_kg") }}" type="number" step="0.01" min="0" placeholder="Weight kg" class="rounded-lg border border-slate-300 px-3 py-2 text-sm sm:col-span-2">
                    </div>
                @endfor
            </div>
        </div>

        <div class="flex gap-2">
            <button class="rounded-lg bg-sky-700 px-5 py-2 text-sm font-medium text-white hover:bg-sky-800">Create shipment</button>
            <a href="{{ route('shipments.index') }}" class="rounded-lg border border-slate-300 px-5 py-2 text-sm hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</x-card>
@endsection
