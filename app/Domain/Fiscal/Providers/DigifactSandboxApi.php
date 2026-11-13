<?php

declare(strict_types=1);

namespace App\Domain\Fiscal\Providers;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Cliente limitado al sandbox documental de Digifact RD.
 *
 * No implementa EcfProvider, no emite documentos, no usa producción y no
 * tiene rutas públicas. Solo autentica y consulta comprobantes existentes.
 *
 * Referencia: https://documentacion.digifact.com/do/api
 */
final class DigifactSandboxApi
{
    private const BASE_URL = 'https://testnucdo.digifact.com/api';

    public function authenticate(string $issuerRnc, string $username, string $password): string
    {
        self::assertRnc($issuerRnc);

        if (preg_match('/^[A-Za-z0-9_.-]{1,80}$/D', $username) !== 1 || $password === '') {
            throw new InvalidArgumentException('Credenciales de sandbox incompletas o inválidas.');
        }

        $response = Http::asJson()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(12)
            ->post(self::BASE_URL.'/login/get_token', [
                'Username' => 'DO.'.$issuerRnc.'.'.$username,
                'Password' => $password,
            ])->throw();

        $token = $response->json('Token');

        if (! is_string($token) || $token === '') {
            throw new UnexpectedValueException('Digifact sandbox no devolvió un token válido.');
        }

        return $token;
    }

    /**
     * Consulta informativa: el estado devuelto por el proveedor NO equivale a
     * aceptación fiscal verificada por el sistema ni modifica facturas.
     *
     * @return array{issuer_rnc: string, encf: string, provider_reported_status: string, track_id: string}
     */
    public function lookupExisting(string $issuerRnc, string $username, string $token, string $encf): array
    {
        self::assertRnc($issuerRnc);

        if (preg_match('/^[A-Za-z0-9_.-]{1,80}$/D', $username) !== 1
            || $token === ''
            || preg_match('/^E[0-9]{12}$/D', $encf) !== 1) {
            throw new InvalidArgumentException('Parámetros de consulta del e-CF inválidos.');
        }

        $response = Http::acceptJson()
            ->withHeaders(['Authorization' => $token])
            ->connectTimeout(5)
            ->timeout(12)
            ->get(self::BASE_URL.'/SHAREDINFO', [
                'TAXID' => $issuerRnc,
                'USERNAME' => $username,
                'DATA1' => 'SHARED_GETDTEINFO',
                'DATA2' => 'AUTHNUMBER|'.$encf,
            ])->throw();

        $data = $response->json('RESPONSE.0');

        if (! is_array($data)
            || (string) ($data['RNCEmisor'] ?? '') !== $issuerRnc
            || (string) ($data['NumeroSecuencia'] ?? '') !== $encf) {
            throw new UnexpectedValueException('La respuesta del proveedor no coincide con el RNC o e-NCF solicitado.');
        }

        return [
            'issuer_rnc' => $issuerRnc,
            'encf' => $encf,
            'provider_reported_status' => (string) ($data['ResultadoEstado'] ?? 'Desconocido'),
            'track_id' => (string) ($data['ResultadoTrackID'] ?? ''),
        ];
    }

    private static function assertRnc(string $rnc): void
    {
        if (preg_match('/^[0-9]{9}$/D', $rnc) !== 1) {
            throw new InvalidArgumentException('RNC emisor inválido: se esperan 9 dígitos.');
        }
    }
}
