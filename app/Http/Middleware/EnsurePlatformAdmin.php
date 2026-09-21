<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->isPlatformAdmin() && $user->is_active,
            403,
            'No tienes acceso a la administración de plataforma.',
        );

        return $next($request);
    }
}
