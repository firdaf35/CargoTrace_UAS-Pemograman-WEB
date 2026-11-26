@extends('layouts.app')
@section('title', 'Edit user')
@section('heading', 'Edit user')

@section('content')
<x-card title="Edit {{ $user->name }}" class="max-w-xl">
    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-3">
        @csrf @method('PUT')
        <x-field name="name" label="Name" :value="$user->name" maxlength="120" required />
        <x-field name="company" label="Company" :value="$user->company" maxlength="150" />
        <x-field name="email" label="Email" type="email" :value="$user->email" required />
        <x-field name="username" label="Username" :value="$user->username" maxlength="50" />
        <x-select name="role" label="Role" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" :value="$user->role->value" />
        <x-field name="password" label="New password (leave blank to keep)" type="password" />
        <div class="flex gap-2"><button class="rounded-lg bg-sky-700 px-5 py-2 text-sm text-white hover:bg-sky-800">Save</button><a href="{{ route('users.index') }}" class="rounded-lg border border-slate-300 px-5 py-2 text-sm">Cancel</a></div>
    </form>
</x-card>
@endsection
