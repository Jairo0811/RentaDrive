<?php

declare(strict_types=1);

namespace App\Support\Security;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Throwable;

/**
 * Política CSP con nonce por petición.
 *
 * La interfaz usa la build CSP de Alpine (`@alpinejs/csp`): no evalúa JavaScript desde atributos HTML, así que
 * `script-src` NO incluye 'unsafe-eval'. No hay scripts en línea sin nonce, ni orígenes de script externos, ni
 * `unsafe-inline` en scripts (ver docs/csp.md).
 */
final class ContentSecurityPolicy
{
    public const MODE_ENFORCE = 'enforce';

    public const MODE_REPORT_ONLY = 'report-only';

    public const MODE_OFF = 'off';

    private const FONT_AWESOME_HOST = 'https://cdnjs.cloudflare.com';

    public static function generateNonce(): string
    {
        return base64_encode(random_bytes(16));
    }

    public static function mode(): string
    {
        $mode = strtolower((string) config('rentadrive.security.csp.mode', self::MODE_ENFORCE));

        return in_array($mode, [self::MODE_ENFORCE, self::MODE_REPORT_ONLY, self::MODE_OFF], true)
            ? $mode
            : self::MODE_ENFORCE;
    }

    public static function headerName(): string
    {
        return self::mode() === self::MODE_REPORT_ONLY
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';
    }

    public function build(string $nonce, bool $secureRequest = false): string
    {
        $dev = $this->developmentOrigins();

        $scriptSrc = ["'self'", "'nonce-{$nonce}'", ...$dev['http']];
        // El nonce de estilos solo en producción/pruebas: en desarrollo Vite inyecta <style> sin nonce y 'unsafe-inline' se anularía si hubiera nonce.
        $styleElem = ["'self'", self::FONT_AWESOME_HOST, ...$dev['http'], ...($dev['http'] !== [] ? ["'unsafe-inline'"] : ["'nonce-{$nonce}'"])];
        $connectSrc = ["'self'", ...$dev['http'], ...$dev['ws'], ...$this->configuredList('connect')];
        $imgSrc = ["'self'", 'data:', 'blob:', ...$this->photoOrigins(), ...$this->configuredList('img')];
        $fontSrc = ["'self'", 'data:', self::FONT_AWESOME_HOST, ...$dev['http']];

        $directives = [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],
            'frame-src' => ["'none'"],
            'form-action' => ["'self'"],
            'script-src' => $scriptSrc,
            'style-src-elem' => $styleElem,
            // Atributos style="" existentes (anchos de barras, visibilidad de componentes); sin <style> en línea.
            'style-src-attr' => ["'unsafe-inline'"],
            'img-src' => $imgSrc,
            'font-src' => $fontSrc,
            'connect-src' => $connectSrc,
            'worker-src' => ["'self'", 'blob:'],
            'manifest-src' => ["'self'"],
            'media-src' => ["'self'"],
        ];

        $parts = [];
        foreach ($directives as $name => $values) {
            $parts[] = $name.' '.implode(' ', array_values(array_unique($values)));
        }

        if ($secureRequest && app()->environment('production')) {
            $parts[] = 'upgrade-insecure-requests';
        }

        $parts[] = 'report-uri '.route('csp.report', absolute: false);

        return implode('; ', $parts);
    }

    /** @return array{http: list<string>, ws: list<string>} */
    private function developmentOrigins(): array
    {
        if (app()->environment('production') || ! Vite::isRunningHot()) {
            return ['http' => [], 'ws' => []];
        }

        try {
            $origin = trim((string) file_get_contents(Vite::hotFile()));
            $parts = parse_url($origin);
        } catch (Throwable) {
            return ['http' => [], 'ws' => []];
        }

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return ['http' => [], 'ws' => []];
        }

        $authority = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $http = $parts['scheme'].'://'.$authority;
        $ws = ($parts['scheme'] === 'https' ? 'wss' : 'ws').'://'.$authority;

        // El servidor de Vite se anuncia como [::1]/localhost/0.0.0.0 según el host configurado.
        $http = [$http, 'http://localhost:'.($parts['port'] ?? 5173), 'http://127.0.0.1:'.($parts['port'] ?? 5173)];
        $ws = [$ws, 'ws://localhost:'.($parts['port'] ?? 5173), 'ws://127.0.0.1:'.($parts['port'] ?? 5173)];

        return ['http' => $http, 'ws' => $ws];
    }

    /** Origen del disco de fotos cuando es absoluto (p. ej. S3/CDN). El disco `public` local es mismo origen. */
    private function photoOrigins(): array
    {
        try {
            $url = Storage::disk((string) config('rentadrive.vehicle_photos.disk'))->url('x');
            $parts = parse_url($url);
        } catch (Throwable) {
            return [];
        }

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])
            || ! in_array($parts['scheme'], ['http', 'https'], true)) {
            return [];
        }

        // El mismo host de la aplicación ya queda cubierto por 'self'.
        if ($parts['host'] === parse_url((string) config('app.url'), PHP_URL_HOST) || $parts['host'] === request()->getHost()) {
            return [];
        }

        return [$parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')];
    }

    /** @return list<string> */
    private function configuredList(string $key): array
    {
        $raw = (string) config('rentadrive.security.csp.'.$key, '');

        return array_values(array_filter(array_map(
            static fn (string $origin): string => trim($origin),
            explode(',', $raw),
        ), static fn (string $origin): bool => preg_match('#^https?://[A-Za-z0-9.*-]+(:\d+)?$#', $origin) === 1));
    }
}
