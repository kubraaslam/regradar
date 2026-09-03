<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Overview</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-100">
                    Welcome back, {{ explode(' ', Auth::user()->name)[0] }}
                </h2>
                <p class="mt-1 text-sm text-slate-400">
                    Regression risk across every pull request you have analysed.
                </p>
            </div>
            <a href="{{ route('analysis.create') }}" class="btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                </svg>
                Analyse a pull request
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

        {{-- Stat tiles --}}
        @php
            $tiles = [
                [
                    'label' => 'Analyses run',
                    'value' => $stats['analyses'],
                    'hint' => $stats['completed'] . ' completed',
                    'tone' => 'brand',
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                ],
                [
                    'label' => 'High risk findings',
                    'value' => $stats['high'],
                    'hint' => 'Needs regression testing',
                    'tone' => 'flare',
                    'risk' => 'High',
                ],
                [
                    'label' => 'Medium risk findings',
                    'value' => $stats['medium'],
                    'hint' => 'Worth a smoke test',
                    'tone' => 'amber',
                    'risk' => 'Medium',
                ],
                [
                    'label' => 'Features tracked',
                    'value' => $stats['features'],
                    'hint' => 'In your registry',
                    'tone' => 'slate',
                    'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
                ],
            ];

            $tones = [
                'brand' => ['ring' => 'border-brand-500/25', 'chip' => 'bg-brand-500/10 text-brand-300', 'num' => 'text-slate-100'],
                'flare' => ['ring' => 'border-flare-500/25', 'chip' => 'bg-flare-500/10 text-flare-300', 'num' => 'text-flare-400'],
                'amber' => ['ring' => 'border-amber-400/25', 'chip' => 'bg-amber-400/10 text-amber-300', 'num' => 'text-amber-300'],
                'slate' => ['ring' => 'border-white/[.07]', 'chip' => 'bg-white/[.06] text-slate-300', 'num' => 'text-slate-100'],
            ];
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($tiles as $i => $tile)
                @php $t = $tones[$tile['tone']]; @endphp
                <div class="card-interactive card-pad animate-fade-up {{ $t['ring'] }}"
                    style="animation-delay: {{ $i * 60 }}ms">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-slate-400">{{ $tile['label'] }}</p>
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $t['chip'] }}">
                            @isset($tile['risk'])
                                <x-risk-icon :level="$tile['risk']" size="h-[18px] w-[18px]" />
                            @else
                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tile['icon'] }}" />
                                </svg>
                            @endisset
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-bold tabular-nums {{ $t['num'] }}">{{ $tile['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $tile['hint'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">

            {{-- Recent analyses --}}
            <div class="card lg:col-span-2">
                <div class="flex items-center justify-between gap-4 border-b border-white/[.06] px-5 py-4 sm:px-6">
                    <h3 class="text-sm font-semibold text-slate-200">Recent analyses</h3>
                    <a href="{{ route('analysis.index') }}" class="link text-xs font-semibold">View all &rarr;</a>
                </div>

                <div class="divide-hairline">
                    @forelse ($recent as $analysis)
                        <a href="{{ route('analysis.show', $analysis) }}"
                            class="row-hover flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-200">
                                    {{ Str::after($analysis->pr_url, 'github.com/') ?: $analysis->pr_url }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">{{ $analysis->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if ($analysis->status === 'completed' && $analysis->results)
                                    @if (($analysis->results['total_high'] ?? 0) > 0)
                                        <span class="badge-high"><x-risk-icon level="High" />{{ $analysis->results['total_high'] }} High</span>
                                    @endif
                                    @if (($analysis->results['total_medium'] ?? 0) > 0)
                                        <span class="badge-medium"><x-risk-icon level="Medium" />{{ $analysis->results['total_medium'] }} Med</span>
                                    @endif
                                    @if (($analysis->results['total_high'] ?? 0) === 0 && ($analysis->results['total_medium'] ?? 0) === 0)
                                        <span class="badge-low"><x-risk-icon level="Low" />Clear</span>
                                    @endif
                                @else
                                    <span class="badge-neutral">{{ ucfirst($analysis->status) }}</span>
                                @endif
                                <svg class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <x-application-logo class="mx-auto h-12 w-12 opacity-60" />
                            <p class="mt-4 text-sm font-medium text-slate-300">No analyses yet</p>
                            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                                Paste a GitHub pull request URL and RegRadar will map the diff onto your feature
                                registry.
                            </p>
                            <a href="{{ route('analysis.create') }}" class="btn-primary btn-sm mt-5">
                                Run your first analysis
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Side column --}}
            <div class="space-y-6">
                <div class="card card-pad">
                    <h3 class="text-sm font-semibold text-slate-200">Quick actions</h3>
                    <div class="mt-4 space-y-2">
                        <a href="{{ route('analysis.create') }}"
                            class="flex items-center gap-3 rounded-xl border border-white/[.07] bg-white/[.02] px-3.5 py-3 transition hover:border-brand-500/40 hover:bg-white/[.05]">
                            <span
                                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-500/10 text-brand-300">
                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-slate-200">New analysis</span>
                                <span class="block text-xs text-slate-500">Scan a pull request diff</span>
                            </span>
                        </a>

                        <a href="{{ route('feature-registry.index') }}"
                            class="flex items-center gap-3 rounded-xl border border-white/[.07] bg-white/[.02] px-3.5 py-3 transition hover:border-brand-500/40 hover:bg-white/[.05]">
                            <span
                                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/[.06] text-slate-300">
                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-slate-200">Feature registry</span>
                                <span class="block text-xs text-slate-500">
                                    {{ $stats['features'] }} {{ Str::plural('feature', $stats['features']) }} mapped
                                </span>
                            </span>
                        </a>

                        <a href="{{ route('analysis.index') }}"
                            class="flex items-center gap-3 rounded-xl border border-white/[.07] bg-white/[.02] px-3.5 py-3 transition hover:border-brand-500/40 hover:bg-white/[.05]">
                            <span
                                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/[.06] text-slate-300">
                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-slate-200">Analysis history</span>
                                <span class="block text-xs text-slate-500">Every past report</span>
                            </span>
                        </a>
                    </div>
                </div>

                {{-- Risk mix --}}
                @php $totalFindings = $stats['high'] + $stats['medium'] + $stats['low']; @endphp
                @if ($totalFindings > 0)
                    @php $pct = fn ($n) => round(($n / $totalFindings) * 100); @endphp
                    <div class="card card-pad">
                        <h3 class="text-sm font-semibold text-slate-200">Risk mix</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Across {{ $totalFindings }} {{ Str::plural('finding', $totalFindings) }}
                        </p>

                        <div class="mt-4 flex h-2 overflow-hidden rounded-full bg-white/[.06]">
                            <div class="bg-flare-500" style="width: {{ $pct($stats['high']) }}%"></div>
                            <div class="bg-amber-400" style="width: {{ $pct($stats['medium']) }}%"></div>
                            <div class="bg-brand-500" style="width: {{ $pct($stats['low']) }}%"></div>
                        </div>

                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="flex items-center gap-2 text-slate-400">
                                    <x-risk-icon level="High" size="h-4 w-4" class="text-flare-400" /> High
                                </dt>
                                <dd class="font-semibold tabular-nums text-slate-200">{{ $stats['high'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="flex items-center gap-2 text-slate-400">
                                    <x-risk-icon level="Medium" size="h-4 w-4" class="text-amber-300" /> Medium
                                </dt>
                                <dd class="font-semibold tabular-nums text-slate-200">{{ $stats['medium'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="flex items-center gap-2 text-slate-400">
                                    <x-risk-icon level="Low" size="h-4 w-4" class="text-brand-400" /> Low
                                </dt>
                                <dd class="font-semibold tabular-nums text-slate-200">{{ $stats['low'] }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
