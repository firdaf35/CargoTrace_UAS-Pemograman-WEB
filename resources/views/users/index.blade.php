@extends('layouts.app')
@section('title', 'Users')
@section('heading', 'Users')

@section('content')
@php $roleOpts = collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all(); @endphp
<x-card title="Add user">
    <form method="POST" action="{{ route('users.store') }}" class="grid gap-3 md:grid-cols-6">
        @csrf
        <x-field name="name" label="Name" maxlength="120" required />
        <x-field name="company" label="Company" maxlength="150" />
        <x-field name="email" label="Email" type="email" required />
        <x-field name="username" label="Username" maxlength="50" />
        <x-select name="role" label="Role" :options="$roleOpts" value="customer" />
        <x-field name="password" label="Password" type="password" required />
        <div class="flex items-end"><button class="w-full rounded-lg bg-sky-700 py-2 text-sm font-medium text-white hover:bg-sky-800">Add</button></div>
    </form>
</x-card>

<x-card>
    <div class="overflow-x-auto">
    <table class="w-full min-w-[640px] text-left text-sm">
        <thead class="bg-slate-100 text-slate-600"><tr>@foreach (['Name', 'Email', 'Company', 'Role', ''] as $h)<th class="px-3 py-2 font-medium">{{ $h }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($users as $u)
                <tr>
                    <td class="px-3 py-2.5 font-medium">{{ $u->name }}</td><td class="px-3 py-2.5">{{ $u->email }}</td><td class="px-3 py-2.5">{{ $u->company ?? '—' }}</td>
                    <td class="px-3 py-2.5">{{ $u->role->label() }}</td>
                    <td class="px-3 py-2.5 text-right">
                        <a href="{{ route('users.edit', $u) }}" class="text-sky-700 hover:underline">Edit</a>
                        @if ($u->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $u) }}" class="ml-2 inline" data-confirm="Delete user?">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-card>
@endsection
