<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · CargoTrace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
            colors: {
                navy: '#0B1628', midnight: '#14243B', ocean: '#176B87', aqua: '#29C7D8', mist: '#F7F9FC',
                // The "sky" palette is remapped to the CargoTrace ocean/cyan brand colours.
                sky: { 50: '#eefbfd', 100: '#d2f3f7', 200: '#a6e6ee', 300: '#6dd5e2', 400: '#29C7D8', 500: '#1fa9bb', 600: '#176B87', 700: '#135a73', 800: '#0f4a5e', 900: '#0B1628' },
            },
        } } };
    </script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.441.0/dist/umd/lucide.min.js"></script>
    <style>
        .leaflet-container { font: inherit; }
        @keyframes pageIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        main { animation: pageIn .35s ease both; }
        @keyframes toastIn { from { opacity: 0; transform: translateX(24px); } to { opacity: 1; transform: none; } }
        .toast { animation: toastIn .3s ease both; }
        #progress { position: fixed; top: 0; left: 0; height: 3px; width: 0; background: linear-gradient(90deg, #29C7D8, #176B87); z-index: 100; transition: width .4s ease, opacity .3s; opacity: 0; }
        .skeleton { background: linear-gradient(90deg, #eef2f7 25%, #f7f9fc 37%, #eef2f7 63%); background-size: 400% 100%; animation: shimmer 1.4s ease infinite; }
        @keyframes shimmer { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }
    </style>
</head>
@php
    $user = auth()->user();
    $staff = $user->isStaff();
    $unread = $user->appNotifications()->whereNull('read_at')->count();
    $latestNotes = $user->appNotifications()->latest()->limit(5)->get();
    $nav = [
        [['dashboard'], 'dashboard', 'Dashboard', 'layout-dashboard', true],
        [['shipments.*'], 'shipments.index', 'Shipments', 'package', true],
        [['tracking.*'], 'tracking.index', 'Tracking', 'map-pin', true],
        [['exceptions.*'], 'exceptions.index', 'Exceptions', 'alert-triangle', true],
        [['transport.*'], 'transport.index', 'Transport Management', 'container', $staff],
        [['customers.*'], 'customers.index', 'Customers', 'users', $staff],
        [['carriers.*'], 'carriers.index', 'Carriers', 'truck', $staff],
        [['reports.*'], 'reports.index', 'Reports', 'bar-chart-3', $staff],
        [['weather.*'], 'weather.index', 'Global Weather', 'cloud-sun', true],
        [['currency.*'], 'currency.index', 'Currency Exchange', 'circle-dollar-sign', true],
        [['settings.*', 'locations.*', 'users.*', 'audit.*'], 'settings.index', 'Settings', 'settings', true],
    ];
@endphp
<body class="bg-mist font-sans text-slate-800 antialiased">
<div id="progress"></div>
<div id="toasts" class="pointer-events-none fixed right-4 top-4 z-[90] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2"></div>

{{-- Confirmation dialog --}}
<div id="confirmModal" class="fixed inset-0 z-[95] hidden items-center justify-center bg-navy/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-red-100 text-red-600"><i data-lucide="alert-triangle"></i></div>
        <h3 class="text-lg font-semibold text-navy">Please confirm</h3>
        <p id="confirmText" class="mt-1 text-sm text-slate-600">Are you sure?</p>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" id="confirmCancel" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Cancel</button>
            <button type="button" id="confirmOk" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Yes, continue</button>
        </div>
    </div>
</div>

<div id="overlay" class="fixed inset-0 z-30 hidden bg-navy/60 md:hidden"></div>
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-[248px] -translate-x-full flex-col bg-gradient-to-b from-navy to-midnight text-slate-300 transition-transform duration-300 md:sticky md:top-0 md:h-screen md:translate-x-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-6 py-6">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-aqua to-ocean text-white shadow-lg shadow-aqua/20"><i data-lucide="ship" class="h-6 w-6"></i></span>
            <span><span class="block text-xl font-bold leading-none text-white">CargoTrace</span><span class="text-[10px] uppercase tracking-[.2em] text-aqua">Global Logistics</span></span>
        </a>
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2">
            @foreach ($nav as [$patterns, $route, $label, $icon, $show])
                @if ($show)
                    @php $active = request()->routeIs(...$patterns); @endphp
                    <a href="{{ route($route) }}" class="group flex items-center gap-3 rounded-xl px-4 py-2.5 text-[14.5px] transition {{ $active ? 'bg-gradient-to-r from-ocean to-ocean/60 font-semibold text-white shadow-md shadow-black/20' : 'hover:bg-white/5 hover:text-white' }}">
                        <i data-lucide="{{ $icon }}" class="h-[18px] w-[18px] {{ $active ? 'text-aqua' : 'text-slate-400 group-hover:text-aqua' }}"></i>{{ $label }}
                    </a>
                @endif
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10 p-3">
            @csrf
            <button class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-[14.5px] hover:bg-white/5 hover:text-white"><i data-lucide="log-out" class="h-[18px] w-[18px] text-slate-400"></i>Logout</button>
        </form>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Top bar --}}
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-slate-200/70 bg-white/85 px-4 py-3 backdrop-blur md:px-6">
            <div class="flex items-center gap-3">
                <button id="menuBtn" class="rounded-lg p-2 hover:bg-slate-100 md:hidden" aria-label="Open menu"><i data-lucide="menu" class="h-5 w-5"></i></button>
                <h1 class="text-xl font-bold text-navy md:text-2xl">@yield('heading', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-2 md:gap-4">
                <form action="{{ route('shipments.index') }}" method="GET" class="hidden lg:block">
                    <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-mist px-4 py-2 focus-within:border-ocean">
                        <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                        <input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Search tracking number..." class="w-52 bg-transparent text-sm focus:outline-none">
                    </div>
                </form>

                {{-- Notifications dropdown --}}
                <div class="relative" data-dropdown>
                    <button data-dropdown-toggle class="relative rounded-full p-2 hover:bg-slate-100" title="Notifications">
                        <i data-lucide="bell" class="h-5 w-5"></i>
                        @if ($unread > 0)<span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
                    </button>
                    <div data-dropdown-menu class="absolute right-0 mt-2 hidden w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-xl">
                        <div class="flex items-center justify-between border-b px-4 py-3"><span class="font-semibold text-navy">Notifications</span><a href="{{ route('notifications.index') }}" class="text-xs text-ocean hover:underline">View all</a></div>
                        @forelse ($latestNotes as $n)
                            <div class="border-b border-slate-50 px-4 py-3 text-sm {{ $n->read_at ? 'opacity-60' : '' }}">
                                <div class="font-medium">{{ $n->title }}</div>
                                <div class="line-clamp-2 text-xs text-slate-500">{{ $n->message }}</div>
                                <div class="mt-1 text-[11px] text-slate-400">{{ $n->created_at->diffForHumans() }}</div>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-sm text-slate-500">No notifications yet.</div>
                        @endforelse
                    </div>
                </div>

                {{-- User dropdown --}}
                <div class="relative" data-dropdown>
                    <button data-dropdown-toggle class="flex items-center gap-2 rounded-full p-1 pr-3 hover:bg-slate-100">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-ocean to-aqua text-sm font-semibold text-white">{{ $user->initials() }}</span>
                        <span class="hidden text-left leading-tight sm:block"><span class="block text-sm font-semibold">{{ $user->name }}</span><span class="block text-[11px] text-slate-500">{{ $user->role->label() }}</span></span>
                        <i data-lucide="chevron-down" class="hidden h-4 w-4 text-slate-400 sm:block"></i>
                    </button>
                    <div data-dropdown-menu class="absolute right-0 mt-2 hidden w-52 overflow-hidden rounded-2xl border border-slate-100 bg-white py-1 shadow-xl">
                        <a href="{{ route('settings.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-mist"><i data-lucide="settings" class="h-4 w-4"></i>Settings</a>
                        <a href="{{ route('notifications.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-mist"><i data-lucide="bell" class="h-4 w-4"></i>Notifications</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50"><i data-lucide="log-out" class="h-4 w-4"></i>Logout</button></form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 space-y-5 p-4 md:p-6">
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
        <footer class="px-6 pb-5 text-xs text-slate-400">CargoTrace · Web Programming 2 UAS · Map data © OpenStreetMap contributors</footer>
    </div>
</div>

<script>
(() => {
    const $ = (s, r = document) => r.querySelector(s);
    const progress = $('#progress');
    window.startProgress = () => { progress.style.opacity = 1; progress.style.width = '70%'; };
    window.doneProgress = () => { progress.style.width = '100%'; setTimeout(() => { progress.style.opacity = 0; progress.style.width = 0; }, 300); };

    // Toast
    window.toast = (message, type = 'success') => {
        const colors = { success: 'border-emerald-300 bg-emerald-50 text-emerald-900', error: 'border-red-300 bg-red-50 text-red-900', info: 'border-sky-200 bg-sky-50 text-sky-900' };
        const el = document.createElement('div');
        el.className = 'toast pointer-events-auto rounded-xl border px-4 py-3 text-sm shadow-lg ' + (colors[type] || colors.info);
        el.textContent = message;
        $('#toasts').appendChild(el);
        setTimeout(() => { el.style.opacity = 0; el.style.transition = 'opacity .4s'; setTimeout(() => el.remove(), 400); }, 4200);
    };
    const flash = {{ \Illuminate\Support\Js::from(['success' => session('success'), 'status' => session('status')]) }};
    if (flash.success) toast(flash.success, 'success');
    if (flash.status) toast(flash.status, 'info');

    // Dropdowns
    document.querySelectorAll('[data-dropdown]').forEach(dd => {
        const menu = $('[data-dropdown-menu]', dd);
        $('[data-dropdown-toggle]', dd).addEventListener('click', e => {
            e.stopPropagation();
            document.querySelectorAll('[data-dropdown-menu]').forEach(m => { if (m !== menu) m.classList.add('hidden'); });
            menu.classList.toggle('hidden');
        });
    });
    document.addEventListener('click', () => document.querySelectorAll('[data-dropdown-menu]').forEach(m => m.classList.add('hidden')));

    // Responsive sidebar
    const sidebar = $('#sidebar'), overlay = $('#overlay');
    const setMenu = open => { sidebar.classList.toggle('-translate-x-full', !open); overlay.classList.toggle('hidden', !open); };
    $('#menuBtn').addEventListener('click', () => setMenu(true));
    overlay.addEventListener('click', () => setMenu(false));

    // Confirmation dialog for forms with data-confirm
    const modal = $('#confirmModal'); let pendingForm = null;
    document.addEventListener('submit', e => {
        const form = e.target;
        if (form.dataset.confirm && !form.dataset.confirmed) {
            e.preventDefault(); pendingForm = form;
            $('#confirmText').textContent = form.dataset.confirm;
            modal.classList.remove('hidden'); modal.classList.add('flex');
            return;
        }
        startProgress();
        form.querySelectorAll('button:not([type=button])').forEach(b => { b.disabled = true; b.classList.add('opacity-60'); });
    });
    const closeModal = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); pendingForm = null; };
    $('#confirmCancel').addEventListener('click', closeModal);
    $('#confirmOk').addEventListener('click', () => { if (pendingForm) { pendingForm.dataset.confirmed = '1'; const f = pendingForm; closeModal(); f.requestSubmit(); } });
    window.addEventListener('pageshow', () => document.querySelectorAll('form button').forEach(b => { b.disabled = false; b.classList.remove('opacity-60'); }));

    // Progress bar on internal link navigation
    document.addEventListener('click', e => {
        const a = e.target.closest('a[href]');
        if (a && a.origin === location.origin && !a.target && !e.ctrlKey && !e.metaKey && !a.getAttribute('href').startsWith('#') && !a.hasAttribute('download')) startProgress();
    });

    lucide.createIcons();
})();
</script>
@stack('scripts')
</body>
</html>
