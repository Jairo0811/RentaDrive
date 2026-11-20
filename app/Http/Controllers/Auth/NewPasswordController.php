<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            [
                'token' => ['required'],
                'email' => ['required', 'email'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ],
            [
                'token.required' => 'Falta el enlace de recuperación. Solicita uno nuevo.',
                'email.required' => 'Escribe tu correo electrónico.',
                'email.email' => 'Introduce un correo electrónico válido.',
                'password.required' => 'Introduce una contraseña nueva.',
                'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            ],
        );

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Tu contraseña se restableció correctamente. Ya puedes iniciar sesión.');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => $status === Password::INVALID_TOKEN
                ? 'El enlace de recuperación es inválido o ha vencido. Solicita uno nuevo.'
                : 'No fue posible restablecer la contraseña. Verifica el correo e inténtalo de nuevo.',
        ]);
    }
}
