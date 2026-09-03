<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Feature Registry</h2>
            <a href="{{ route('feature-registry.create') }}"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                + Add Feature
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Feature</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code Areas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Endpoints</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($features as $feature)
                            <tr>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ $feature->feature_name }}
                                    @if($feature->description)
                                        <p class="text-xs text-gray-400 mt-1">{{ $feature->description }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ implode(', ', $feature->code_areas) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ implode(', ', $feature->endpoints) }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('feature-registry.edit', $feature) }}"
                                        class="text-blue-600 hover:underline mr-3">Edit</a>
                                    <form method="POST" action="{{ route('feature-registry.destroy', $feature) }}"
                                        class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline"
                                            onclick="return confirm('Delete this feature?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-400">
                                    No features yet. Add your first feature to the registry.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>