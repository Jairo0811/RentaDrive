<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email']],
            [
                'email.required' => 'Escribe tu correo electrónico.',
                'email.email' => 'Introduce un correo electrónico válido.',
            ],
        );

        $status = Password::sendResetLink($request->only('email'));

        // No revelar si el correo corresponde a una cuenta registrada.
        if (in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER], true)) {
            return back()->with('status', 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => $status === Password::RESET_THROTTLED
                ? 'Ya se solicitó un enlace recientemente. Inténtalo de nuevo dentro de unos minutos.'
                : 'No fue posible procesar la solicitud. Inténtalo de nuevo más tarde.',
        ]);
    }
}
