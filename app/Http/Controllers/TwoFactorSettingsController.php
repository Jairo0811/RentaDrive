<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Security\Mfa\TwoFactorAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Gestión de MFA desde el perfil. Todas las rutas exigen contraseña confirmada recientemente (ver rutas).
 */
final class TwoFactorSettingsController extends Controller
{
    public function __construct(private readonly TwoFactorAuthentication $mfa) {}

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('status', 'two-factor-already-enabled');
        }

        $this->mfa->beginSetup($user);

        return redirect()->route('profile.edit')->with('status', 'two-factor-setup-started');
    }

    public function confirm(Request $request): View|RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:16']]);

        /** @var User $user */
        $user = $request->user();
        $this->throttle($request, $user, 'code');

        $codes = $this->mfa->confirmSetup($user, (string) $request->input('code'));

        if ($codes === null) {
            $this->failed($request, $user, 'code', 'El código no es válido. Revisa la hora de tu dispositivo e inténtalo de nuevo.');
        }

        RateLimiter::clear($this->key($request, $user));

        // Se muestra directamente en la respuesta (no en la sesión) y no se vuelve a poder consultar.
        return view('profile.two-factor-recovery-codes', ['codes' => $codes, 'title' => 'MFA activado']);
    }

    public function regenerate(Request $request): View
    {
        $request->validate(['code' => ['required', 'string', 'max:16']]);

        /** @var User $user */
        $user = $request->user();
        $this->throttle($request, $user, 'code');

        $codes = $this->mfa->regenerateRecoveryCodes($user, (string) $request->input('code'));

        if ($codes === null) {
            $this->failed($request, $user, 'code', 'El código no es válido.');
        }

        RateLimiter::clear($this->key($request, $user));

        return view('profile.two-factor-recovery-codes', ['codes' => $codes, 'title' => 'Nuevos códigos de recuperación']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->mfa->disable($user);

        return redirect()->route('profile.edit')->with('status', 'two-factor-disabled');
    }

    private function key(Request $request, User $user): string
    {
        return 'mfa-settings|'.$user->getKey().'|'.$request->ip();
    }

    private function throttle(Request $request, User $user, string $field): void
    {
        $key = $this->key($request, $user);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                $field => 'Demasiados intentos. Intenta nuevamente en '.max(1, (int) ceil(RateLimiter::availableIn($key) / 60)).' minuto(s).',
            ]);
        }
    }

    private function failed(Request $request, User $user, string $field, string $message): never
    {
        RateLimiter::hit($this->key($request, $user), 60);
        $this->mfa->record($user, 'mfa_failed');

        throw ValidationException::withMessages([$field => $message]);
    }
}
