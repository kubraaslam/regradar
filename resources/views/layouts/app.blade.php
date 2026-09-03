@props(['width' => 'max-w-7xl'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F172A">

    <title>{{ isset($title) ? $title . ' · ' : '' }}{{ config('app.name', 'RegRadar') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|jetbrains-mono:400,500&display=swap"
        rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen font-sans">
    <div class="flex min-h-screen flex-col">
        @include('layouts.navigation')

        {{-- Page Heading --}}
        @isset($header)
            <header class="border-b border-white/[.06] bg-ink-900/40">
                <div class="mx-auto {{ $width }} px-4 py-7 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        {{-- Flash messages --}}
        @if (session('success') || session('error') || session('warning'))
            <div class="mx-auto w-full {{ $width }} px-4 pt-6 sm:px-6 lg:px-8">
                @if (session('success'))
                    <div class="alert-success animate-fade-up">
                        <svg class="mt-px h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="alert-warning animate-fade-up">
                        <svg class="mt-px h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert-error animate-fade-up">
                        <svg class="mt-px h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- Page Content --}}
        <main class="flex-1">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="mt-8 border-t border-white/[.06]">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'RegRadar') }}. Regression risk radar for pull
                    requests.</p>
                <p class="flex items-center gap-2">
                    <span class="dot bg-brand-500 shadow-glow-sm"></span>
                    All systems operational
                </p>
            </div>
        </footer>
    </div>
</body>

</html>
