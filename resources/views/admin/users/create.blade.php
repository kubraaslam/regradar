<x-app-layout width="max-w-3xl">
    <x-slot name="header">
        <div>
            <p class="eyebrow">Administration</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-100">Add a user</h2>
            <p class="mt-1 text-sm text-slate-400">Create an account and choose what it can do.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="card hairline-top card-pad animate-fade-up">
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
                @csrf

                @include('admin.users.partials.fields', ['roles' => $roles])

                <div>
                    <label for="password" class="label">Initial password</label>
                    <input id="password" type="password" name="password" class="input" required
                        autocomplete="new-password" />
                    <p class="mt-2 text-xs text-slate-500">Share this with the user; they can change it from their profile.</p>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <label for="password_confirmation" class="label">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="input"
                        required autocomplete="new-password" />
                </div>

                <div class="flex flex-wrap gap-3 pt-1">
                    <button type="submit" class="btn-primary">Create account</button>
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
