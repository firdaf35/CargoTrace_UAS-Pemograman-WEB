@extends('layouts.app')
@section('title', 'Notifications')
@section('heading', 'Notifications')

@section('content')
<x-card title="In-app notifications">
    <x-slot:actions>
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Mark all as read</button></form>
    </x-slot:actions>
    @forelse ($items as $n)
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 py-3 last:border-0 {{ $n->read_at ? 'opacity-60' : '' }}">
            <div>
                <div class="flex items-center gap-2 font-medium">@unless ($n->read_at)<span class="h-2 w-2 rounded-full bg-sky-500"></span>@endunless {{ $n->title }}</div>
                <div class="text-sm text-slate-600">{{ $n->message }}</div>
                <div class="text-xs text-slate-400">{{ $n->created_at->diffForHumans() }}</div>
            </div>
            @unless ($n->read_at)
                <form method="POST" action="{{ route('notifications.read', $n) }}">@csrf<button class="whitespace-nowrap text-sm text-sky-700 hover:underline">{{ $n->shipment_id ? 'Open' : 'Mark read' }}</button></form>
            @endunless
        </div>
    @empty
        <p class="py-8 text-center text-sm text-slate-500">You have no notifications.</p>
    @endforelse
    <div class="mt-4">{{ $items->links() }}</div>
</x-card>
@endsection
