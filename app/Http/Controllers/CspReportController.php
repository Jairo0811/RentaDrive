<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Recibe violaciones de CSP (formatos `report-uri` y Reporting API) y las registra de forma mínima.
 * Es un endpoint público: acota el tamaño, no persiste el cuerpo y solo registra campos conocidos y truncados.
 */
final class CspReportController extends Controller
{
    private const MAX_BYTES = 8192;

    public function __invoke(Request $request): Response
    {
        $raw = $request->getContent();

        if ($raw === '' || strlen($raw) > self::MAX_BYTES) {
            return response()->noContent();
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return response()->noContent();
        }

        // `report-uri`: {"csp-report": {...}}; Reporting API: [{"type":"csp-violation","body":{...}}].
        $reports = isset($decoded['csp-report']) ? [$decoded['csp-report']] : array_column(
            array_filter($decoded, static fn ($item): bool => is_array($item) && ($item['type'] ?? null) === 'csp-violation'),
            'body',
        );

        foreach (array_slice($reports, 0, 5) as $report) {
            if (! is_array($report)) {
                continue;
            }

            Log::warning('security.csp_violation', [
                'directive' => $this->text($report['effective-directive'] ?? $report['effectiveDirective'] ?? $report['violated-directive'] ?? null),
                'blocked' => $this->origin($report['blocked-uri'] ?? $report['blockedURL'] ?? null),
                'page' => $this->path($report['document-uri'] ?? $report['documentURL'] ?? null),
                'source' => $this->origin($report['source-file'] ?? $report['sourceFile'] ?? null),
                'line' => is_numeric($report['line-number'] ?? $report['lineNumber'] ?? null) ? (int) ($report['line-number'] ?? $report['lineNumber']) : null,
                'disposition' => $this->text($report['disposition'] ?? null),
            ]);
        }

        return response()->noContent();
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) ? Str::limit($value, 80, '') : null;
    }

    /** Solo esquema+host (o palabra clave como inline/eval): nunca rutas ni consultas del recurso bloqueado. */
    private function origin(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $parts = parse_url($value);

        if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
            return $parts['scheme'].'://'.$parts['host'];
        }

        return Str::limit($value, 40, '');
    }

    /** Solo la ruta de la página (sin consulta ni fragmento, que podrían contener tokens). */
    private function path(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);

        return is_string($path) ? Str::limit(preg_replace('#/book/[^/]+/reservation/[^/]+#', '/book/*/reservation/*', $path) ?? $path, 120, '') : null;
    }
}
