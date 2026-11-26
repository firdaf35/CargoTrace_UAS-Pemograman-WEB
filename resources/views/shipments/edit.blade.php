@extends('layouts.app')
@section('title', 'Edit shipment')
@section('heading', 'Edit '.$shipment->shipment_number)

@section('content')
<x-card title="Edit shipment" subtitle="Status is not editable here. Use tracking events on the shipment page.">
    <form method="POST" action="{{ route('shipments.update', $shipment) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('shipments._form')
        <div class="flex gap-2">
            <button class="rounded-lg bg-sky-700 px-5 py-2 text-sm font-medium text-white hover:bg-sky-800">Save changes</button>
            <a href="{{ route('shipments.show', $shipment) }}" class="rounded-lg border border-slate-300 px-5 py-2 text-sm hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</x-card>
@endsection
