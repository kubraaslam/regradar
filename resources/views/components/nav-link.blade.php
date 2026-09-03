@props(['active'])

@php
$classes = ($active ?? false)
    ? 'relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold text-white transition duration-150 ease-in-out after:absolute after:inset-x-3 after:-bottom-px after:h-0.5 after:rounded-full after:bg-brand-gradient after:shadow-glow-sm focus:outline-none'
    : 'relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-400 transition duration-150 ease-in-out hover:bg-white/[.05] hover:text-slate-100 focus:text-slate-100 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
