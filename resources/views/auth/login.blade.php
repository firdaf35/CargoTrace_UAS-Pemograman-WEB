@extends('layouts.guest')
@section('title', 'Sign in')

@section('content')
<h1 class="text-2xl font-bold text-navy">Welcome back</h1>
<p class="mb-6 text-sm text-slate-500">Sign in to manage and track your shipments.</p>

@if (session('success'))<div class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('success') }}</div>@endif

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <label class="block text-sm">
        <span class="mb-1 block font-medium text-slate-700">Email or username</span>
        <div class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 focus-within:border-ocean focus-within:ring-1 focus-within:ring-ocean">
            <i data-lucide="user" class="h-4 w-4 text-slate-400"></i>
            <input name="login" value="{{ old('login') }}" required autofocus autocomplete="username" class="w-full bg-transparent py-2.5 text-sm focus:outline-none" placeholder="you@company.com">
        </div>
        @error('login')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
    </label>

    <label class="block text-sm">
        <span class="mb-1 block font-medium text-slate-700">Password</span>
        <div class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 focus-within:border-ocean focus-within:ring-1 focus-within:ring-ocean">
            <i data-lucide="lock" class="h-4 w-4 text-slate-400"></i>
            <input id="pw" type="password" name="password" required autocomplete="current-password" class="w-full bg-transparent py-2.5 text-sm focus:outline-none" placeholder="••••••••">
            <button type="button" id="togglePw" class="text-slate-400 hover:text-ocean" aria-label="Show or hide password"><i data-lucide="eye" class="h-4 w-4"></i></button>
        </div>
        @error('password')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
    </label>

    <div class="flex items-center justify-between text-sm">
        <label class="flex items-center gap-2 text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300 text-ocean"> Remember me</label>
        <a href="{{ route('password.request') }}" class="font-medium text-ocean hover:underline">Forgot password?</a>
    </div>

    <button id="loginBtn" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-ocean to-aqua py-3 font-semibold text-white shadow-lg shadow-ocean/30 transition hover:brightness-110">
        <span>Login</span>
    </button>
</form>

<p class="mt-5 text-center text-sm text-slate-500">Customer without an account? <a href="{{ route('register') }}" class="font-medium text-ocean hover:underline">Register</a></p>
<div class="mt-5 rounded-xl bg-mist p-3 text-xs text-slate-500">
    <div class="font-semibold text-slate-600">Demo accounts (password: <code>password</code>)</div>
    admin · operator · customer (or <code>@cargotrace.test</code> emails)
</div>
@endsection

@push('scripts')
<script>
document.getElementById('togglePw').addEventListener('click', () => {
    const i = document.getElementById('pw'); const show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    document.querySelector('#togglePw i').setAttribute('data-lucide', show ? 'eye-off' : 'eye');
    lucide.createIcons();
});
document.querySelector('form').addEventListener('submit', () => {
    const b = document.getElementById('loginBtn'); b.disabled = true;
    b.innerHTML = '<svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4"/></svg><span>Signing in...</span>';
});
</script>
@endpush
