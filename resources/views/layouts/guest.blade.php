<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F172A">

    <title>{{ config('app.name', 'RegRadar') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|jetbrains-mono:400,500&display=swap"
        rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="w-full max-w-md animate-fade-up">
            {{-- Brand --}}
            <a href="/" class="group mb-8 flex flex-col items-center gap-3">
                <x-application-logo class="h-14 w-14 transition-transform duration-500 group-hover:scale-105" />
                <span class="text-2xl font-bold tracking-tight text-slate-100">
                    Reg<span class="gradient-text">Radar</span>
                </span>
                <span class="text-sm text-slate-500">Know what a pull request might break.</span>
            </a>

            {{-- Card --}}
            <div class="card hairline-top card-pad shadow-lift">
                {{ $slot }}
            </div>

            <p class="mt-6 text-center text-xs text-slate-600">
                &copy; {{ date('Y') }} {{ config('app.name', 'RegRadar') }}
            </p>
        </div>
    </div>
</body>

</html>
