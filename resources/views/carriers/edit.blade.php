@extends('layouts.app')
@section('title', 'Edit carrier')
@section('heading', 'Edit carrier')

@section('content')
<x-card title="Edit {{ $carrier->name }}" class="max-w-xl">
    <form method="POST" action="{{ route('carriers.update', $carrier) }}" class="space-y-3">
        @csrf @method('PUT')
        <x-field name="name" label="Name" :value="$carrier->name" maxlength="120" required />
        <x-field name="code" label="Code" :value="$carrier->code" maxlength="10" required />
        <x-field name="contact_email" label="Contact email" type="email" :value="$carrier->contact_email" />
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $carrier->is_active)) class="rounded"> Active</label>
        <div class="flex gap-2"><button class="rounded-lg bg-sky-700 px-5 py-2 text-sm text-white hover:bg-sky-800">Save</button><a href="{{ route('carriers.index') }}" class="rounded-lg border border-slate-300 px-5 py-2 text-sm">Cancel</a></div>
    </form>
</x-card>
@endsection
