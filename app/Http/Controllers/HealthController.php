<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Operations\HealthReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HealthController extends Controller
{
    /** Disponibilidad pública y mínima: solo "ok" o "down" según la base de datos. */
    public function live(HealthReport $health): JsonResponse
    {
        $up = $health->databaseIsUp();

        return response()
            ->json(['status' => $up ? 'ok' : 'down'], $up ? 200 : 503)
            ->header('Cache-Control', 'no-store');
    }

    /** Detalle operativo protegido con token Bearer (HEALTH_TOKEN, ≥ 32 caracteres). Sin token configurado, no existe. */
    public function details(Request $request, HealthReport $health): JsonResponse
    {
        $expected = (string) config('rentadrive.operations.health_token');

        abort_if(strlen($expected) < 32, 404);
        abort_unless(hash_equals($expected, (string) $request->bearerToken()), 401);

        $report = $health->details();

        return response()
            ->json($report, $report['status'] === 'ok' ? 200 : 503)
            ->header('Cache-Control', 'no-store');
    }
}
