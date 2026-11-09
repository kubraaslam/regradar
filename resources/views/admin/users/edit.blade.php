<x-app-layout width="max-w-3xl">
    <x-slot name="header">
        <div>
            <p class="eyebrow">Administration</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-100">Edit user</h2>
            <p class="mt-1 text-sm text-slate-400">
                Updating {{ $user->name }}.
                @unless ($user->isActive())
                    This account is deactivated.
                @endunless
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="card hairline-top card-pad animate-fade-up">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
                @csrf
                @method('PUT')

                @include('admin.users.partials.fields', ['roles' => $roles, 'user' => $user])

                <div class="flex flex-wrap gap-3 pt-1">
                    <button type="submit" class="btn-primary">Save changes</button>
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
