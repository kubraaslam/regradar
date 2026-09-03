@props(['active'])

@php
$classes = ($active ?? false)
    ? 'block w-full border-s-2 border-brand-500 bg-brand-500/10 py-2 pe-4 ps-4 text-start text-base font-semibold text-brand-300 transition duration-150 ease-in-out focus:outline-none'
    : 'block w-full border-s-2 border-transparent py-2 pe-4 ps-4 text-start text-base font-medium text-slate-400 transition duration-150 ease-in-out hover:border-white/20 hover:bg-white/[.04] hover:text-slate-100 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
