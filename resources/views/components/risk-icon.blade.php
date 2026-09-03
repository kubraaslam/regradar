@props(['level' => 'Low', 'size' => 'h-3.5 w-3.5'])

{{--
    Jira-style severity glyph: chevrons up for High, stacked bars for Medium,
    chevron down for Low. Inherits colour from its surroundings via currentColor,
    so it drops into .badge-high / -medium / -low unchanged.

    Size comes from the `size` prop rather than a merged class, so callers never
    depend on Tailwind's utility ordering to win an override.
--}}
@php
    $path = match ($level) {
        // Double chevron up
        'High' => 'M6 12.25L12 6.75l6 5.5M6 17.25L12 11.75l6 5.5',
        // Two stacked bars
        'Medium' => 'M6 9.5h12M6 14.5h12',
        // Chevron down
        'Low' => 'M6 9.25L12 14.75l6-5.5',
        // Unknown level: a neutral dot
        default => 'M12 12h.01',
    };
@endphp

<svg {{ $attributes->class(['shrink-0', $size]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
    stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $path }}" />
</svg>
