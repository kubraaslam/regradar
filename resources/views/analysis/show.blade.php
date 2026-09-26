<x-app-layout width="max-w-5xl">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <p class="eyebrow">Report</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-100">Regression risk report</h2>
                <p class="mt-1 truncate font-mono text-xs text-slate-500">
                    {{ Str::after($analysis->pr_url, 'github.com/') ?: $analysis->pr_url }}
                </p>
            </div>
            <a href="{{ $analysis->pr_url }}" target="_blank" rel="noopener noreferrer" class="btn-secondary btn-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12 .5A11.5 11.5 0 00.5 12a11.5 11.5 0 007.86 10.92c.58.1.79-.25.79-.55v-1.94c-3.2.7-3.88-1.54-3.88-1.54-.53-1.34-1.29-1.7-1.29-1.7-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.55-.29-5.24-1.28-5.24-5.7 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 015.8 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.12 3.05.74.81 1.18 1.84 1.18 3.1 0 4.43-2.69 5.4-5.25 5.69.41.36.78 1.05.78 2.12v3.14c0 .3.2.66.79.55A11.5 11.5 0 0023.5 12A11.5 11.5 0 0012 .5z" />
                </svg>
                Open on GitHub
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">

        @if ($analysis->status === 'completed' && $analysis->results)
            @php
                $r = $analysis->results;
                $high = $r['total_high'] ?? 0;
                $medium = $r['total_medium'] ?? 0;
                $low = $r['total_low'] ?? 0;
                $total = $high + $medium + $low;

                $verdict = $high > 0
                    ? ['label' => 'High risk', 'tone' => 'flare', 'level' => 'High', 'copy' => 'Run a full regression pass on the features below before merging.']
                    : ($medium > 0
                        ? ['label' => 'Moderate risk', 'tone' => 'amber', 'level' => 'Medium', 'copy' => 'A targeted smoke test over the affected features should be enough.']
                        : ['label' => 'Low risk', 'tone' => 'brand', 'level' => 'Low', 'copy' => 'Nothing high or medium risk surfaced for this pull request.']);

                $verdictTone = [
                    'flare' => 'border-flare-500/30 bg-flare-500/[.07] text-flare-300',
                    'amber' => 'border-amber-400/30 bg-amber-400/[.07] text-amber-300',
                    'brand' => 'border-brand-500/30 bg-brand-500/[.07] text-brand-300',
                ][$verdict['tone']];
            @endphp

            {{-- Verdict banner --}}
            <div class="card hairline-top animate-fade-up overflow-hidden">
                <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="flex items-start gap-4">
                        <span
                            class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border {{ $verdictTone }}">
                            <x-risk-icon :level="$verdict['level']" size="h-6 w-6" />
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-slate-100">{{ $verdict['label'] }}</h3>
                                <span class="badge-neutral">{{ $total }} {{ Str::plural('finding', $total) }}</span>
                            </div>
                            <p class="mt-1 max-w-xl text-sm text-slate-400">{{ $verdict['copy'] }}</p>
                        </div>
                    </div>

                    <div class="text-xs text-slate-500 sm:text-right">
                        <p>Analysed</p>
                        <p class="mt-0.5 font-medium text-slate-400">
                            {{ $analysis->created_at->format('j M Y, H:i') }}
                        </p>
                        <p class="mt-2">
                            <span class="badge-neutral font-mono">
                                {{ \App\Services\LLMService::MODEL_LABELS[$analysis->llm_model] ?? $analysis->llm_model }}
                                <span class="text-slate-600">·</span>
                                {{ \App\Services\LLMService::STRATEGY_LABELS[$analysis->prompting_strategy] ?? $analysis->prompting_strategy }}
                            </span>
                        </p>
                        @if ($analysis->duration_ms)
                            <p class="mt-1.5 font-mono text-[.7rem] text-slate-600">
                                {{ number_format($analysis->duration_ms / 1000, 1) }}s
                                @if ($analysis->prompt_tokens)
                                    <span class="text-slate-700">·</span>
                                    {{ number_format($analysis->prompt_tokens + $analysis->completion_tokens) }} tokens
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                @if ($total > 0)
                    @php $pct = fn ($n) => round(($n / $total) * 100); @endphp
                    <div class="flex h-1.5">
                        <div class="bg-flare-500" style="width: {{ $pct($high) }}%"></div>
                        <div class="bg-amber-400" style="width: {{ $pct($medium) }}%"></div>
                        <div class="bg-brand-500" style="width: {{ $pct($low) }}%"></div>
                    </div>
                @endif
            </div>

            {{-- AI summary --}}
            @if (!empty($r['summary']))
                <div class="card card-pad animate-fade-up">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                        </svg>
                        <h3 class="eyebrow">Summary</h3>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">{{ $r['summary'] }}</p>

                    @if (!empty($r['testing_focus']))
                        <div class="mt-5 border-t border-white/[.06] pt-4">
                            <h4 class="eyebrow">What to test</h4>
                            <ul class="mt-3 space-y-2">
                                @foreach ($r['testing_focus'] as $item)
                                    <li class="flex items-start gap-2.5 text-sm text-slate-300">
                                        <svg class="mt-1 h-3.5 w-3.5 shrink-0 text-brand-400" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Risk counts --}}
            <div class="grid gap-4 sm:grid-cols-3">
                @php
                    $counts = [
                        ['label' => 'High risk', 'level' => 'High', 'value' => $high, 'num' => 'text-flare-400', 'ring' => 'border-flare-500/25', 'bar' => 'bg-flare-500'],
                        ['label' => 'Medium risk', 'level' => 'Medium', 'value' => $medium, 'num' => 'text-amber-300', 'ring' => 'border-amber-400/25', 'bar' => 'bg-amber-400'],
                        ['label' => 'Low risk', 'level' => 'Low', 'value' => $low, 'num' => 'text-brand-400', 'ring' => 'border-brand-500/25', 'bar' => 'bg-brand-500'],
                    ];
                @endphp

                @foreach ($counts as $i => $c)
                    <div class="card card-pad animate-fade-up {{ $c['ring'] }}"
                        style="animation-delay: {{ $i * 60 }}ms">
                        <p class="flex items-center gap-2 text-sm font-medium text-slate-400">
                            <x-risk-icon :level="$c['level']" size="h-4 w-4" :class="$c['num']" />
                            {{ $c['label'] }}
                        </p>
                        <p class="mt-2 text-4xl font-bold tabular-nums {{ $c['num'] }}">{{ $c['value'] }}</p>
                        <div class="mt-4 h-1 overflow-hidden rounded-full bg-white/[.06]">
                            <div class="h-full {{ $c['bar'] }} rounded-full"
                                style="width: {{ $total > 0 ? round(($c['value'] / $total) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Risk list --}}
            <div class="card animate-fade-up">
                <div class="border-b border-white/[.06] px-5 py-4 sm:px-6">
                    <h3 class="text-sm font-semibold text-slate-200">Features at risk</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Ranked highest risk first.</p>
                </div>

                <div class="divide-hairline">
                    @forelse ($r['risks'] as $risk)
                        @php
                            $level = $risk['risk_level'] ?? 'Unknown';
                            $accent = match ($level) {
                                'High' => 'bg-flare-500',
                                'Medium' => 'bg-amber-400',
                                'Low' => 'bg-brand-500',
                                default => 'bg-slate-600',
                            };
                            $badge = match ($level) {
                                'High' => 'badge-high',
                                'Medium' => 'badge-medium',
                                'Low' => 'badge-low',
                                default => 'badge-neutral',
                            };
                        @endphp

                        <div class="row-hover relative p-5 pl-6 sm:pl-7">
                            <span class="absolute inset-y-4 left-0 w-1 rounded-full {{ $accent }}"></span>
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="font-semibold text-slate-100">{{ $risk['feature'] }}</span>
                                <span class="{{ $badge }}">
                                    <x-risk-icon :level="$level" />{{ $level }} risk
                                </span>
                            </div>
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-400">
                                {{ $risk['reason'] }}
                            </p>
                        </div>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <span
                                class="mx-auto grid h-12 w-12 place-items-center rounded-2xl border border-brand-500/30 bg-brand-500/[.08] text-brand-300">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <p class="mt-4 text-sm font-medium text-slate-300">No features at risk</p>
                            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                                Nothing in this diff mapped to a feature in your registry.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Changed files --}}
            @if (!empty($r['changed_files']))
                <div class="card animate-fade-up" x-data="{ open: true }">
                    <button type="button" @click="open = !open"
                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-white/[.02] sm:px-6">
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-sm font-semibold text-slate-200">Files changed</h3>
                            <span class="badge-neutral">{{ count($r['changed_files']) }}</span>
                        </div>
                        <svg class="h-4 w-4 text-slate-500 transition-transform duration-200"
                            x-bind:class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <ul x-show="open" x-transition.origin.top.duration.200ms
                        class="max-h-80 overflow-y-auto border-t border-white/[.06] px-5 py-3 sm:px-6">
                        @foreach ($r['changed_files'] as $file)
                            <li class="flex items-center gap-2.5 py-1.5 font-mono text-xs text-slate-400">
                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <span class="truncate">{{ $file }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @else
            <div class="card card-pad animate-fade-up text-center">
                <span
                    class="mx-auto grid h-12 w-12 place-items-center rounded-2xl border border-flare-500/30 bg-flare-500/[.08] text-flare-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </span>
                <h3 class="mt-4 text-base font-semibold text-slate-100">This analysis did not complete</h3>
                <p class="mx-auto mt-1.5 max-w-md text-sm text-slate-500">
                    Status: <span class="font-medium text-slate-400">{{ ucfirst($analysis->status) }}</span>.
                    Check that the pull request URL is reachable, then try again.
                </p>
                <a href="{{ route('analysis.create') }}" class="btn-primary mt-6">Try another pull request</a>
            </div>
        @endif

        <div class="flex flex-wrap gap-3 pt-2">
            <a href="{{ route('analysis.create') }}" class="btn-primary">Analyse another pull request</a>
            <a href="{{ route('analysis.index') }}" class="btn-secondary">View all analyses</a>
        </div>
    </div>
</x-app-layout>
