<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Analyse a Pull Request</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-lg shadow">
                @if($errors->any())
                    <div class="mb-4 text-red-600">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('analysis.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            GitHub Pull Request URL
                        </label>
                        <input type="url" name="pr_url" placeholder="https://github.com/owner/repo/pull/123"
                            value="{{ old('pr_url') }}"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required />
                    </div>
                    <button type="submit" id="submit-btn"
                        class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700"
                        onclick="this.disabled=true; this.innerText='Analysing... this may take up to 30 seconds'; this.form.submit();">
                        Analyse Pull Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>