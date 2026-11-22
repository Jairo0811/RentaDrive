<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        if ($user->hasTwoFactorEnabled()) {
            // Las credenciales son correctas, pero todavía no hay sesión: solo un "pase" temporal hacia el desafío MFA.
            $request->session()->regenerate();
            $request->session()->put(TwoFactorChallengeController::PENDING_USER, $user->getKey());
            $request->session()->put(TwoFactorChallengeController::PENDING_REMEMBER, $request->boolean('remember'));
            $request->session()->put(TwoFactorChallengeController::PENDING_EXPIRES, now()->addMinutes(TwoFactorChallengeController::TTL_MINUTES)->getTimestamp());

            return redirect()->route('two-factor.challenge');
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->isPlatformAdmin()) {
            return redirect()->route('platform.dashboard');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
