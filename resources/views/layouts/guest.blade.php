<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') · CargoTrace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] }, colors: { navy: '#0B1628', midnight: '#14243B', ocean: '#176B87', aqua: '#29C7D8', mist: '#F7F9FC' } } } };</script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.441.0/dist/umd/lucide.min.js"></script>
    @php $photo = file_exists(public_path('images/login-bg.jpg')); @endphp
    <style>
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes drift { 0%,100% { transform: translateX(0); } 50% { transform: translateX(14px); } }
        .rise { animation: rise .6s ease both; }
        .drift { animation: drift 9s ease-in-out infinite; }
        .bg-scene { background-color: #0B1628; @if ($photo) background-image: url('{{ asset('images/login-bg.jpg') }}'); background-size: cover; background-position: center; @endif }
    </style>
</head>
<body class="font-sans antialiased">
<div class="bg-scene relative min-h-screen overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-navy/95 via-midnight/90 to-ocean/70"></div>
    @unless ($photo)
        <div class="absolute inset-x-0 bottom-0 opacity-90"><x-logistics-art class="drift w-full" /></div>
        <div class="absolute -left-24 -top-24 h-96 w-96 rounded-full bg-aqua/10 blur-3xl"></div>
    @endunless
    <div class="relative z-10 mx-auto grid min-h-screen max-w-6xl items-center gap-10 px-5 py-10 lg:grid-cols-2">
        <div class="rise hidden text-white lg:block">
            <div class="mb-8 flex items-center gap-3">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-aqua to-ocean shadow-xl shadow-aqua/30"><i data-lucide="ship" class="h-8 w-8"></i></span>
                <span class="text-4xl font-bold tracking-tight">CargoTrace</span>
            </div>
            <h2 class="max-w-md text-3xl font-semibold leading-snug">Connecting Global Trade, One Shipment at a Time.</h2>
            <p class="mt-4 max-w-md text-slate-300">Track sea, air and land freight, monitor delays and exceptions, and keep every stakeholder informed from a single platform.</p>
            <div class="mt-8 flex gap-6 text-sm text-slate-200">
                <span class="flex items-center gap-2"><i data-lucide="ship" class="h-4 w-4 text-aqua"></i>Sea</span>
                <span class="flex items-center gap-2"><i data-lucide="plane" class="h-4 w-4 text-aqua"></i>Air</span>
                <span class="flex items-center gap-2"><i data-lucide="truck" class="h-4 w-4 text-aqua"></i>Land</span>
            </div>
        </div>
        <div class="rise mx-auto w-full max-w-md" style="animation-delay:.1s">
            <div class="mb-6 flex items-center justify-center gap-3 text-white lg:hidden">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-aqua to-ocean"><i data-lucide="ship"></i></span>
                <span class="text-3xl font-bold">CargoTrace</span>
            </div>
            <div class="rounded-3xl border border-white/20 bg-white/95 p-8 shadow-2xl backdrop-blur">
                @yield('content')
            </div>
            <p class="mt-4 text-center text-xs text-slate-300">© {{ date('Y') }} CargoTrace · Web Programming 2 UAS</p>
        </div>
    </div>
</div>
<script>lucide.createIcons();</script>
@stack('scripts')
</body>
</html>
