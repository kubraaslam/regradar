<x-app-layout width="max-w-3xl">
    <x-slot name="header">
        <div>
            <p class="eyebrow">Feature registry</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-100">Edit feature</h2>
            <p class="mt-1 text-sm text-slate-400">
                Updating {{ $featureRegistry->feature_name }}.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        @include('feature-registry.partials.form', [
            'action' => route('feature-registry.update', $featureRegistry),
            'method' => 'PUT',
            'submitLabel' => 'Update feature',
            'feature' => $featureRegistry,
        ])
    </div>
</x-app-layout>
