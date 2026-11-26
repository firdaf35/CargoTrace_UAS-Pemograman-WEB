@extends('layouts.app')
@section('title', 'Global Weather')
@section('heading', 'Global Weather')

@section('content')
<x-card title="Search city or country" subtitle="Live conditions and forecast · provider: {{ $provider }}">
    <form id="wForm" class="flex flex-wrap gap-2">
        <div class="flex min-w-[240px] flex-1 items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 focus-within:border-ocean">
            <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
            <input id="wQ" minlength="2" maxlength="80" required placeholder="e.g. Rotterdam, Jakarta, Singapore..." class="w-full bg-transparent py-2.5 text-sm focus:outline-none">
        </div>
        <button class="rounded-xl bg-gradient-to-r from-ocean to-aqua px-6 py-2.5 text-sm font-semibold text-white shadow hover:brightness-110">Search</button>
    </form>
    <div id="results" class="mt-3 flex flex-wrap gap-2"></div>
</x-card>

<div id="mainCard" class="hidden"></div>

<div>
    <h2 class="mb-3 text-lg font-semibold text-navy">Shipment locations</h2>
    <div id="placeGrid" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($places as $i => $p)
            <div class="place rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_2px_14px_rgba(11,22,40,.06)]" data-lat="{{ $p['lat'] }}" data-lng="{{ $p['lng'] }}" data-name="{{ $p['name'] }}" data-country="{{ $p['country'] }}" data-cc="{{ $p['cc'] }}" data-role="{{ $p['role'] }}" data-shipment="{{ $p['shipment'] }}">
                <div class="skeleton h-5 w-1/2 rounded"></div><div class="skeleton mt-3 h-10 w-1/3 rounded"></div><div class="skeleton mt-3 h-4 w-full rounded"></div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl bg-white py-12 text-center text-slate-500 shadow-sm"><div class="mb-2 text-3xl">🌍</div>No active shipments. Search a city above to see its weather.</div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const SEARCH = {{ \Illuminate\Support\Js::from(route('weather.search')) }}, DATA = {{ \Illuminate\Support\Js::from(route('weather.data')) }};
    const $ = id => document.getElementById(id);
    const el = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };
    const flag = cc => { if (!cc || cc.length !== 2) return null; const i = el('img', 'h-4 w-6 rounded-sm object-cover shadow'); i.src = 'https://flagcdn.com/w40/' + cc.toLowerCase() + '.png'; i.alt = cc; i.loading = 'lazy'; i.onerror = () => i.remove(); return i; };
    const icon = w => { if (w.icon_url) { const i = el('img', 'h-14 w-14'); i.src = w.icon_url; i.alt = w.condition; return i; } return el('span', 'text-5xl', w.icon_emoji || '🌡️'); };
    const day = d => new Date(d + 'T12:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
    const stat = (k, v) => { const d = el('div', 'rounded-xl bg-mist px-3 py-2 text-center'); d.append(el('div', 'text-[11px] text-slate-500', k), el('div', 'font-semibold text-navy', v)); return d; };

    async function fetchWeather(lat, lng) {
        const r = await fetch(DATA + '?' + new URLSearchParams({ lat, lng }), { headers: { Accept: 'application/json' } });
        if (!r.ok) throw new Error((await r.json().catch(() => ({}))).message || 'Weather service unavailable');
        return (await r.json()).data;
    }

    function buildCard(p, w, big) {
        const card = el('div', 'rounded-2xl border border-slate-100 bg-white p-' + (big ? '6' : '4') + ' shadow-[0_2px_14px_rgba(11,22,40,.06)]');
        const head = el('div', 'flex items-center justify-between gap-2');
        const left = el('div', 'flex items-center gap-2'); const f = flag(p.cc); if (f) left.appendChild(f);
        const t = el('div'); t.append(el('div', 'font-semibold text-navy', p.name), el('div', 'text-xs text-slate-500', p.country)); left.appendChild(t);
        head.appendChild(left);
        if (p.role) head.appendChild(el('span', 'rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold uppercase text-ocean', p.role));
        card.appendChild(head);
        const main = el('div', 'mt-3 flex items-center gap-3'); main.append(icon(w), el('div', 'text-4xl font-bold text-navy', Math.round(w.temperature) + '°C'));
        const cond = el('div', 'text-sm text-slate-600', w.condition); main.appendChild(cond); card.appendChild(main);
        const g = el('div', 'mt-3 grid grid-cols-3 gap-2'); g.append(stat('Feels like', Math.round(w.feels_like) + '°C'), stat('Humidity', w.humidity + '%'), stat('Wind', w.wind_kmh + ' km/h')); card.appendChild(g);
        if (p.shipment) card.appendChild(el('div', 'mt-2 text-[11px] text-slate-400', 'Shipment ' + p.shipment));
        if (big && w.daily.length) {
            const fc = el('div', 'mt-5 grid grid-cols-2 gap-2 sm:grid-cols-5');
            w.daily.forEach(d => { const c = el('div', 'rounded-xl border border-slate-100 p-3 text-center'); c.append(el('div', 'text-xs font-semibold text-slate-500', day(d.date)), icon({ icon_url: d.icon_url, icon_emoji: d.icon_emoji, condition: d.condition }), el('div', 'text-sm font-bold text-navy', d.max + '° / ' + d.min + '°'), el('div', 'text-[11px] text-slate-500', d.condition), el('div', 'text-[11px] text-ocean', '☔ ' + d.precip + '%')); fc.appendChild(c); });
            card.appendChild(fc);
        } else if (w.daily.length) {
            const fc = el('div', 'mt-3 flex gap-1 overflow-x-auto text-center'); w.daily.slice(0, 4).forEach(d => { const c = el('div', 'min-w-[60px] flex-1 rounded-lg bg-mist px-1 py-1.5'); c.append(el('div', 'text-[10px] text-slate-500', day(d.date).split(' ')[0]), el('div', 'text-sm', d.icon_emoji || ''), el('div', 'text-[11px] font-semibold', d.max + '°/' + d.min + '°')); fc.appendChild(c); }); card.appendChild(fc);
        }
        card.appendChild(el('div', 'mt-3 text-[10px] text-slate-400', 'Source: ' + w.provider + ' · updated ' + new Date(w.fetched_at).toLocaleTimeString()));
        return card;
    }

    function errorCard(msg, retry) { const c = el('div', 'rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-800'); c.append(el('div', 'font-semibold', '⚠️ Weather unavailable'), el('div', 'text-xs', msg)); if (retry) { const b = el('button', 'mt-2 rounded-lg bg-white px-3 py-1 text-xs shadow', 'Retry'); b.type = 'button'; b.onclick = retry; c.appendChild(b); } return c; }

    async function loadPlace(div) {
        div.replaceChildren(el('div', 'skeleton h-5 w-1/2 rounded'), el('div', 'skeleton mt-3 h-10 w-1/3 rounded'), el('div', 'skeleton mt-3 h-4 w-full rounded'));
        const p = { name: div.dataset.name, country: div.dataset.country, cc: div.dataset.cc, role: div.dataset.role, shipment: div.dataset.shipment };
        try { const w = await fetchWeather(div.dataset.lat, div.dataset.lng); div.replaceWith(Object.assign(buildCard(p, w, false), { className: buildCard(p, w, false).className })); }
        catch (e) { div.replaceChildren(errorCard(e.message, () => loadPlace(div))); }
    }
    document.querySelectorAll('.place').forEach(d => loadPlace(d));

    // Search
    $('wForm').addEventListener('submit', async e => {
        e.preventDefault(); const q = $('wQ').value.trim(); const box = $('results'); box.replaceChildren(el('span', 'text-sm text-slate-500', 'Searching...'));
        try {
            const r = await fetch(SEARCH + '?' + new URLSearchParams({ q }), { headers: { Accept: 'application/json' } });
            if (!r.ok) throw new Error((await r.json().catch(() => ({}))).message || 'Search failed');
            const res = (await r.json()).results; box.replaceChildren();
            if (!res.length) { box.appendChild(el('span', 'text-sm text-slate-500', 'No city or country found for "' + q + '".')); return; }
            res.forEach(x => { const b = el('button', 'flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm transition hover:border-ocean hover:bg-sky-50'); b.type = 'button'; const f = flag(x.country_code); if (f) b.appendChild(f); b.append(document.createTextNode(x.name + (x.region ? ', ' + x.region : '') + ' · ' + x.country)); b.onclick = () => showMain(x); box.appendChild(b); });
            if (res.length === 1) showMain(res[0]);
        } catch (err) { box.replaceChildren(el('span', 'text-sm text-red-600', err.message)); toast(err.message, 'error'); }
    });
    async function showMain(x) {
        const m = $('mainCard'); m.classList.remove('hidden'); m.replaceChildren(el('div', 'skeleton h-56 rounded-2xl'));
        try { const w = await fetchWeather(x.lat, x.lng); m.replaceChildren(buildCard({ name: x.name + (x.region ? ', ' + x.region : ''), country: x.country, cc: x.country_code }, w, true)); }
        catch (e) { m.replaceChildren(errorCard(e.message, () => showMain(x))); }
    }
})();
</script>
@endpush
