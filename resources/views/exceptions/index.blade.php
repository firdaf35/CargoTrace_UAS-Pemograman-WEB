@extends('layouts.app')
@section('title', 'Exceptions')
@section('heading', 'Exceptions')

@section('content')
<x-card>
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <x-select name="status" label="Status" :options="collect($statuses)->mapWithKeys(fn ($s) => [$s => $s])->all()" :value="request('status')" placeholder="All" />
        <x-select name="severity" label="Severity" :options="collect($severities)->mapWithKeys(fn ($s) => [$s => $s])->all()" :value="request('severity')" placeholder="All" />
        <button class="rounded-lg bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800">Filter</button>
        <a href="{{ route('exceptions.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Reset</a>
    </form>
</x-card>

<x-card>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="bg-slate-100 text-slate-600"><tr>
                @foreach (['Shipment', 'Type', 'Severity', 'Status', 'Detected', 'Resolved', 'Action'] as $h)<th class="px-3 py-2 font-medium">{{ $h }}</th>@endforeach
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $ex)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="px-3 py-2.5"><a class="font-medium text-sky-700 hover:underline" href="{{ route('shipments.show', $ex->shipment_id) }}">{{ $ex->shipment->shipment_number }}</a><div class="text-xs text-slate-500">{{ $ex->shipment->customer->company ?: $ex->shipment->customer->name }}</div></td>
                        <td class="px-3 py-2.5">{{ $ex->type->label() }}<div class="max-w-xs text-xs text-slate-500">{{ $ex->description }}</div></td>
                        <td class="px-3 py-2.5"><x-status-badge :status="$ex->severity" /></td>
                        <td class="px-3 py-2.5"><x-status-badge :status="$ex->status" /></td>
                        <td class="px-3 py-2.5">{{ $ex->detected_at->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2.5">{{ $ex->resolved_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-3 py-2.5">@include('exceptions._update', ['ex' => $ex])</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-10 text-center text-slate-500">No exceptions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-card>
@endsection
