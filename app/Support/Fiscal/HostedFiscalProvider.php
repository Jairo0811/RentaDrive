<?php

declare(strict_types=1);

namespace App\Support\Fiscal;

use App\Support\Fiscal\Contracts\FiscalProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class HostedFiscalProvider implements FiscalProvider
{
    public function name(): string
    {
        return 'hosted';
    }

    public function submit(array $payload, string $idempotencyKey): array
    {
        $baseUrl = trim((string) config('services.fiscal_gateway.base_url'));
        $token = trim((string) config('services.fiscal_gateway.token'));

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('El adaptador fiscal electrónico no está configurado en el entorno.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout((int) config('services.fiscal_gateway.timeout', 20))
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->post(rtrim($baseUrl, '/').'/documents', $payload)
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new RuntimeException('El proveedor fiscal devolvió una respuesta inválida.');
        }

        $status = (string) ($response['status'] ?? 'pending');

        if (! in_array($status, ['accepted', 'pending', 'rejected'], true)) {
            $status = 'pending';
        }

        return [
            'status' => $status,
            'reference' => isset($response['reference']) ? (string) $response['reference'] : null,
            'response' => $response,
        ];
    }
}
