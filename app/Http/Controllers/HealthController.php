<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Production\PlatformHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HealthController extends Controller
{
    public function live(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'RentaDrive',
            'request_id' => $request->attributes->get('request_id'),
            'time' => now()->toIso8601String(),
        ]);
    }

    public function ready(
        Request $request,
        PlatformHealthService $health,
    ): JsonResponse {
        $result = $health->check();

        return response()->json([
            'status' => $result['healthy'] ? 'ok' : 'degraded',
            'request_id' => $request->attributes->get('request_id'),
            'checks' => $result['checks'],
            'time' => now()->toIso8601String(),
        ], $result['healthy'] ? 200 : 503);
    }
}
