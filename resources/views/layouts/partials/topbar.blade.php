<header class="z-30 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
    <div class="flex h-20 items-center gap-4 px-4 sm:px-6 lg:px-8">
        <button type="button" class="focus-ring rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 lg:hidden" @click="sidebarOpen = true">
            <span class="sr-only">Abrir menú</span>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="min-w-0 flex-1">
            @isset($header)
                {{ $header }}
            @else
                <p class="truncate text-lg font-bold text-slate-950 dark:text-white">{{ config('app.name') }}</p>
            @endisset
        </div>

        <button
            type="button"
            class="focus-ring rounded-xl border border-slate-200 p-2.5 text-slate-500 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
            @click="$store.theme.toggle()"
            :aria-label="$store.theme.dark ? 'Activar modo claro' : 'Activar modo oscuro'"
        >
            <svg x-show="! $store.theme.dark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.4 6.4-.7-.7M6.3 6.3l-.7-.7m12.8 0-.7.7M6.3 17.7l-.7.7M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/>
            </svg>
            <svg x-cloak x-show="$store.theme.dark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/>
            </svg>
        </button>

        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" class="focus-ring flex items-center gap-3 rounded-xl p-1.5 pr-2 hover:bg-slate-100 dark:hover:bg-slate-800" @click="open = ! open" :aria-expanded="open">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-600 text-sm font-black text-white">
                    {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="hidden min-w-0 text-left sm:block">
                    <span class="block max-w-40 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ Auth::user()->name }}</span>
                    <span class="block max-w-40 truncate text-xs text-slate-500">{{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}</span>
                </span>
                <svg class="hidden h-4 w-4 text-slate-400 sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                </svg>
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition.origin.top.right
                class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900"
            >
                <div class="border-b border-slate-100 px-3 py-2 dark:border-slate-800">
                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ Auth::user()->email }}</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="mt-1 block rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                    Mi perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
