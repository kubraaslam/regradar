<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" fill="none" {{ $attributes }}>
    <defs>
        <linearGradient id="rr-brand" x1="4" y1="4" x2="44" y2="44" gradientUnits="userSpaceOnUse">
            <stop stop-color="#5EEAD4" />
            <stop offset=".5" stop-color="#14B8A6" />
            <stop offset="1" stop-color="#0D9488" />
        </linearGradient>
        <linearGradient id="rr-sweep" x1="24" y1="24" x2="44" y2="10" gradientUnits="userSpaceOnUse">
            <stop stop-color="#14B8A6" stop-opacity=".9" />
            <stop offset="1" stop-color="#14B8A6" stop-opacity="0" />
        </linearGradient>
    </defs>

    {{-- Plate --}}
    <rect x="1.5" y="1.5" width="45" height="45" rx="12" fill="#0F172A" stroke="url(#rr-brand)" stroke-opacity=".45"
        stroke-width="1.5" />

    {{-- Radar rings --}}
    <circle cx="24" cy="24" r="15" stroke="url(#rr-brand)" stroke-opacity=".22" stroke-width="1.5" />
    <circle cx="24" cy="24" r="10" stroke="url(#rr-brand)" stroke-opacity=".38" stroke-width="1.5" />
    <circle cx="24" cy="24" r="5" stroke="url(#rr-brand)" stroke-opacity=".6" stroke-width="1.5" />

    {{-- Crosshairs --}}
    <path d="M24 7v34M7 24h34" stroke="url(#rr-brand)" stroke-opacity=".14" stroke-width="1.5"
        stroke-linecap="round" />

    {{-- Sweep --}}
    <path d="M24 24 L39 13.5 A19 19 0 0 1 41 26 Z" fill="url(#rr-sweep)" />

    {{-- Core --}}
    <circle cx="24" cy="24" r="2.75" fill="url(#rr-brand)" />

    {{-- Detected risk blip --}}
    <circle cx="33" cy="17" r="3" fill="#F43F5E" />
    <circle cx="33" cy="17" r="3" fill="#F43F5E" fill-opacity=".35" class="animate-pulse-ring"
        style="transform-origin: 33px 17px" />
</svg>
