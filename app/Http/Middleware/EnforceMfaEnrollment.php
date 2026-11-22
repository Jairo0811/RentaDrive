<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Con MFA_ENFORCE=true, SuperAdmin y Administradores de empresa deben activar MFA antes de usar el panel.
 * Solo quedan accesibles el perfil, la gestión de MFA, la confirmación de contraseña y el cierre de sesión.
 */
final class EnforceMfaEnrollment
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            config('rentadrive.security.mfa.enforce') !== true
            || $user === null
            || $user->hasTwoFactorEnabled()
            || ! $user->isPrivileged()
            || $request->routeIs('profile.*', 'two-factor.*', 'password.confirm', 'logout', 'verification.*')
            || $request->is('up', 'confirm-password')
        ) {
            return $next($request);
        }

        return redirect()
            ->route('profile.edit')
            ->with('status', 'two-factor-required');
    }
}
