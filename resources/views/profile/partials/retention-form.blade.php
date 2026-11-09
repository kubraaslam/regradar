<section>
    <header>
        <h2 class="text-lg font-medium text-slate-100">Data retention</h2>
        <p class="mt-1 text-sm text-slate-400">
            Your analyses are deleted {{ config('regradar.retention_days') }} days after they were run, unless you
            choose to keep them. Deletion covers the pull request details and the model's findings; it does not affect
            your Feature Registry.
        </p>
    </header>

    <form method="post" action="{{ route('profile.retention') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <label for="retain" class="flex items-start gap-3">
            <input type="hidden" name="retain" value="0" />
            <input id="retain" type="checkbox" name="retain" value="1" class="checkbox mt-0.5"
                @checked($user->hasRetentionConsent()) />
            <span class="text-sm text-slate-300">
                Keep my analyses beyond {{ config('regradar.retention_days') }} days
                <span class="mt-0.5 block text-xs text-slate-500">
                    @if ($user->hasRetentionConsent())
                        Consent given {{ $user->retention_consent_at->format('j M Y') }}. Untick to withdraw it.
                    @else
                        Not consented. Analyses older than {{ config('regradar.retention_days') }} days will be removed.
                    @endif
                </span>
            </span>
        </label>

        <div class="flex items-center gap-4">
            <x-primary-button>Save</x-primary-button>

            @if (session('status') === 'retention-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-400">Saved.</p>
            @endif
        </div>
    </form>
</section>
