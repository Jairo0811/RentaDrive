<?php

declare(strict_types=1);

namespace App\Domain\Fiscal\Services;

use App\Domain\Fiscal\Contracts\EcfProvider;
use App\Models\FiscalProfile;

/**
 * Lista informativa para preparar un tenant; no sustituye certificación DGII.
 */
final class EcfReadinessService
{
    public function __construct(private readonly EcfProvider $provider) {}

    /**
     * @return array{provider: string, provider_configured: bool, issuer_authorized: bool, emission_enabled: bool, completed_steps: int, total_steps: int, checks: list<array{key: string, label: string, complete: bool, detail: string}>}
     */
    public function summary(?FiscalProfile $profile): array
    {
        $validRnc = $profile !== null && preg_match('/^\d{9}$/D', (string) $profile->rnc) === 1;
        $hasIdentity = $validRnc && trim((string) $profile->legal_name) !== '';
        $claimedAuthorization = $profile !== null && $profile->ecf_status === 'certified';
        $providerConfigured = $this->provider->configured();

        $checks = [
            [
                'key' => 'profile',
                'label' => 'Identificación fiscal',
                'complete' => $hasIdentity,
                'detail' => $hasIdentity
                    ? 'Razón social y formato de RNC presentes; el RNC aún requiere verificación oficial.'
                    : 'Completar la razón social y un RNC con formato de 9 dígitos.',
            ],
            [
                'key' => 'issuer',
                'label' => 'Habilitación del emisor en DGII',
                'complete' => false,
                'detail' => $claimedAuthorization
                    ? 'El perfil indica certified, pero aún falta verificar evidencia de autorización del contribuyente.'
                    : 'Completar y verificar el proceso de habilitación del contribuyente ante DGII.',
            ],
            [
                'key' => 'provider',
                'label' => 'Proveedor autorizado y sandbox',
                'complete' => false,
                'detail' => $providerConfigured
                    ? 'Hay un adaptador configurado; faltan contrato, credenciales por empresa y pruebas de interoperabilidad.'
                    : 'Seleccionar proveedor autorizado con API, documentación, contrato y ambiente de pruebas.',
            ],
            [
                'key' => 'signed_xml',
                'label' => 'XML, firma y e-NCF',
                'complete' => false,
                'detail' => 'Integrar XML/XSD vigente, certificado, firma y asignación autorizada de e-NCF.',
            ],
            [
                'key' => 'acknowledgement',
                'label' => 'Acuses y aceptación',
                'complete' => false,
                'detail' => 'Validar recepción, aceptación/rechazo y conciliación con proveedor y DGII.',
            ],
        ];

        return [
            'provider' => $this->provider->name(),
            'provider_configured' => $providerConfigured,
            // Estado declarado en la BD; no equivale a comprobación documental DGII.
            'issuer_authorized' => $claimedAuthorization,
            // No existe aún un flujo de emisión certificado y probado.
            'emission_enabled' => false,
            'completed_steps' => count(array_filter($checks, static fn (array $check): bool => $check['complete'])),
            'total_steps' => count($checks),
            'checks' => $checks,
        ];
    }
}
