<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Analysis History</h2>
            <a href="{{ route('analysis.create') }}"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                + New Analysis
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow divide-y">
                @forelse($analyses as $analysis)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 truncate max-w-md">
                                {{ $analysis->pr_url }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">{{ $analysis->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($analysis->status === 'completed' && $analysis->results)
                                <span class="text-xs text-red-600 font-medium">
                                    {{ $analysis->results['total_high'] }} High
                                </span>
                                <span class="text-xs text-yellow-600 font-medium">
                                    {{ $analysis->results['total_medium'] }} Medium
                                </span>
                            @else
                                <span class="text-xs text-gray-400">{{ ucfirst($analysis->status) }}</span>
                            @endif
                            <a href="{{ route('analysis.show', $analysis) }}"
                                class="text-blue-600 text-sm hover:underline">View</a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400">
                        No analyses yet. Submit your first PR URL to get started.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>