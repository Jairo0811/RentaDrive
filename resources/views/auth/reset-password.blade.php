<x-guest-layout>
    <div class="mb-7">
        <p class="text-sm font-bold uppercase tracking-[.18em] text-blue-400">Acceso seguro</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight text-white">Crear nueva contraseña</h1>
        <p class="mt-3 text-sm leading-relaxed text-slate-300">
            Introduce tu correo y elige una contraseña nueva. El enlace de recuperación es temporal y de un solo uso.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input
                id="email"
                class="mt-2 block w-full"
                type="email"
                name="email"
                :value="old('email', $request->email)"
                maxlength="255"
                required
                autocomplete="email"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" role="alert" />
        </div>

        <div>
            <x-input-label for="password" value="Nueva contraseña" />
            <x-text-input
                id="password"
                class="mt-2 block w-full"
                type="password"
                name="password"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" role="alert" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar nueva contraseña" />
            <x-text-input
                id="password_confirmation"
                class="mt-2 block w-full"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" role="alert" />
        </div>

        <x-primary-button class="w-full">Restablecer contraseña</x-primary-button>
    </form>

    <a href="{{ route('login') }}" class="focus-ring mt-5 inline-flex min-h-11 items-center rounded text-sm font-semibold text-blue-300 hover:text-blue-200">
        Volver al inicio de sesión
    </a>
</x-guest-layout>
