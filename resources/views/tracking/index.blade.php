@extends('layouts.app')
@section('title', 'Tracking')
@section('heading', 'Shipment Tracking')

@section('content')
<x-card class="!p-4">
    <div class="flex flex-wrap items-end gap-3">
        <label class="min-w-[220px] flex-1 text-sm">
            <span class="mb-1 block font-medium text-slate-700">Search tracking number</span>
            <div class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 focus-within:border-ocean">
                <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                <input id="fQ" value="{{ $q }}" maxlength="50" placeholder="e.g. SHP-2026-000123" class="w-full bg-transparent py-2.5 text-sm focus:outline-none">
            </div>
        </label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Transport</span>
            <select id="fMode" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                <option value="">All Transport</option>
                @foreach ($modes as $m)<option value="{{ $m->value }}">{{ $m->label() }}</option>@endforeach
            </select></label>
        <label class="text-sm"><span class="mb-1 block font-medium text-slate-700">Status</span>
            <select id="fStatus" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)<option value="{{ $s }}">{{ str_replace('_', ' ', $s) }}</option>@endforeach
            </select></label>
        <button id="fApply" class="rounded-xl bg-gradient-to-r from-ocean to-aqua px-5 py-2.5 text-sm font-semibold text-white shadow hover:brightness-110">Search</button>
    </div>
</x-card>

<div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
    <div id="mapWrap" class="relative overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-[0_2px_14px_rgba(11,22,40,.06)]">
        <div id="worldMap" class="h-[calc(100vh-280px)] min-h-[480px] w-full"></div>
        <div id="mapLoading" class="absolute inset-0 z-[500] flex items-center justify-center bg-white/70 backdrop-blur-sm">
            <div class="flex items-center gap-3 rounded-xl bg-white px-5 py-3 shadow-lg"><svg class="h-5 w-5 animate-spin text-ocean" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4"/></svg><span class="text-sm font-medium">Loading shipments...</span></div>
        </div>
        <div id="mapError" class="absolute inset-0 z-[500] hidden items-center justify-center bg-white/90">
            <div class="text-center"><div class="text-4xl">⚠️</div><p class="mt-2 font-semibold text-navy">Could not load shipment data</p><button id="retry" class="mt-3 rounded-lg bg-ocean px-4 py-2 text-sm text-white">Retry</button></div>
        </div>
        <div class="absolute bottom-3 left-3 z-[400] max-w-[calc(100%-1.5rem)] rounded-xl bg-white/95 p-3 text-[11px] shadow-lg">
            <div class="mb-1 font-semibold text-navy">Legend</div>
            <div class="flex flex-wrap gap-x-3 gap-y-1" id="legend"></div>
            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-slate-600">
                <span class="flex items-center gap-1"><span class="inline-block w-6 border-t-2 border-ocean"></span>Reported path</span>
                <span class="flex items-center gap-1"><span class="inline-block w-6 border-t-2 border-dashed border-ocean"></span>Estimated route</span>
            </div>
            <div class="mt-1 text-slate-500">Marker = last reported location (not real-time GPS).</div>
        </div>
    </div>

    <aside class="space-y-4">
        <x-card id="detailCard" title="Shipment details">
            <div id="detailEmpty" class="py-8 text-center text-sm text-slate-500"><div class="mb-2 text-3xl">🗺️</div>Select a shipment on the map or from the list.</div>
            <div id="detail" class="hidden"></div>
        </x-card>
        <x-card title="Shipments" subtitle="Click to focus on the map">
            <div id="list" class="max-h-[320px] space-y-1 overflow-y-auto"></div>
        </x-card>
    </aside>
</div>

