@extends('layouts.guest')
@section('title', 'Reset password')

@section('content')
<h1 class="text-2xl font-bold text-navy">Reset password</h1>
<p class="mb-5 text-sm text-slate-500">Choose a new password (minimum 8 characters).</p>
<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <x-field name="email" label="Email" type="email" :value="$email" required />
    <x-field name="password" label="New password" type="password" required />
    <x-field name="password_confirmation" label="Confirm new password" type="password" required />
    <button class="w-full rounded-xl bg-gradient-to-r from-ocean to-aqua py-3 font-semibold text-white shadow-lg shadow-ocean/30 hover:brightness-110">Reset password</button>
</form>
@endsection
