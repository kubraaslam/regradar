<x-app-layout width="max-w-3xl">
    <x-slot name="header">
        <div>
            <p class="eyebrow">New scan</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-100">Analyse a pull request</h2>
            <p class="mt-1 text-sm text-slate-400">
                RegRadar reads the diff, maps it onto your feature registry and ranks what could regress.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="card hairline-top card-pad animate-fade-up" x-data="{ busy: false }">
            @if ($errors->any())
                <div class="alert-error mb-5">
                    <svg class="mt-px h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <div class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('analysis.store') }}" @submit="busy = true">
                @csrf

                <label for="pr_url" class="label">GitHub pull request URL</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-slate-500">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 .5A11.5 11.5 0 00.5 12a11.5 11.5 0 007.86 10.92c.58.1.79-.25.79-.55v-1.94c-3.2.7-3.88-1.54-3.88-1.54-.53-1.34-1.29-1.7-1.29-1.7-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.55-.29-5.24-1.28-5.24-5.7 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 015.8 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.12 3.05.74.81 1.18 1.84 1.18 3.1 0 4.43-2.69 5.4-5.25 5.69.41.36.78 1.05.78 2.12v3.14c0 .3.2.66.79.55A11.5 11.5 0 0023.5 12A11.5 11.5 0 0012 .5z" />
                        </svg>
                    </span>
                    <input id="pr_url" type="url" name="pr_url" value="{{ old('pr_url') }}"
                        placeholder="https://github.com/owner/repo/pull/123"
                        class="input pl-11 font-mono text-[.8rem]" required autofocus />
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    Public repositories, or any repo your configured GitHub token can read.
                </p>

                <button type="submit" class="btn-primary mt-6 w-full" x-bind:disabled="busy">
                    <span class="inline-flex items-center gap-2" x-show="!busy">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m-3-3h6" />
                        </svg>
                        Analyse pull request
                    </span>
                    <span class="inline-flex items-center gap-2" x-show="busy" x-cloak>
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                            <path class="opacity-90" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Analysing, this can take up to 30 seconds
                    </span>
                </button>
            </form>
        </div>

        {{-- How it works --}}
        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            @php
                $steps = [
                    ['n' => '01', 'title' => 'Fetch the diff', 'body' => 'The pull request diff is pulled straight from GitHub.'],
                    ['n' => '02', 'title' => 'Match features', 'body' => 'Changed files and functions are matched against your registry.'],
                    ['n' => '03', 'title' => 'Rank the risk', 'body' => 'Each affected feature gets a High, Medium or Low rating.'],
                ];
            @endphp

            @foreach ($steps as $i => $step)
                <div class="card card-pad animate-fade-up" style="animation-delay: {{ 80 + $i * 70 }}ms">
                    <span class="font-mono text-xs font-semibold text-brand-400">{{ $step['n'] }}</span>
                    <h3 class="mt-2 text-sm font-semibold text-slate-200">{{ $step['title'] }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $step['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
