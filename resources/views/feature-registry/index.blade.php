<x-app-layout width="max-w-6xl">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Configuration</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-100">Feature registry</h2>
                <p class="mt-1 text-sm text-slate-400">
                    The map RegRadar uses to turn a diff into affected features.
                </p>
            </div>
            <a href="{{ route('feature-registry.create') }}" class="btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                </svg>
                Add feature
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="card animate-fade-up overflow-hidden">
            @if ($features->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="border-b border-white/[.07] bg-white/[.02]">
                            <tr>
                                <th class="th">Feature</th>
                                <th class="th">Code areas</th>
                                <th class="th">Endpoints</th>
                                <th class="th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-hairline">
                            @foreach ($features as $feature)
                                <tr class="row-hover">
                                    <td class="td">
                                        <p class="font-semibold text-slate-100">{{ $feature->feature_name }}</p>
                                        @if ($feature->description)
                                            <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
                                                {{ $feature->description }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="td">
                                        <div class="flex max-w-sm flex-wrap gap-1.5">
                                            @forelse ($feature->code_areas ?? [] as $area)
                                                <span
                                                    class="rounded-md border border-brand-500/25 bg-brand-500/[.08] px-2 py-0.5 font-mono text-xs text-brand-300">
                                                    {{ $area }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-slate-600">None</span>
                                            @endforelse
                                        </div>
                                    </td>

                                    <td class="td">
                                        <div class="flex max-w-sm flex-wrap gap-1.5">
                                            @forelse ($feature->endpoints ?? [] as $endpoint)
                                                <span
                                                    class="rounded-md border border-white/10 bg-white/[.05] px-2 py-0.5 font-mono text-xs text-slate-400">
                                                    {{ $endpoint }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-slate-600">None</span>
                                            @endforelse
                                        </div>
                                    </td>

                                    <td class="td text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="{{ route('feature-registry.edit', $feature) }}"
                                                class="btn-ghost btn-sm">
                                                Edit
                                            </a>
                                            <form method="POST"
                                                action="{{ route('feature-registry.destroy', $feature) }}"
                                                class="inline"
                                                onsubmit="return confirm('Delete “{{ $feature->feature_name }}” from the registry?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm text-slate-400 hover:bg-flare-500/10 hover:text-flare-400">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-20 text-center">
                    <span
                        class="mx-auto grid h-14 w-14 place-items-center rounded-2xl border border-brand-500/25 bg-brand-500/[.08] text-brand-300">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25z" />
                        </svg>
                    </span>
                    <p class="mt-5 text-base font-semibold text-slate-200">Your registry is empty</p>
                    <p class="mx-auto mt-1.5 max-w-md text-sm text-slate-500">
                        Add the features you care about, along with the classes and endpoints behind them. RegRadar
                        needs at least one before it can analyse a pull request.
                    </p>
                    <a href="{{ route('feature-registry.create') }}" class="btn-primary mt-6">Add your first
                        feature</a>
                </div>
            @endif
        </div>

        @if ($features->count())
            <p class="mt-4 text-xs text-slate-500">
                {{ $features->count() }} {{ Str::plural('feature', $features->count()) }} mapped.
                The more precise your code areas, the sharper the risk ranking.
            </p>
        @endif
    </div>
</x-app-layout>
