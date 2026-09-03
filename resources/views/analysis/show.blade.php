<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Regression Risk Report</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- PR Info --}}
            <div class="bg-white p-4 rounded-lg shadow text-sm text-gray-600">
                <strong>PR:</strong>
                <a href="{{ $analysis->pr_url }}" target="_blank"
                    class="text-blue-600 hover:underline">{{ $analysis->pr_url }}</a>
                <span class="ml-4 text-gray-400">{{ $analysis->created_at->diffForHumans() }}</span>
            </div>

            @if($analysis->status === 'completed' && $analysis->results)

                {{-- Summary --}}
                @if($analysis->results['summary'])
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                        <p class="text-sm text-blue-800">{{ $analysis->results['summary'] }}</p>
                    </div>
                @endif

                {{-- Risk counts --}}
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-red-50 border border-red-200 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-red-700">{{ $analysis->results['total_high'] }}</div>
                        <div class="text-sm text-red-600 mt-1">High Risk</div>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-yellow-700">{{ $analysis->results['total_medium'] }}</div>
                        <div class="text-sm text-yellow-600 mt-1">Medium Risk</div>
                    </div>
                    <div class="bg-green-50 border border-green-200 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-green-700">{{ $analysis->results['total_low'] }}</div>
                        <div class="text-sm text-green-600 mt-1">Low Risk</div>
                    </div>
                </div>

                {{-- Risk list --}}
                <div class="bg-white rounded-lg shadow divide-y">
                    @forelse($analysis->results['risks'] as $risk)
                        <div class="p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="font-medium text-gray-900">{{ $risk['feature'] }}</span>
                                <span class="text-xs px-2 py-1 rounded-full font-medium {{ $risk['badge_class'] }}">
                                    {{ $risk['risk_level'] }} Risk
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">{{ $risk['reason'] }}</p>
                        </div>
                    @empty
                        <div class="p-6 text-center text-gray-400">
                            No features at risk were identified for this pull request.
                        </div>
                    @endforelse
                </div>

                {{-- Changed files --}}
                @if(!empty($analysis->results['changed_files']))
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Files changed in this PR</h3>
                        <ul class="text-sm text-gray-500 space-y-1">
                            @foreach($analysis->results['changed_files'] as $file)
                                <li class="font-mono">{{ $file }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            @else
                <div class="bg-red-50 border border-red-200 p-4 rounded-lg text-red-700">
                    Analysis failed. Please try again.
                </div>
            @endif

            <div class="flex gap-3">
                <a href="{{ route('analysis.create') }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                    Analyse Another PR
                </a>
                <a href="{{ route('analysis.index') }}"
                    class="border border-gray-300 px-4 py-2 rounded hover:bg-gray-50 text-sm">
                    View All Analyses
                </a>
            </div>
        </div>
    </div>
</x-app-layout>