<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\Security\Mfa\TwoFactorAuthentication;
use Illuminate\Http\RedirectResponse;

/**
 * Recuperación de MFA de administradores de empresa que perdieron su dispositivo y sus códigos.
 * Solo el SuperAdmin (tras confirmar su contraseña); el MFA del propio SuperAdmin solo se restablece por consola.
 */
final class PlatformUserMfaController extends Controller
{
    public function reset(Company $company, User $user, TwoFactorAuthentication $mfa): RedirectResponse
    {
        abort_unless($user->company_id === $company->getKey() && ! $user->isPlatformAdmin(), 404);

        if ($user->two_factor_secret === null) {
            return back()->with('status', 'Ese usuario no tiene verificación en dos pasos configurada.');
        }

        $mfa->disable($user);
        $mfa->record($user, 'mfa_reset_by_platform', actor: auth()->user());

        return back()->with('status', 'MFA restablecido. Verifica la identidad del administrador por un canal independiente antes de devolverle el acceso; deberá activarlo de nuevo.');
    }
}
