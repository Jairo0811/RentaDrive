<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Security\ContentSecurityPolicy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $csp = ContentSecurityPolicy::mode() !== ContentSecurityPolicy::MODE_OFF;
        $nonce = $csp ? ContentSecurityPolicy::generateNonce() : null;

        if ($nonce !== null) {
            // El nonce debe existir antes de renderizar las vistas: @vite y los <script> en línea lo usan.
            Vite::useCspNonce($nonce);
        }

        /** @var Response $response */
        $response = $next($request);

        if ($nonce !== null && ! $response->headers->has(ContentSecurityPolicy::headerName())) {
            $response->headers->set(
                ContentSecurityPolicy::headerName(),
                app(ContentSecurityPolicy::class)->build($nonce, $request->isSecure()),
            );
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        if ($request->user() !== null || $request->is('book/*/reservation/*')) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        }

        return $response;
    }
}
