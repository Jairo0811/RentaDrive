@php
    $mfa = app(\App\Support\Security\Mfa\TwoFactorAuthentication::class);
    $enabled = $user->hasTwoFactorEnabled();
    $pending = ! $enabled && $user->two_factor_secret !== null;
    $status = session('status');
@endphp

<section aria-labelledby="two-factor-title">
    <header>
        <h2 id="two-factor-title" class="text-lg font-medium text-gray-900 dark:text-gray-100">Verificación en dos pasos (MFA)</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Protege tu cuenta con un código temporal de una aplicación autenticadora (Google Authenticator, Microsoft Authenticator, Authy, 1Password…).
            @if ($user->isPrivileged())
                Es muy recomendable para tu rol de administración.
            @endif
        </p>
    </header>

    @if ($status === 'two-factor-required')
        <p class="mt-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/50 dark:text-amber-200" role="alert">
            Tu rol exige verificación en dos pasos. Actívala para continuar usando el panel.
        </p>
    @elseif ($status === 'two-factor-disabled')
        <p class="mt-4 text-sm text-emerald-600 dark:text-emerald-400" role="status">La verificación en dos pasos fue desactivada.</p>
    @endif

    <div class="mt-6 space-y-5">
        @if ($enabled)
            <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Estado: activada</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Códigos de recuperación disponibles: {{ $mfa->recoveryCodesRemaining($user) }}</p>

            <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="space-y-3">
                @csrf
                <x-input-label for="regen_code" value="Código actual para generar nuevos códigos de recuperación" />
                <x-text-input id="regen_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="block w-full max-w-xs font-mono" required />
                <x-input-error :messages="$errors->get('code')" />
                <x-secondary-button type="submit">Regenerar códigos de recuperación</x-secondary-button>
            </form>

            <form method="POST" action="{{ route('two-factor.disable') }}">
                @csrf
                @method('DELETE')
                <x-danger-button type="submit">Desactivar verificación en dos pasos</x-danger-button>
            </form>
        @elseif ($pending)
            <ol class="list-decimal space-y-2 pl-5 text-sm text-gray-700 dark:text-gray-300">
                <li>En tu aplicación autenticadora elige «Ingresar clave de configuración» (cuenta: {{ $user->email }}, tipo: basado en tiempo).</li>
                <li>Escribe esta clave: <code class="select-all break-all rounded bg-slate-100 px-2 py-1 font-mono text-slate-900 dark:bg-slate-800 dark:text-slate-100">{{ trim(chunk_split($user->two_factor_secret, 4, ' ')) }}</code></li>
                <li>Ingresa el código de 6 dígitos que genera para confirmar.</li>
            </ol>
            <p class="break-all text-xs text-gray-500 dark:text-gray-400">URI de aprovisionamiento (para gestores de contraseñas compatibles): <span class="select-all font-mono">{{ $mfa->provisioningUri($user) }}</span></p>

            <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-3">
                @csrf
                <x-input-label for="confirm_code" value="Código de 6 dígitos" />
                <x-text-input id="confirm_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="block w-full max-w-xs font-mono" required />
                <x-input-error :messages="$errors->get('code')" />
                <x-primary-button type="submit">Confirmar y activar</x-primary-button>
            </form>

            <form method="POST" action="{{ route('two-factor.disable') }}">
                @csrf
                @method('DELETE')
                <x-secondary-button type="submit">Cancelar configuración</x-secondary-button>
            </form>
        @else
            <p class="text-sm text-gray-600 dark:text-gray-400">Estado: desactivada</p>
            <form method="POST" action="{{ route('two-factor.enable') }}">
                @csrf
                <x-primary-button type="submit">Activar verificación en dos pasos</x-primary-button>
            </form>
        @endif
    </div>
</section>
