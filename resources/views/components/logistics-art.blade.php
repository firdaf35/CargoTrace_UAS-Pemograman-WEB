{{-- Original vector illustration: container ship, cargo plane, container truck and port waves. --}}
@php
    $colors = ['#29C7D8', '#176B87', '#F2A33A', '#E8F1F8', '#3D8FB5', '#E4572E', '#7FD1AE'];
@endphp
<svg viewBox="0 0 640 250" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Container ship, cargo plane and truck illustration" {{ $attributes }}>
    <defs>
        <linearGradient id="seaG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#29C7D8" stop-opacity=".35"/><stop offset="1" stop-color="#176B87" stop-opacity=".1"/></linearGradient>
        <linearGradient id="hullG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1B3A5C"/><stop offset="1" stop-color="#0B1628"/></linearGradient>
    </defs>

    <circle cx="96" cy="54" r="30" fill="#29C7D8" opacity=".12"/>
    <circle cx="96" cy="54" r="16" fill="#29C7D8" opacity=".2"/>

    {{-- cargo plane --}}
    <g transform="translate(400 36) rotate(-7)">
        <path d="M0 24 Q10 8 44 10 L128 13 Q150 15 152 24 Q150 33 128 35 L44 38 Q10 40 0 24Z" fill="#E8F1F8"/>
        <path d="M6 14 L-2 -8 L16 -8 L32 12Z" fill="#CFE0EC"/>
        <path d="M60 30 L96 66 L112 66 L92 28Z" fill="#B9D3E4"/>
        <path d="M60 18 L92 -14 L104 -14 L88 18Z" fill="#CFE0EC"/>
        <rect x="118" y="18" width="22" height="7" rx="3" fill="#29C7D8"/>
        <rect x="40" y="20" width="44" height="8" rx="2" fill="#176B87" opacity=".35"/>
        <circle cx="78" cy="39" r="6" fill="#0B1628" opacity=".5"/>
    </g>
    <path d="M300 78 C 340 60, 380 64, 410 58" fill="none" stroke="#29C7D8" stroke-width="1.5" stroke-dasharray="3 6" opacity=".6"/>

    {{-- container ship --}}
    <g>
        <rect x="56" y="132" width="46" height="64" rx="3" fill="#E8F1F8"/>
        <rect x="46" y="120" width="66" height="16" rx="3" fill="#CFE0EC"/>
        @for ($w = 0; $w < 4; $w++)<rect x="{{ 62 + $w * 10 }}" y="142" width="6" height="8" rx="1" fill="#176B87"/>@endfor
        <rect x="76" y="100" width="4" height="22" fill="#CFE0EC"/>
        @for ($row = 0; $row < 4; $row++)
            @for ($col = 0; $col < 9; $col++)
                <rect x="{{ 118 + $col * 30 }}" y="{{ 176 - $row * 19 }}" width="28" height="17" rx="2" fill="{{ $colors[($row * 3 + $col * 2) % count($colors)] }}"/>
                <rect x="{{ 118 + $col * 30 }}" y="{{ 176 - $row * 19 }}" width="28" height="3" rx="1" fill="#fff" opacity=".25"/>
            @endfor
        @endfor
        <path d="M30 196 L400 196 L372 226 L58 226 Z" fill="url(#hullG)"/>
        <rect x="36" y="196" width="364" height="5" fill="#29C7D8"/>
    </g>

    {{-- waves --}}
    <path d="M0 222 Q 40 210 80 222 T 160 222 T 240 222 T 320 222 T 400 222 T 480 222 T 560 222 T 640 222 V250 H0Z" fill="url(#seaG)"/>
    <path d="M0 232 Q 50 222 100 232 T 200 232 T 300 232 T 400 232 T 500 232 T 600 232 T 700 232 V250 H0Z" fill="#29C7D8" opacity=".18"/>

    {{-- truck --}}
    <g>
        <rect x="440" y="170" width="112" height="44" rx="3" fill="#176B87"/>
        <rect x="440" y="170" width="112" height="6" rx="2" fill="#29C7D8"/>
        @for ($r = 0; $r < 6; $r++)<rect x="{{ 450 + $r * 18 }}" y="182" width="3" height="28" fill="#fff" opacity=".25"/>@endfor
        <path d="M556 184 h30 l16 18 v18 h-46z" fill="#E8F1F8"/>
        <path d="M566 188 h16 l10 12 h-26z" fill="#176B87" opacity=".6"/>
        <rect x="430" y="214" width="180" height="5" rx="2" fill="#0B1628" opacity=".5"/>
        <circle cx="462" cy="220" r="9" fill="#0B1628"/><circle cx="462" cy="220" r="4" fill="#CFE0EC"/>
        <circle cx="494" cy="220" r="9" fill="#0B1628"/><circle cx="494" cy="220" r="4" fill="#CFE0EC"/>
        <circle cx="590" cy="220" r="9" fill="#0B1628"/><circle cx="590" cy="220" r="4" fill="#CFE0EC"/>
    </g>
</svg>
