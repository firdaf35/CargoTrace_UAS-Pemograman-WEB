@extends('layouts.guest')
@section('title', 'Forgot password')

@section('content')
<h1 class="text-2xl font-bold text-navy">Forgot password?</h1>
<p class="mb-5 text-sm text-slate-500">Enter your email and we will send you a reset link.</p>
@if (session('status'))<div class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <x-field name="email" label="Email" type="email" required autofocus />
    <button class="w-full rounded-xl bg-gradient-to-r from-ocean to-aqua py-3 font-semibold text-white shadow-lg shadow-ocean/30 hover:brightness-110">Send reset link</button>
</form>
<p class="mt-4 text-center text-sm"><a href="{{ route('login') }}" class="font-medium text-ocean hover:underline">← Back to login</a></p>
@endsection
