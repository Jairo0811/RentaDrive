<x-guest-layout>
    <div class="mb-8">
        <p class="flex items-center gap-2 text-sm font-bold uppercase tracking-[.22em] text-[#0568f5] dark:text-[#22a3ff]">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            Acceso seguro
        </p>
        <h1 class="mt-3 flex items-center gap-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">
            <i class="fa-solid fa-car-side text-[#0568f5] dark:text-[#22a3ff]" aria-hidden="true"></i>
            Bienvenido a RentaDrive
        </h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
            Ingresa tus credenciales para acceder al panel de gestión.
        </p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900/60 dark:bg-emerald-950/50" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email">
                <span class="inline-flex items-center gap-2">
                    <i class="fa-solid fa-envelope text-[#0568f5] dark:text-[#22a3ff]" aria-hidden="true"></i>
                    Correo electrónico
                </span>
            </x-input-label>
            <div class="relative mt-2">
                <i class="fa-solid fa-at pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                <x-text-input
                    id="email"
                    class="block w-full pl-11"
                    type="email"
                    name="email"
                    :value="old('email', 'admin@rentadrive.com.do')"
                    placeholder="nombre@empresa.com"
                    required
                    autofocus
                    autocomplete="username"
                />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-lock text-[#0568f5] dark:text-[#22a3ff]" aria-hidden="true"></i>
                        Contraseña
                    </span>
                </x-input-label>
                @if (Route::has('password.request'))
                    <a class="focus-ring inline-flex items-center gap-1.5 rounded text-xs font-semibold text-[#0568f5] hover:text-[#004fc7] dark:text-[#22a3ff]" href="{{ route('password.request') }}">
                        <i class="fa-solid fa-key" aria-hidden="true"></i>
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>

            <div class="relative mt-2">
                <i class="fa-solid fa-key pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                <x-text-input
                    id="password"
                    class="block w-full pl-11"
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex cursor-pointer items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-[#0568f5] shadow-sm focus:ring-[#168ce8] dark:border-slate-700 dark:bg-slate-950" name="remember">
            <i class="fa-solid fa-clock-rotate-left text-slate-400" aria-hidden="true"></i>
            Mantener mi sesión iniciada
        </label>

        <x-primary-button class="w-full">
            <span class="inline-flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                Iniciar sesión
            </span>
        </x-primary-button>
    </form>

    @env(['local', 'testing'])
        <div class="mt-7 rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-200">
            <p class="flex items-center gap-2 font-bold">
                <i class="fa-solid fa-flask" aria-hidden="true"></i>
                Acceso de demostración
            </p>
            <p class="mt-1 flex items-center gap-2 font-mono">
                <i class="fa-solid fa-user-gear" aria-hidden="true"></i>
                admin@rentadrive.com.do / RentaDrive123..
            </p>
        </div>
    @endenv

    <p class="mt-7 flex items-center justify-center gap-2 text-center text-xs text-slate-400">
        <i class="fa-solid fa-user-lock" aria-hidden="true"></i>
        El registro público está deshabilitado por seguridad.
    </p>
</x-guest-layout>
