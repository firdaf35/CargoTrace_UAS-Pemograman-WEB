@extends('layouts.app')
@section('title', 'Carriers')
@section('heading', 'Carriers')

@section('content')
@if (auth()->user()->isAdmin())
<x-card title="Add carrier">
    <form method="POST" action="{{ route('carriers.store') }}" class="grid gap-3 md:grid-cols-5">
        @csrf
        <x-field name="name" label="Name" maxlength="120" required />
        <x-field name="code" label="Code" maxlength="10" required />
        <x-field name="contact_email" label="Contact email" type="email" />
        <label class="flex items-end gap-2 pb-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Active</label>
        <div class="flex items-end"><button class="w-full rounded-lg bg-sky-700 py-2 text-sm font-medium text-white hover:bg-sky-800">Add</button></div>
    </form>
</x-card>
@endif

<x-card>
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-100 text-slate-600"><tr>
            @foreach (['Carrier', 'Code', 'Contact', 'Shipments', 'Delayed', 'Active', ''] as $h)<th class="px-3 py-2 font-medium">{{ $h }}</th>@endforeach
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($carriers as $c)
                <tr>
                    <td class="px-3 py-2.5 font-medium">{{ $c->name }}</td>
                    <td class="px-3 py-2.5">{{ $c->code }}</td>
                    <td class="px-3 py-2.5">{{ $c->contact_email ?? '—' }}</td>
                    <td class="px-3 py-2.5">{{ $c->shipments_count }}</td>
                    <td class="px-3 py-2.5">{{ $c->delayed_count }}</td>
                    <td class="px-3 py-2.5">{{ $c->is_active ? 'Yes' : 'No' }}</td>
                    <td class="px-3 py-2.5 text-right">
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('carriers.edit', $c) }}" class="text-sky-700 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('carriers.destroy', $c) }}" class="ml-2 inline" data-confirm="Delete carrier?">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-card>
@endsection
