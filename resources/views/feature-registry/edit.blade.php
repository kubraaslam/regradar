<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Add Feature to Registry</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-lg shadow">
                @if($errors->any())
                    <div class="mb-4 text-red-600 text-sm">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('feature-registry.update', $featureRegistry) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Feature Name</label>
                        <input type="text" name="feature_name" value="{{ $featureRegistry->feature_name }}"
                            placeholder="e.g. User Authentication"
                            class="w-full border border-gray-300 rounded px-3 py-2" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Code Areas <span class="text-gray-400 font-normal">(comma separated)</span>
                        </label>
                        <input type="text" name="code_areas" value="{{ implode(', ', $featureRegistry->code_areas) }}"
                            placeholder="e.g. AuthController, UserService, LoginRequest"
                            class="w-full border border-gray-300 rounded px-3 py-2" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            API Endpoints <span class="text-gray-400 font-normal">(comma separated, optional)</span>
                        </label>
                        <input type="text" name="endpoints"
                            value="{{ old('endpoints', implode(', ', $featureRegistry->endpoints ?? [])) }}""
                            placeholder=" e.g. /api/auth/login, /api/auth/logout"
                            class="w-full border border-gray-300 rounded px-3 py-2" />
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                        <textarea name="description" rows="2" class="w-full border border-gray-300 rounded px-3 py-2"
                            placeholder="Brief description of what this feature covers">{{ old('description', $featureRegistry->description) }}</textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                            Update Feature
                        </button>
                        <a href="{{ route('feature-registry.index') }}"
                            class="px-6 py-2 border border-gray-300 rounded hover:bg-gray-50">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>