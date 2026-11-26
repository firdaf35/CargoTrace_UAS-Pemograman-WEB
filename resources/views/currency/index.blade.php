@extends('layouts.app')
@section('title', 'Currency Exchange')
@section('heading', 'Currency Exchange')

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format($n, $n < 1 ? 6 : 4), '0'), '.');
    $opts = collect($currencies)->mapWithKeys(fn ($name, $code) => [$code => $code.' — '.$name])->all();
@endphp

<div class="grid gap-4 lg:grid-cols-5">
    <x-card title="Currency converter" subtitle="Live conversion using real exchange rates" class="lg:col-span-2">
        <div class="space-y-3">
            <x-select name="from_ui" label="From" :options="$opts" value="USD" id="cFrom" />
            <div class="flex justify-center"><button type="button" id="cSwap" class="rounded-full border border-slate-200 p-2 text-ocean transition hover:rotate-180 hover:bg-sky-50" title="Swap"><i data-lucide="arrow-up-down" class="h-4 w-4"></i></button></div>
            <x-select name="to_ui" label="To" :options="$opts" value="IDR" id="cTo" />
            <label class="block text-sm"><span class="mb-1 block font-medium text-slate-700">Amount</span>
                <input id="cAmount" type="number" min="0" step="any" value="100" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-lg font-semibold focus:border-ocean focus:outline-none"></label>
        </div>
        <div id="cResult" class="mt-4 rounded-2xl bg-gradient-to-br from-navy to-ocean p-5 text-white">
            <div class="text-xs uppercase tracking-wide text-aqua">Converted amount</div>
            <div id="cOut" class="mt-1 text-3xl font-bold">—</div>
            <div id="cRate" class="mt-2 text-sm text-slate-200"></div>
            <div id="cTime" class="text-xs text-slate-300"></div>
        </div>
        <div id="cErr" class="mt-3 hidden rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-800"></div>
    </x-card>

    <x-card title="Exchange rate comparison" subtitle="Rates against the selected base currency" class="lg:col-span-3">
        <x-slot:actions>
            <form method="GET" class="flex items-center gap-2">
                <select name="base" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach ($currencies as $code => $name)<option value="{{ $code }}" @selected($base === $code)>Base: {{ $code }}</option>@endforeach
                </select>
            </form>
        </x-slot:actions>

        @if ($data)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-mist text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-2.5">Currency</th><th class="px-3 py-2.5 text-right">1 {{ $base }} =</th><th class="px-3 py-2.5 text-right">1 unit = {{ $base }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($data['rates'] as $code => $rate)
                            <tr class="transition hover:bg-mist/70">
                                <td class="px-3 py-2.5"><span class="font-semibold text-navy">{{ $code }}</span> <span class="text-xs text-slate-500">{{ $currencies[$code] ?? '' }}</span></td>
                                <td class="px-3 py-2.5 text-right font-medium">{{ $fmt($rate) }} {{ $code }}</td>
                                <td class="px-3 py-2.5 text-right text-slate-600">{{ $rate > 0 ? $fmt(1 / $rate) : '—' }} {{ $base }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-slate-500">Rates date: <b>{{ $data['date'] }}</b> (ECB reference rates via Frankfurter) · fetched {{ \Illuminate\Support\Carbon::parse($data['fetched_at'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i') }}. Rates are indicative and refreshed every 30 minutes.</p>
        @else
            <div class="rounded-2xl border border-red-100 bg-red-50 p-8 text-center">
                <div class="text-4xl">⚠️</div>
                <p class="mt-2 font-semibold text-red-800">Exchange rates are currently unavailable</p>
                <p class="text-sm text-red-700">The exchange-rate service could not be reached. No rates are shown to avoid displaying wrong numbers.</p>
                <a href="{{ route('currency.index', ['base' => $base]) }}" class="mt-4 inline-block rounded-lg bg-white px-4 py-2 text-sm shadow hover:bg-slate-50">Try again</a>
            </div>
        @endif
    </x-card>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const URL_ = {{ \Illuminate\Support\Js::from(route('currency.convert')) }};
    const $ = id => document.getElementById(id);
    let timer, seq = 0;
    const nf = (n, max) => new Intl.NumberFormat('en-US', { maximumFractionDigits: max }).format(n);

    async function run() {
        const mine = ++seq; const from = $('cFrom').value, to = $('cTo').value, amount = $('cAmount').value;
        $('cErr').classList.add('hidden');
        if (amount === '' || Number(amount) < 0) { $('cOut').textContent = '—'; $('cRate').textContent = ''; $('cTime').textContent = ''; return; }
        $('cOut').textContent = '...';
        try {
            const r = await fetch(URL_ + '?' + new URLSearchParams({ from, to, amount }), { headers: { Accept: 'application/json' } });
            const j = await r.json();
            if (mine !== seq) return;
            if (!r.ok) throw new Error(j.message || 'Conversion failed');
            const d = j.data;
            $('cOut').textContent = nf(d.result, d.result < 1 ? 6 : 2) + ' ' + to;
            $('cRate').textContent = '1 ' + from + ' = ' + nf(d.rate, d.rate < 1 ? 6 : 4) + ' ' + to;
            $('cTime').textContent = 'Rate date ' + d.date + ' · fetched ' + new Date(d.fetched_at).toLocaleString();
        } catch (e) {
            if (mine !== seq) return;
            $('cOut').textContent = '—'; $('cRate').textContent = ''; $('cTime').textContent = '';
            $('cErr').textContent = e.message; $('cErr').classList.remove('hidden');
        }
    }
    const debounced = () => { clearTimeout(timer); timer = setTimeout(run, 250); };
    ['cFrom', 'cTo'].forEach(id => $(id).addEventListener('change', run));
    $('cAmount').addEventListener('input', debounced);
    $('cSwap').addEventListener('click', () => { const f = $('cFrom').value; $('cFrom').value = $('cTo').value; $('cTo').value = f; run(); });
    run();
})();
</script>
@endpush
