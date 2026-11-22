<x-guest-layout>
    <div class="mb-8">
        <p class="flex items-center gap-2 text-sm font-bold uppercase tracking-[.22em] text-[#0568f5] dark:text-[#22a3ff]">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            Verificación en dos pasos
        </p>
        <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">
            {{ $useRecovery ? 'Código de recuperación' : 'Código de verificación' }}
        </h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
            @if ($useRecovery)
                Ingresa uno de tus códigos de recuperación. Cada código funciona una sola vez.
            @else
                Ingresa el código de 6 dígitos que muestra tu aplicación autenticadora.
            @endif
        </p>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge') }}" class="space-y-5">
        @csrf

        @if ($useRecovery)
            <div>
                <x-input-label for="recovery_code" value="Código de recuperación" />
                <x-text-input id="recovery_code" class="mt-2 block w-full font-mono" type="text" name="recovery_code" required autofocus autocomplete="off" placeholder="xxxxx-xxxxx" />
                <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
            </div>
        @else
            <div>
                <x-input-label for="code" value="Código de 6 dígitos" />
                <x-text-input id="code" class="mt-2 block w-full font-mono tracking-[.4em]" type="text" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" required autofocus autocomplete="one-time-code" placeholder="000000" />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>
        @endif

        <x-primary-button class="w-full">Verificar y continuar</x-primary-button>
    </form>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
        @if ($useRecovery)
            <a class="focus-ring inline-flex min-h-11 items-center rounded font-semibold text-[#22a3ff]" href="{{ route('two-factor.challenge') }}">Usar la aplicación autenticadora</a>
        @else
            <a class="focus-ring inline-flex min-h-11 items-center rounded font-semibold text-[#22a3ff]" href="{{ route('two-factor.challenge', ['recovery' => 1]) }}">Usar un código de recuperación</a>
        @endif
        <a class="focus-ring inline-flex min-h-11 items-center rounded text-slate-400" href="{{ route('login') }}">Volver al inicio de sesión</a>
    </div>
</x-guest-layout>
