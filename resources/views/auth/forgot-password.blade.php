<x-guest-layout>
    <div class="mb-7">
        <p class="text-sm font-bold uppercase tracking-[.18em] text-blue-400">Recuperación de acceso</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight text-white">¿Olvidaste tu contraseña?</h1>
        <p class="mt-3 text-sm leading-relaxed text-slate-300">
            Escribe el correo asociado a tu cuenta. Si está registrado, te enviaremos un enlace para establecer una nueva contraseña.
        </p>
    </div>

    <x-auth-session-status
        class="mb-5 rounded-xl border border-emerald-800 bg-emerald-950/50 px-4 py-3 text-emerald-200"
        role="status"
        aria-live="polite"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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
                maxlength="255"
                autocomplete="email"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" role="alert" />
        </div>

        <x-primary-button class="w-full">Enviar enlace de recuperación</x-primary-button>
    </form>

    <a href="{{ route('login') }}" class="focus-ring mt-5 inline-flex min-h-11 items-center rounded text-sm font-semibold text-blue-300 hover:text-blue-200">
        Volver al inicio de sesión
    </a>
</x-guest-layout>
