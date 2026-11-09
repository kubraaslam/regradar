<x-app-layout width="max-w-6xl">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Administration</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-100">Users</h2>
                <p class="mt-1 text-sm text-slate-400">
                    {{ $users->count() }} {{ Str::plural('account', $users->count()) }}.
                    Deactivated accounts keep their analyses and registry entries but cannot sign in.
                </p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                </svg>
                Add user
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($errors->has('user'))
            <div class="alert-error mb-5">
                <span>{{ $errors->first('user') }}</span>
            </div>
        @endif

        <div class="card animate-fade-up overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-white/[.07] bg-white/[.02]">
                        <tr>
                            <th class="th">User</th>
                            <th class="th">Role</th>
                            <th class="th">Status</th>
                            <th class="th text-right">Activity</th>
                            <th class="th text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-hairline">
                        @foreach ($users as $user)
                            <tr class="row-hover {{ $user->isActive() ? '' : 'opacity-60' }}">
                                <td class="td">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white/[.06] text-xs font-bold text-slate-300">
                                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-100">
                                                {{ $user->name }}
                                                @if ($user->is(Auth::user()))
                                                    <span class="text-xs font-normal text-slate-500">(you)</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="td">
                                    <span @class([
                                        'badge',
                                        'border-brand-500/30 bg-brand-500/10 text-brand-300' => $user->role === 'qa_engineer',
                                        'border-white/10 bg-white/[.05] text-slate-300' => $user->role === 'developer',
                                        'border-amber-400/30 bg-amber-400/10 text-amber-300' => $user->role === 'admin',
                                    ])>{{ $user->roleLabel() }}</span>
                                </td>
                                <td class="td">
                                    @if ($user->isActive())
                                        <span class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                                            <span class="dot bg-brand-500"></span> Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                            <span class="dot bg-slate-600"></span>
                                            Deactivated {{ $user->deactivated_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </td>
                                <td class="td text-right text-xs text-slate-500 tabular-nums">
                                    {{ $user->analyses_count }} {{ Str::plural('analysis', $user->analyses_count) }}
                                    <span class="text-slate-700">·</span>
                                    {{ $user->feature_registries_count }} {{ Str::plural('feature', $user->feature_registries_count) }}
                                </td>
                                <td class="td text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost btn-sm">Edit</a>

                                        @unless ($user->is(Auth::user()))
                                            @if ($user->isActive())
                                                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}"
                                                    class="inline"
                                                    onsubmit="return confirm('Deactivate {{ e($user->name) }}? They will be signed out and unable to sign in until reactivated.')">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-sm text-slate-400 hover:bg-flare-500/10 hover:text-flare-400">
                                                        Deactivate
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.reactivate', $user) }}"
                                                    class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-sm text-slate-400 hover:bg-brand-500/10 hover:text-brand-300">
                                                        Reactivate
                                                    </button>
                                                </form>
                                            @endif
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
