<header class="z-30 border-b border-slate-800 bg-slate-900/90 backdrop-blur">
    <div class="flex h-20 items-center gap-4 px-4 sm:px-6 lg:px-8">
        <button type="button" class="focus-ring rounded-xl p-2 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden" @click="sidebarOpen = true" aria-controls="primary-navigation" :aria-expanded="sidebarOpen">
            <span class="sr-only">Abrir menú principal</span>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="min-w-0 flex-1">
            @isset($header)
                {{ $header }}
            @else
                <p class="truncate text-lg font-bold text-white">{{ config('app.name') }}</p>
            @endisset
        </div>

        @include('layouts.partials.accessibility-menu')

        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
            <button type="button" class="focus-ring flex items-center gap-3 rounded-xl p-1.5 pr-2 hover:bg-slate-800" @click="open = ! open" :aria-expanded="open" aria-controls="user-menu" aria-label="Abrir menú de usuario">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-600 text-sm font-black text-white" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="hidden min-w-0 text-left sm:block">
                    <span class="block max-w-40 truncate text-sm font-semibold text-slate-100">{{ Auth::user()->name }}</span>
                    <span class="block max-w-40 truncate text-xs text-slate-500">{{ Auth::user()->isPlatformAdmin() ? 'SuperAdmin de plataforma' : (Auth::user()->getRoleNames()->first() ?? 'Sin rol') }}</span>
                </span>
                <svg class="hidden h-4 w-4 text-slate-400 sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                </svg>
            </button>

            <div
                id="user-menu"
                x-cloak
                x-show="open"
                x-transition.origin.top.right
                class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-700 bg-slate-900 p-2 shadow-xl"
                role="menu"
            >
                <div class="border-b border-slate-800 px-3 py-2">
                    <p class="truncate text-sm font-semibold text-slate-100">{{ Auth::user()->email }}</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="focus-ring mt-1 block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800" role="menuitem">
                    Mi perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="focus-ring block w-full rounded-lg px-3 py-2 text-left text-sm text-red-400 hover:bg-red-950/40" role="menuitem">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
