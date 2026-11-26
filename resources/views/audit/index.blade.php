@extends('layouts.app')
@section('title', 'Audit log')
@section('heading', 'Audit Log')

@section('content')
<x-card>
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <x-select name="entity" label="Entity" :options="$entities->mapWithKeys(fn ($e) => [$e => $e])->all()" :value="request('entity')" placeholder="All" />
        <x-field name="action" label="Action contains" :value="request('action')" maxlength="50" />
        <button class="rounded-lg bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800">Filter</button>
        <a href="{{ route('audit.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Reset</a>
    </form>
</x-card>
<x-card>
    <div class="overflow-x-auto">
    <table class="w-full min-w-[820px] text-left text-sm">
        <thead class="bg-slate-100 text-slate-600"><tr>@foreach (['Time', 'Actor', 'Action', 'Entity', 'Changes', 'IP'] as $h)<th class="px-3 py-2 font-medium">{{ $h }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($logs as $l)
                <tr class="align-top">
                    <td class="whitespace-nowrap px-3 py-2.5">{{ $l->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td class="px-3 py-2.5">{{ $l->user?->name ?? 'system' }}</td>
                    <td class="px-3 py-2.5 font-medium">{{ $l->action }}</td>
                    <td class="px-3 py-2.5">{{ $l->entity_type }} #{{ $l->entity_id }}</td>
                    <td class="max-w-md px-3 py-2.5 text-xs text-slate-500"><code class="break-all">{{ $l->new_values ? json_encode($l->new_values) : '' }}</code></td>
                    <td class="px-3 py-2.5 text-xs">{{ $l->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No audit entries.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-card>
@endsection
