{{--
    Shared registry form.
    Expects: $action, $method ('POST' | 'PUT'), $submitLabel, $feature (FeatureRegistry|null)
--}}
@php
    $feature = $feature ?? null;
@endphp

<div class="card hairline-top card-pad animate-fade-up">
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

    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if ($method === 'PUT')
            @method('PUT')
        @endif

        <div>
            <label for="feature_name" class="label">Feature name</label>
            <input id="feature_name" type="text" name="feature_name"
                value="{{ old('feature_name', $feature->feature_name ?? '') }}" placeholder="e.g. User Authentication"
                class="input" required />
        </div>

        <div>
            <label for="code_areas" class="label">
                Code areas <span class="label-hint">(comma separated)</span>
            </label>
            <input id="code_areas" type="text" name="code_areas"
                value="{{ old('code_areas', isset($feature) ? implode(', ', $feature->code_areas ?? []) : '') }}"
                placeholder="AuthController, UserService, LoginRequest" class="input font-mono text-[.8rem]"
                required />
            <p class="mt-2 text-xs text-slate-500">
                Classes, files or directories that implement this feature. These are matched against the diff.
            </p>
        </div>

        <div>
            <label for="endpoints" class="label">
                API endpoints <span class="label-hint">(comma separated, optional)</span>
            </label>
            <input id="endpoints" type="text" name="endpoints"
                value="{{ old('endpoints', isset($feature) ? implode(', ', $feature->endpoints ?? []) : '') }}"
                placeholder="/api/auth/login, /api/auth/logout" class="input font-mono text-[.8rem]" />
        </div>

        <div>
            <label for="description" class="label">
                Description <span class="label-hint">(optional)</span>
            </label>
            <textarea id="description" name="description" rows="3" class="input"
                placeholder="Brief description of what this feature covers">{{ old('description', $feature->description ?? '') }}</textarea>
        </div>

        <div class="flex flex-wrap gap-3 pt-1">
            <button type="submit" class="btn-primary">{{ $submitLabel }}</button>
            <a href="{{ route('feature-registry.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
