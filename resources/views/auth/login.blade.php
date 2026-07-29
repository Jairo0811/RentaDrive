<x-guest-layout>
    <div class="mb-8">
        <p class="text-sm font-bold uppercase tracking-[.22em] text-[#0568f5] dark:text-[#22a3ff]">Acceso seguro</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Bienvenido a RentaDrive</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
            Ingresa tus credenciales para acceder al panel de gestión.
        </p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900/60 dark:bg-emerald-950/50" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input
                id="email"
                class="mt-2 block w-full"
                type="email"
                name="email"
                :value="old('email')"
                placeholder="nombre@empresa.com"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Contraseña" />
                @if (Route::has('password.request'))
                    <a class="focus-ring rounded text-xs font-semibold text-[#0568f5] hover:text-[#004fc7] dark:text-[#22a3ff]" href="{{ route('password.request') }}">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>

            <x-text-input
                id="password"
                class="mt-2 block w-full"
                type="password"
                name="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex cursor-pointer items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-[#0568f5] shadow-sm focus:ring-[#168ce8] dark:border-slate-700 dark:bg-slate-950" name="remember">
            Mantener mi sesión iniciada
        </label>

        <x-primary-button class="w-full">
            Iniciar sesión
        </x-primary-button>
    </form>

    @env(['local', 'testing'])
        <div class="mt-7 rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-200">
            <p class="font-bold">Acceso de demostración</p>
            <p class="mt-1 font-mono">admin@rentadrive.test / password</p>
        </div>
    @endenv

    <p class="mt-7 text-center text-xs text-slate-400">
        El registro público está deshabilitado por seguridad.
    </p>
</x-guest-layout>