<x-card title="Latest tracking events" subtitle="Recorded events from the database">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-mist text-xs uppercase tracking-wide text-slate-500"><tr>@foreach (['Time', 'Shipment', 'Event', 'Location', 'Description'] as $h)<th class="px-3 py-2.5 font-semibold">{{ $h }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($events as $e)
                    <tr class="hover:bg-mist/70">
                        <td class="whitespace-nowrap px-3 py-2.5">{{ $e->occurred_at->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2.5"><a class="font-semibold text-ocean hover:underline" href="{{ route('shipments.show', $e->shipment_id) }}">{{ $e->shipment->shipment_number }}</a></td>
                        <td class="px-3 py-2.5 {{ $e->is_exception ? 'font-semibold text-red-600' : '' }}">{{ ucwords(strtolower(str_replace('_', ' ', $e->event_type))) }}</td>
                        <td class="px-3 py-2.5">{{ $e->location_name ?? '—' }}</td>
                        <td class="px-3 py-2.5 text-slate-600">{{ $e->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-10 text-center text-slate-500">No tracking events found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $events->links() }}</div>
</x-card>
@endsection

@push('scripts')
<script>
(() => {
    const DATA_URL = {{ \Illuminate\Support\Js::from(route('tracking.data')) }};
    let selectedId = {{ \Illuminate\Support\Js::from($selected) }};
    const COLORS = { CREATED: '#64748b', BOOKED: '#8b5cf6', PICKED_UP: '#6366f1', IN_TRANSIT: '#176B87', DELIVERED: '#16a34a', DELAYED: '#dc2626', CANCELLED: '#9ca3af' };
    const $ = id => document.getElementById(id);
    const color = s => COLORS[s] || '#64748b';
    const label = s => String(s || '').replace(/_/g, ' ');
    const title = s => label(s).toLowerCase().replace(/\b\w/g, c => c.toUpperCase());

    // Legend
    Object.entries(COLORS).forEach(([k, v]) => { const s = document.createElement('span'); s.className = 'flex items-center gap-1'; const d = document.createElement('i'); d.style.cssText = `width:10px;height:10px;border-radius:50%;background:${v};display:inline-block`; s.append(d, document.createTextNode(title(k))); $('legend').appendChild(s); });

    const map = L.map('worldMap', { minZoom: 2, maxBoundsViscosity: .8, worldCopyJump: true, zoomControl: true }).setView([20, 40], 2);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors' }).addTo(map);
    const markers = L.layerGroup().addTo(map), route = L.layerGroup().addTo(map);

    // Fullscreen control
    const FS = L.Control.extend({ options: { position: 'topleft' }, onAdd() {
        const b = L.DomUtil.create('a', 'leaflet-bar leaflet-control'); b.href = '#'; b.title = 'Fullscreen'; b.style.cssText = 'background:#fff;width:30px;height:30px;line-height:30px;text-align:center;font-size:16px;text-decoration:none;display:block;color:#0B1628';
        b.textContent = '⛶'; L.DomEvent.on(b, 'click', e => { L.DomEvent.preventDefault(e); document.fullscreenElement ? document.exitFullscreen() : $('mapWrap').requestFullscreen?.(); }); return b; } });
    map.addControl(new FS());
    document.addEventListener('fullscreenchange', () => { $('worldMap').style.height = document.fullscreenElement ? '100vh' : ''; setTimeout(() => map.invalidateSize(), 150); });

    const icon = (s, emoji, big) => L.divIcon({ className: '', iconSize: big ? [40, 40] : [30, 30], iconAnchor: big ? [20, 20] : [15, 15], popupAnchor: [0, -14],
        html: `<div style="width:${big ? 40 : 30}px;height:${big ? 40 : 30}px;border-radius:50%;background:${color(s)};border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center;font-size:${big ? 20 : 15}px">${emoji}</div>` });
    const dot = (c, r = 6) => L.circleMarker([0, 0], { radius: r, color: '#fff', weight: 2, fillColor: c, fillOpacity: 1 });
    const pop = (head, lines) => { const d = document.createElement('div'); const b = document.createElement('b'); b.textContent = head; d.append(b); lines.filter(Boolean).forEach(l => { d.append(document.createElement('br'), document.createTextNode(l)); }); return d; };

    // Great-circle points (air routes)
    const arc = (a, b, n = 48) => { const r = Math.PI / 180, p1 = a[0] * r, l1 = a[1] * r, p2 = b[0] * r, l2 = b[1] * r;
        const d = 2 * Math.asin(Math.sqrt(Math.sin((p2 - p1) / 2) ** 2 + Math.cos(p1) * Math.cos(p2) * Math.sin((l2 - l1) / 2) ** 2)); if (!d) return [a, b];
        const pts = []; for (let i = 0; i <= n; i++) { const f = i / n, A = Math.sin((1 - f) * d) / Math.sin(d), B = Math.sin(f * d) / Math.sin(d);
            const x = A * Math.cos(p1) * Math.cos(l1) + B * Math.cos(p2) * Math.cos(l2), y = A * Math.cos(p1) * Math.sin(l1) + B * Math.cos(p2) * Math.sin(l2), z = A * Math.sin(p1) + B * Math.sin(p2);
            pts.push([Math.atan2(z, Math.sqrt(x * x + y * y)) / r, Math.atan2(y, x) / r]); } return pts; };

    let all = [];
    const params = () => { const p = new URLSearchParams(); [['mode', 'fMode'], ['status', 'fStatus'], ['q', 'fQ']].forEach(([k, id]) => { const v = $(id).value.trim(); if (v) p.set(k, v); }); return p; };

    async function load() {
        $('mapLoading').classList.remove('hidden'); $('mapError').classList.add('hidden'); $('mapError').classList.remove('flex');
        try {
            const res = await fetch(DATA_URL + '?' + params(), { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error(res.status);
            all = (await res.json()).data;
            const exact = all.find(s => s.number.toLowerCase() === $('fQ').value.trim().toLowerCase());
            if (exact) selectedId = exact.id;
            render();
        } catch (e) { $('mapError').classList.remove('hidden'); $('mapError').classList.add('flex'); toast('Failed to load tracking data.', 'error'); }
        $('mapLoading').classList.add('hidden');
    }

    function render() {
        markers.clearLayers(); route.clearLayers();
        const list = $('list'); list.innerHTML = '';
        if (!all.length) { list.innerHTML = '<div class="py-8 text-center text-sm text-slate-500">No shipments match the filters.</div>'; showDetail(null); return; }
        const bounds = [];
        all.forEach(s => {
            const pos = s.current ? [s.current.lat, s.current.lng] : (s.origin ? [s.origin.lat, s.origin.lng] : null);
            if (pos) {
                const m = L.marker(pos, { icon: icon(s.status, s.emoji, s.id === selectedId) }).addTo(markers);
                m.bindPopup(pop(s.number + ' · ' + s.mode_label, [title(s.status), s.current ? 'Last reported: ' + (s.current.name || 'position') : 'Not yet departed (at origin)', s.current ? s.current.time : null]));
                m.on('click', () => select(s.id));
                if (s.id === selectedId) m.setZIndexOffset(1000);
                bounds.push(pos);
            }
            const row = document.createElement('button'); row.type = 'button';
            row.className = 'flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm transition hover:bg-mist ' + (s.id === selectedId ? 'bg-sky-50 ring-1 ring-ocean/30' : '');
            const em = document.createElement('span'); em.textContent = s.emoji; em.className = 'text-lg';
            const mid = document.createElement('span'); mid.className = 'min-w-0 flex-1';
            const a = document.createElement('span'); a.className = 'block truncate font-semibold text-navy'; a.textContent = s.number;
            const b = document.createElement('span'); b.className = 'block truncate text-xs text-slate-500'; b.textContent = s.origin.city + ' → ' + s.destination.city;
            mid.append(a, b);
            const st = document.createElement('span'); st.className = 'rounded px-1.5 py-0.5 text-[10px] font-bold uppercase text-white'; st.style.background = color(s.status); st.textContent = label(s.status);
            row.append(em, mid, st); row.addEventListener('click', () => select(s.id)); list.appendChild(row);
        });
        const sel = all.find(s => s.id === selectedId);
        if (sel) { drawRoute(sel); showDetail(sel); } else { showDetail(null); if (bounds.length) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 4 }); }
    }

    function drawRoute(s) {
        route.clearLayers();
        const o = [s.origin.lat, s.origin.lng], d = [s.destination.lat, s.destination.lng];
        const reported = s.path.length ? s.path : [];
        const last = reported.length ? reported[reported.length - 1] : o;
        const remaining = s.raw_status === 'DELIVERED' ? [] : (s.mode === 'air' ? arc(last, d) : [last, d]);
        const dashed = s.raw_status === 'DELIVERED' || s.raw_status === 'CANCELLED' ? [] : remaining;
        if (!reported.length && s.raw_status !== 'CANCELLED') L.polyline(s.mode === 'air' ? arc(o, d) : [o, d], { color: '#176B87', weight: 3, opacity: .7, dashArray: '8 8' }).addTo(route);
        if (reported.length > 1) L.polyline(reported, { color: '#176B87', weight: 4, opacity: .9 }).addTo(route);
        if (reported.length && dashed.length) L.polyline(dashed, { color: '#176B87', weight: 3, opacity: .7, dashArray: '8 8' }).addTo(route);
        dot('#16a34a', 8).setLatLng(o).addTo(route).bindPopup(pop('Origin', [s.origin.name, s.origin.city + ', ' + s.origin.country]));
        dot('#f08a24', 8).setLatLng(d).addTo(route).bindPopup(pop('Destination', [s.destination.name, s.destination.city + ', ' + s.destination.country]));
        s.events.filter(e => e.lat != null && !e.exception).forEach(e => dot('#176B87', 4).setLatLng([e.lat, e.lng]).addTo(route).bindPopup(pop(title(e.type), [e.time, e.location, e.description])));
        s.events.filter(e => e.lat != null && e.exception).forEach(e => dot('#dc2626', 6).setLatLng([e.lat, e.lng]).addTo(route).bindPopup(pop('Exception', [e.time, e.description])));
        map.fitBounds([o, d, ...reported], { padding: [60, 60], maxZoom: 6 });
    }

    function row(dl, k, v) { const d = document.createElement('div'); d.className = 'flex justify-between gap-3 py-1.5 text-sm border-b border-slate-50'; const a = document.createElement('dt'); a.className = 'text-slate-500'; a.textContent = k; const b = document.createElement('dd'); b.className = 'text-right font-medium'; b.textContent = v ?? '—'; d.append(a, b); dl.appendChild(d); }

    function showDetail(s) {
        $('detailEmpty').classList.toggle('hidden', !!s); $('detail').classList.toggle('hidden', !s);
        if (!s) return;
        const box = $('detail'); box.innerHTML = '';
        const head = document.createElement('div'); head.className = 'mb-2 flex items-center justify-between';
        const n = document.createElement('div'); n.className = 'text-lg font-bold text-navy'; n.textContent = s.emoji + ' ' + s.number;
        const st = document.createElement('span'); st.className = 'rounded px-2 py-0.5 text-[10px] font-bold uppercase text-white'; st.style.background = color(s.status); st.textContent = label(s.status);
        head.append(n, st); box.appendChild(head);
        const dl = document.createElement('dl');
        row(dl, 'Tracking Number', s.number); row(dl, 'Transport', s.mode_label + (s.asset ? ' · ' + s.asset : ''));
        row(dl, 'Origin', s.origin.name + ', ' + s.origin.city); row(dl, 'Destination', s.destination.name + ', ' + s.destination.city);
        row(dl, 'Current Location', s.current ? (s.current.name || 'Position reported') + ' (last reported)' : 'Not reported yet');
        row(dl, 'Carrier', s.carrier); row(dl, 'Shipment Status', title(s.status));
        row(dl, 'Departure Date', s.departure); row(dl, 'Estimated Arrival', s.eta); row(dl, 'Last Updated', s.last_updated);
        box.appendChild(dl);
        const h = document.createElement('div'); h.className = 'mb-2 mt-4 text-sm font-semibold text-navy'; h.textContent = 'Journey'; box.appendChild(h);
        const ol = document.createElement('ol'); ol.className = 'max-h-56 space-y-2.5 overflow-y-auto border-l-2 border-slate-200 pl-4';
        s.events.slice().reverse().forEach(e => { const li = document.createElement('li'); li.className = 'relative';
            const dotEl = document.createElement('span'); dotEl.className = 'absolute -left-[22px] top-1 h-3 w-3 rounded-full border-2 border-white'; dotEl.style.background = e.exception ? '#dc2626' : '#176B87';
            const t = document.createElement('div'); t.className = 'text-sm font-semibold ' + (e.exception ? 'text-red-600' : 'text-navy'); t.textContent = title(e.type);
            const m = document.createElement('div'); m.className = 'text-xs text-slate-500'; m.textContent = e.time + (e.location ? ' · ' + e.location : '');
            li.append(dotEl, t, m); ol.appendChild(li); });
        box.appendChild(ol);
        const a = document.createElement('a'); a.href = s.url; a.className = 'mt-4 block rounded-xl bg-gradient-to-r from-ocean to-aqua py-2.5 text-center text-sm font-semibold text-white hover:brightness-110'; a.textContent = 'Open full details'; box.appendChild(a);
    }

    function select(id) { selectedId = id; render(); }

    $('fApply').addEventListener('click', () => { selectedId = null; load(); });
    $('fQ').addEventListener('keydown', e => { if (e.key === 'Enter') { selectedId = null; load(); } });
    ['fMode', 'fStatus'].forEach(id => $(id).addEventListener('change', () => { selectedId = null; load(); }));
    $('retry').addEventListener('click', load);
    load();
})();
</script>
@endpush
