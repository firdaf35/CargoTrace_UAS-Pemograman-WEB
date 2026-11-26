@extends('layouts.app')
@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
<div class="grid gap-4 lg:grid-cols-3">
    <x-card title="My profile" subtitle="Update your name, company and password" class="lg:col-span-2">
        <form method="POST" action="{{ route('settings.profile') }}" class="grid gap-3 md:grid-cols-2">
            @csrf @method('PUT')
            <x-field name="name" label="Full name" :value="$user->name" maxlength="120" required />
            <x-field name="company" label="Company" :value="$user->company" maxlength="150" />
            <label class="block text-sm"><span class="mb-1 block font-medium text-slate-700">Email / username</span>
                <input value="{{ $user->email }}{{ $user->username ? ' · '.$user->username : '' }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500"></label>
            <label class="block text-sm"><span class="mb-1 block font-medium text-slate-700">Role</span>
                <input value="{{ $user->role->label() }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500"></label>
            <div class="md:col-span-2 mt-2 border-t pt-4 text-sm font-semibold text-navy">Change password (optional)</div>
            <x-field name="current_password" label="Current password" type="password" autocomplete="current-password" />
            <div></div>
            <x-field name="password" label="New password (min 8 chars)" type="password" autocomplete="new-password" />
            <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" />
            <div class="md:col-span-2"><button class="rounded-xl bg-gradient-to-r from-ocean to-aqua px-6 py-2.5 text-sm font-semibold text-white shadow hover:brightness-110">Save changes</button></div>
        </form>
    </x-card>

    <div class="space-y-4">
        @if ($user->isAdmin())
            <x-card title="Administration">
                <div class="space-y-2">
                    @foreach ([['users.index', 'Users & roles', 'users'], ['locations.index', 'Locations (ports, airports, hubs)', 'map'], ['audit.index', 'Audit log', 'shield-check'], ['carriers.index', 'Carriers', 'truck']] as [$r, $t, $i])
                        <a href="{{ route($r) }}" class="flex items-center gap-3 rounded-xl bg-mist px-4 py-3 text-sm font-medium transition hover:bg-sky-100"><i data-lucide="{{ $i }}" class="h-4 w-4 text-ocean"></i>{{ $t }}</a>
                    @endforeach
                </div>
            </x-card>
        @endif
        <x-card title="System parameters" subtitle="Configured in .env">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Delay threshold</dt><dd class="font-medium">{{ config('cargotrace.delay_threshold_hours') }} h</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Severity bands</dt><dd class="font-medium">≤{{ config('cargotrace.severity.low_max_hours') }}h / ≤{{ config('cargotrace.severity.medium_max_hours') }}h</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Weather provider</dt><dd class="font-medium">{{ config('services.openweather.api_key') ? 'OpenWeatherMap' : 'Open-Meteo' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Timezone</dt><dd class="font-medium">{{ config('app.timezone') }}</dd></div>
            </dl>
        </x-card>
    </div>
</div>
@endsection
