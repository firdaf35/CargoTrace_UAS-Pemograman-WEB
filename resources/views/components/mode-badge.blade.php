@props(['mode'])
@php $m = $mode instanceof \App\Enums\TransportMode ? $mode : \App\Enums\TransportMode::tryFrom((string) $mode) ?? \App\Enums\TransportMode::SEA; @endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold '.$m->tone()]) }}>
    <i data-lucide="{{ $m->icon() }}" class="h-3.5 w-3.5"></i>{{ $m->label() }}
</span>
