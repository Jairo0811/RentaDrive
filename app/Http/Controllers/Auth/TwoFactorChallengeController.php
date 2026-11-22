<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Security\Mfa\TwoFactorAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TwoFactorChallengeController extends Controller
{
    public const PENDING_USER = 'mfa.pending_user_id';

    public const PENDING_REMEMBER = 'mfa.pending_remember';

    public const PENDING_EXPIRES = 'mfa.pending_expires_at';

    public const TTL_MINUTES = 5;

    public function __construct(private readonly TwoFactorAuthentication $mfa) {}

    public function create(Request $request): View|RedirectResponse
    {
        if ($this->pendingUser($request) === null) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', ['useRecovery' => $request->boolean('recovery')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login')->withErrors(['email' => 'La verificación expiró. Inicia sesión nuevamente.']);
        }

        $perAttempt = 'mfa|'.$user->getKey().'|'.$request->ip();
        $perAccount = 'mfa-account|'.$user->getKey();
        $field = $request->filled('recovery_code') ? 'recovery_code' : 'code';

        if (RateLimiter::tooManyAttempts($perAttempt, 5) || RateLimiter::tooManyAttempts($perAccount, 10)) {
            $seconds = max(RateLimiter::availableIn($perAttempt), RateLimiter::availableIn($perAccount));
            $this->mfa->record($user, 'mfa_locked');

            throw ValidationException::withMessages([
                $field => 'Demasiados intentos. Intenta nuevamente en '.max(1, (int) ceil($seconds / 60)).' minuto(s).',
            ]);
        }

        $request->validate([
            'code' => ['nullable', 'string', 'max:16'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
        ]);

        $valid = $field === 'recovery_code'
            ? $this->mfa->verifyRecoveryCode($user, (string) $request->input('recovery_code'))
            : $this->mfa->verifyCode($user, (string) $request->input('code'));

        if (! $valid) {
            RateLimiter::hit($perAttempt, 60);
            RateLimiter::hit($perAccount, 900);
            $this->mfa->record($user, 'mfa_failed');

            throw ValidationException::withMessages([
                $field => $field === 'recovery_code'
                    ? 'El código de recuperación no es válido o ya fue utilizado.'
                    : 'El código de verificación no es válido.',
            ]);
        }

        RateLimiter::clear($perAttempt);
        RateLimiter::clear($perAccount);

        $remember = (bool) $request->session()->get(self::PENDING_REMEMBER, false);
        $request->session()->forget([self::PENDING_USER, self::PENDING_REMEMBER, self::PENDING_EXPIRES]);

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        if ($user->isPlatformAdmin()) {
            return redirect()->route('platform.dashboard');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get(self::PENDING_USER);
        $expires = (int) $request->session()->get(self::PENDING_EXPIRES, 0);

        if ($id === null || $expires < now()->getTimestamp()) {
            $request->session()->forget([self::PENDING_USER, self::PENDING_REMEMBER, self::PENDING_EXPIRES]);

            return null;
        }

        $user = User::query()->find($id);

        return $user !== null && $user->is_active && $user->hasTwoFactorEnabled() ? $user : null;
    }
}
