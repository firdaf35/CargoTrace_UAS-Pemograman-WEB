@extends('layouts.app')
@section('title', 'Customers')
@section('heading', 'Customers')

@section('content')
<x-card>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex gap-2">
            <input name="q" value="{{ $q }}" maxlength="100" placeholder="Search customer, company, email..." class="w-72 max-w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-ocean focus:outline-none">
            <button class="rounded-xl bg-ocean px-4 py-2 text-sm font-medium text-white hover:bg-sky-800">Search</button>
        </form>
        @if (auth()->user()->isAdmin())<a href="{{ route('users.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm hover:bg-mist">Manage users</a>@endif
    </div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($customers as $c)
            <div class="rounded-2xl border border-slate-100 bg-mist p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-ocean to-aqua font-semibold text-white">{{ $c->initials() }}</span>
                    <div class="min-w-0"><div class="truncate font-semibold text-navy">{{ $c->company ?: $c->name }}</div><div class="truncate text-xs text-slate-500">{{ $c->name }} · {{ $c->email }}</div></div>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-white py-2"><div class="text-xl font-bold text-navy">{{ $c->shipments_count }}</div><div class="text-[11px] text-slate-500">Shipments</div></div>
                    <div class="rounded-xl bg-white py-2"><div class="text-xl font-bold text-ocean">{{ $c->active_count }}</div><div class="text-[11px] text-slate-500">Active</div></div>
                    <div class="rounded-xl bg-white py-2"><div class="text-xl font-bold text-orange-500">{{ $c->delayed_count }}</div><div class="text-[11px] text-slate-500">Delayed</div></div>
                </div>
                <a href="{{ route('shipments.index', ['customer_id' => $c->id]) }}" class="mt-3 block text-center text-sm font-medium text-ocean hover:underline">View shipments →</a>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-500"><div class="mb-2 text-3xl">👥</div>No customers found.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
</x-card>
@endsection
