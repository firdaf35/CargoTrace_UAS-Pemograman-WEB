@props(['title' => null, 'subtitle' => null])
<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_2px_14px_rgba(11,22,40,.06)] transition hover:shadow-[0_6px_24px_rgba(11,22,40,.09)]']) }}>
    @if ($title)
        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-navy">{{ $title }}</h2>
                @if ($subtitle)<p class="text-sm text-slate-500">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions){{ $actions }}@endisset
        </div>
    @endif
    {{ $slot }}
</section>
