<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0F172A">

    <title>{{ config('app.name', 'RegRadar') }}: regression risk radar for pull requests</title>
    <meta name="description"
        content="RegRadar reads a GitHub pull request diff, maps it onto your feature registry and tells you exactly what to regression test before you merge.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|jetbrains-mono:400,500&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans">

    {{-- Nav --}}
    <header class="sticky top-0 z-40 border-b border-white/[.07] bg-ink-900/70 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="/" class="group flex items-center gap-2.5">
                <x-application-logo class="h-9 w-9 transition-transform duration-500 group-hover:scale-105" />
                <span class="text-lg font-bold tracking-tight text-slate-100">
                    Reg<span class="gradient-text">Radar</span>
                </span>
            </a>

            @if (Route::has('login'))
                <nav class="flex items-center gap-2">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-primary btn-sm">Open dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost btn-sm">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-primary btn-sm">Get started</a>
                        @endif
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    <main>
        {{-- Hero --}}
        <section class="relative overflow-hidden">
            <div class="mx-auto max-w-6xl px-4 pb-20 pt-20 sm:px-6 lg:px-8 lg:pt-28">
                <div class="grid items-center gap-14 lg:grid-cols-2">

                    <div class="animate-fade-up">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-brand-500/30 bg-brand-500/[.08] px-3 py-1 text-xs font-semibold text-brand-300">
                            <span class="dot bg-brand-400"></span>
                            AI-assisted regression triage
                        </span>

                        <h1 class="mt-6 text-4xl font-bold leading-[1.1] tracking-tight text-slate-50 sm:text-5xl">
                            Know what a pull request
                            <span class="gradient-text">might break</span>
                            <span class="block">before you merge it.</span>
                        </h1>

                        <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-400">
                            RegRadar reads the diff, matches it against your own feature registry, and hands your QA
                            team a ranked list of what to regression test. No more guessing which corner of the app
                            just moved.
                        </p>

                        <div class="mt-9 flex flex-wrap items-center gap-3">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn-primary">Open dashboard</a>
                            @else
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn-primary">
                                        Start analysing
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                    </a>
                                @endif
                                <a href="{{ route('login') }}" class="btn-secondary">Log in</a>
                            @endauth
                        </div>

                        <dl class="mt-12 grid max-w-md grid-cols-3 gap-6">
                            <div>
                                <dt class="text-2xl font-bold text-slate-100">&lt;30s</dt>
                                <dd class="mt-1 text-xs text-slate-500">per pull request</dd>
                            </div>
                            <div>
                                <dt class="text-2xl font-bold text-slate-100">3</dt>
                                <dd class="mt-1 text-xs text-slate-500">risk tiers</dd>
                            </div>
                            <div>
                                <dt class="text-2xl font-bold text-slate-100">0</dt>
                                <dd class="mt-1 text-xs text-slate-500">config files to write</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Mock report --}}
                    <div class="animate-fade-up lg:justify-self-end" style="animation-delay: 120ms">
                        <div class="card w-full shadow-lift lg:max-w-md">
                            <div class="flex items-center gap-2 border-b border-white/[.06] px-4 py-3">
                                <span class="dot bg-flare-500"></span>
                                <span class="dot bg-amber-400"></span>
                                <span class="dot bg-brand-500"></span>
                                <span class="ms-2 truncate font-mono text-xs text-slate-500">
                                    acme/checkout-api · pull/482
                                </span>
                            </div>

                            <div class="space-y-3 p-4">
                                <div class="rounded-xl border border-flare-500/25 bg-flare-500/[.07] p-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-100">Payment capture</span>
                                        <span class="badge-high"><x-risk-icon level="High" />High risk</span>
                                    </div>
                                    <p class="mt-1.5 text-xs leading-relaxed text-slate-400">
                                        PaymentController::capture was modified and the retry branch changed.
                                    </p>
                                </div>

                                <div class="rounded-xl border border-amber-400/25 bg-amber-400/[.06] p-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-100">Order webhooks</span>
                                        <span class="badge-medium"><x-risk-icon level="Medium" />Medium risk</span>
                                    </div>
                                    <p class="mt-1.5 text-xs leading-relaxed text-slate-400">
                                        Shares the OrderService dependency touched by this diff.
                                    </p>
                                </div>

                                <div class="rounded-xl border border-white/[.07] bg-white/[.02] p-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-100">User profile</span>
                                        <span class="badge-low"><x-risk-icon level="Low" />Low risk</span>
                                    </div>
                                    <p class="mt-1.5 text-xs leading-relaxed text-slate-400">
                                        Only a shared validation helper was touched.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between border-t border-white/[.06] px-4 py-3">
                                <span class="text-xs text-slate-500">3 features flagged</span>
                                <span class="font-mono text-xs text-brand-400">analysis complete</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section class="border-y border-white/[.06] bg-ink-900/40">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <p class="eyebrow">How it works</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-100">
                        Three steps between a diff and a test plan
                    </h2>
                </div>

                @php
                    $steps = [
                        [
                            'n' => '01',
                            'title' => 'Register your features',
                            'body' => 'List the features that matter and the controllers, services and endpoints behind each one. This is the only setup RegRadar needs.',
                            'icon' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
                        ],
                        [
                            'n' => '02',
                            'title' => 'Paste a pull request URL',
                            'body' => 'RegRadar pulls the diff from GitHub, extracts the changed files and functions, and strips out the noise.',
                            'icon' => 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244',
                        ],
                        [
                            'n' => '03',
                            'title' => 'Get a ranked risk report',
                            'body' => 'Every affected feature comes back rated High, Medium or Low, with a plain-English reason your QA team can act on.',
                            'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                        ],
                    ];
                @endphp

                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach ($steps as $i => $step)
                        <div class="card-interactive card-pad animate-fade-up"
                            style="animation-delay: {{ $i * 80 }}ms">
                            <div class="flex items-center justify-between">
                                <span
                                    class="grid h-11 w-11 place-items-center rounded-xl bg-brand-500/10 text-brand-300">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $step['icon'] }}" />
                                    </svg>
                                </span>
                                <span class="font-mono text-xs font-semibold text-slate-600">{{ $step['n'] }}</span>
                            </div>
                            <h3 class="mt-5 text-base font-semibold text-slate-100">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $step['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Risk tiers --}}
        <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <p class="eyebrow">Risk tiers</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-100">
                        A verdict your QA team can plan around
                    </h2>
                    <p class="mt-4 max-w-lg text-slate-400">
                        Every finding lands in one of three tiers, so a release manager can tell at a glance whether a
                        pull request needs a full regression pass or a five-minute smoke test.
                    </p>
                </div>

                <div class="space-y-3">
                    <div class="flex items-start gap-4 rounded-2xl border border-flare-500/25 bg-flare-500/[.06] p-5">
                        <span
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-flare-500/15 text-flare-400">
                            <x-risk-icon level="High" size="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-flare-300">High risk</h3>
                            <p class="mt-1 text-sm text-slate-400">
                                The feature's own code was modified directly. Run the full regression suite.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 rounded-2xl border border-amber-400/25 bg-amber-400/[.05] p-5">
                        <span
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-400/15 text-amber-300">
                            <x-risk-icon level="Medium" size="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-amber-300">Medium risk</h3>
                            <p class="mt-1 text-sm text-slate-400">
                                A shared dependency moved. Worth a targeted smoke test.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 rounded-2xl border border-brand-500/25 bg-brand-500/[.06] p-5">
                        <span
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-500/15 text-brand-400">
                            <x-risk-icon level="Low" size="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-brand-300">Low risk</h3>
                            <p class="mt-1 text-sm text-slate-400">
                                Only loosely related code changed. Keep an eye on it, nothing more.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="mx-auto max-w-6xl px-4 pb-24 sm:px-6 lg:px-8">
            <div class="card hairline-top relative overflow-hidden px-6 py-14 text-center sm:px-12">
                <div
                    class="pointer-events-none absolute -top-24 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-brand-500/20 blur-3xl">
                </div>

                <div class="relative">
                    <x-application-logo class="mx-auto h-12 w-12" />
                    <h2 class="mt-6 text-3xl font-bold tracking-tight text-slate-50">
                        Stop shipping blind
                    </h2>
                    <p class="mx-auto mt-3 max-w-lg text-slate-400">
                        Register your features once, then get a regression risk report on every pull request you open.
                    </p>

                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-primary">Open dashboard</a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-primary">Create an account</a>
                            @endif
                            <a href="{{ route('login') }}" class="btn-secondary">Log in</a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-white/[.06]">
        <div
            class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-8 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
            <p class="flex items-center gap-2">
                <x-application-logo class="h-5 w-5" />
                &copy; {{ date('Y') }} {{ config('app.name', 'RegRadar') }}
            </p>
            <p>Laravel v{{ Illuminate\Foundation\Application::VERSION }} · PHP v{{ PHP_VERSION }}</p>
        </div>
    </footer>
</body>

</html>
