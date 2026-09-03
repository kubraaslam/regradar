<nav x-data="{ open: false }"
    class="sticky top-0 z-40 border-b border-white/[.07] bg-ink-900/80 backdrop-blur-xl">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                {{-- Logo --}}
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="group flex items-center gap-2.5">
                        <x-application-logo class="h-9 w-9 transition-transform duration-500 group-hover:scale-105" />
                        <span class="text-lg font-bold tracking-tight text-slate-100">
                            Reg<span class="gradient-text">Radar</span>
                        </span>
                    </a>
                </div>

                {{-- Navigation Links --}}
                <div class="hidden sm:-my-px sm:ms-10 sm:flex sm:gap-1">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('analysis.create')" :active="request()->routeIs('analysis.create')">
                        New Analysis
                    </x-nav-link>
                    <x-nav-link :href="route('analysis.index')" :active="request()->routeIs('analysis.index') || request()->routeIs('analysis.show')">
                        History
                    </x-nav-link>
                    <x-nav-link :href="route('feature-registry.index')" :active="request()->routeIs('feature-registry.*')">
                        Feature Registry
                    </x-nav-link>
                </div>
            </div>

            {{-- Right side --}}
            <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-3">
                <a href="{{ route('analysis.create') }}" class="btn-primary btn-sm">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                    </svg>
                    New Analysis
                </a>

                {{-- Settings Dropdown --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="group inline-flex items-center gap-2 rounded-xl border border-white/[.07] bg-white/[.03] px-2 py-1.5 text-sm font-medium text-slate-300 transition hover:border-brand-500/40 hover:text-white focus:outline-none">
                            <span
                                class="grid h-7 w-7 place-items-center rounded-lg bg-brand-gradient text-[.7rem] font-bold text-white">
                                {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="max-w-[9rem] truncate">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 text-slate-500 transition-colors group-hover:text-brand-400"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="border-b border-white/[.07] px-4 py-3">
                            <p class="truncate text-sm font-semibold text-slate-100">{{ Auth::user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Hamburger --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition hover:bg-white/[.06] hover:text-white focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Responsive Navigation Menu --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-white/[.07] bg-ink-900/95 sm:hidden">
        <div class="space-y-1 py-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('analysis.create')" :active="request()->routeIs('analysis.create')">
                New Analysis
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('analysis.index')" :active="request()->routeIs('analysis.index') || request()->routeIs('analysis.show')">
                History
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('feature-registry.index')" :active="request()->routeIs('feature-registry.*')">
                Feature Registry
            </x-responsive-nav-link>
        </div>

        {{-- Responsive Settings Options --}}
        <div class="border-t border-white/[.07] pb-3 pt-4">
            <div class="flex items-center gap-3 px-4">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-gradient text-sm font-bold text-white">
                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <div class="truncate text-base font-medium text-slate-100">{{ Auth::user()->name }}</div>
                    <div class="truncate text-sm text-slate-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
