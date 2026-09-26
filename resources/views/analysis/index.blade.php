<x-app-layout width="max-w-5xl">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Archive</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-100">Analysis history</h2>
                <p class="mt-1 text-sm text-slate-400">
                    {{ $analyses->count() }} {{ Str::plural('report', $analyses->count()) }} on record.
                </p>
            </div>
            <a href="{{ route('analysis.create') }}" class="btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                </svg>
                New analysis
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="card animate-fade-up divide-hairline">
            @forelse ($analyses as $analysis)
                @php
                    $results = is_array($analysis->results) ? $analysis->results : null;
                    $high = $results['total_high'] ?? 0;
                    $medium = $results['total_medium'] ?? 0;
                    $low = $results['total_low'] ?? 0;
                @endphp

                <div class="row-hover flex flex-wrap items-center justify-between gap-4 p-5">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-white/[.07] bg-white/[.04] text-slate-400">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12 .5A11.5 11.5 0 00.5 12a11.5 11.5 0 007.86 10.92c.58.1.79-.25.79-.55v-1.94c-3.2.7-3.88-1.54-3.88-1.54-.53-1.34-1.29-1.7-1.29-1.7-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.55-.29-5.24-1.28-5.24-5.7 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 015.8 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.12 3.05.74.81 1.18 1.84 1.18 3.1 0 4.43-2.69 5.4-5.25 5.69.41.36.78 1.05.78 2.12v3.14c0 .3.2.66.79.55A11.5 11.5 0 0023.5 12A11.5 11.5 0 0012 .5z" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-mono text-sm font-medium text-slate-200">
                                    {{ Str::after($analysis->pr_url, 'github.com/') ?: $analysis->pr_url }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $analysis->created_at->diffForHumans() }}
                                    <span class="text-slate-700">·</span>
                                    {{ $analysis->created_at->format('j M Y, H:i') }}
                                    <span class="text-slate-700">·</span>
                                    <span class="font-mono">
                                        {{ \App\Services\LLMService::MODEL_LABELS[$analysis->llm_model] ?? $analysis->llm_model }}
                                        /
                                        {{ \App\Services\LLMService::STRATEGY_LABELS[$analysis->prompting_strategy] ?? $analysis->prompting_strategy }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($analysis->status === 'completed' && $results)
                            @if ($high > 0)
                                <span class="badge-high"><x-risk-icon level="High" />{{ $high }} High</span>
                            @endif
                            @if ($medium > 0)
                                <span class="badge-medium"><x-risk-icon level="Medium" />{{ $medium }} Medium</span>
                            @endif
                            @if ($low > 0)
                                <span class="badge-low"><x-risk-icon level="Low" />{{ $low }} Low</span>
                            @endif
                            @if ($high === 0 && $medium === 0 && $low === 0)
                                <span class="badge-neutral">No findings</span>
                            @endif
                        @elseif ($analysis->status === 'failed')
                            <span class="badge-high">Failed</span>
                        @else
                            <span class="badge-neutral">{{ ucfirst($analysis->status) }}</span>
                        @endif

                        <a href="{{ route('analysis.show', $analysis) }}" class="btn-secondary btn-sm">
                            View report
                        </a>
                    </div>
                </div>
            @empty
                <div class="px-6 py-20 text-center">
                    <x-application-logo class="mx-auto h-14 w-14 opacity-60" />
                    <p class="mt-5 text-base font-semibold text-slate-200">Nothing on the radar yet</p>
                    <p class="mx-auto mt-1.5 max-w-sm text-sm text-slate-500">
                        Submit your first pull request URL and RegRadar will tell you which features to regression test.
                    </p>
                    <a href="{{ route('analysis.create') }}" class="btn-primary mt-6">Run an analysis</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
