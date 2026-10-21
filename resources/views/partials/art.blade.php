<svg class="art" viewBox="0 0 400 290" aria-hidden="true" focusable="false">
    <g transform="rotate(-7 170 160)">
        <rect x="14" y="64" width="300" height="230" rx="22" fill="#fff"/>
        <path d="M14 86a22 22 0 0 1 22-22h256a22 22 0 0 1 22 22v40H14z" fill="#3b82f6"/>
        @for($i = 0; $i < 7; $i++)
            <rect x="{{ 40 + $i * 40 }}" y="40" width="16" height="50" rx="8" fill="#c3cfe2"/>
        @endfor
        @for($r = 0; $r < 3; $r++)
            @for($c = 0; $c < 5; $c++)
                <rect x="{{ 34 + $c * 54 }}" y="{{ 146 + $r * 46 }}" width="36" height="32" rx="8" fill="#fff" stroke="#cbd5e1" stroke-width="2"/>
            @endfor
        @endfor
    </g>
    <path d="M292 108 372 66l10 20-80 44z" fill="#9db8e6"/>
    <path d="m276 128 20-12 6 12-14 22z" fill="#f3c5b5"/>
    <circle cx="268" cy="212" r="76" fill="#3b82f6"/>
    <circle cx="268" cy="212" r="60" fill="#fff"/>
    <g stroke="#cbd5e1" stroke-width="3" stroke-linecap="round">
        <path d="M268 160v8M268 256v8M216 212h8M312 212h8"/>
    </g>
    <path d="M268 212v-34" stroke="#93b4e8" stroke-width="5" stroke-linecap="round"/>
    <path d="m268 212 24 16" stroke="#3b82f6" stroke-width="5" stroke-linecap="round"/>
    <circle cx="268" cy="212" r="8" fill="#334155"/>
</svg>
