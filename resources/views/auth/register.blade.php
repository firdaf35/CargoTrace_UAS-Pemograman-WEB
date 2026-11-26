@extends('layouts.guest')
@section('title', 'Register')

@section('content')
<h1 class="text-2xl font-bold text-navy">Create customer account</h1>
<p class="mb-5 text-sm text-slate-500">Track your own shipments in CargoTrace.</p>
<form method="POST" action="{{ route('register') }}" class="space-y-3">
    @csrf
    <x-field name="name" label="Full name" required />
    <x-field name="company" label="Company (optional)" />
    <x-field name="email" label="Email" type="email" required />
    <x-field name="password" label="Password (min 8 chars)" type="password" required />
    <x-field name="password_confirmation" label="Confirm password" type="password" required />
    <button class="w-full rounded-xl bg-gradient-to-r from-ocean to-aqua py-3 font-semibold text-white shadow-lg shadow-ocean/30 hover:brightness-110">Register</button>
</form>
<p class="mt-4 text-center text-sm text-slate-500">Already registered? <a href="{{ route('login') }}" class="font-medium text-ocean hover:underline">Sign in</a></p>
@endsection
