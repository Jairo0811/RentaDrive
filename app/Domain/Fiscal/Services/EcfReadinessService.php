<?php

declare(strict_types=1);

namespace App\Domain\Fiscal\Services;

use App\Domain\Fiscal\Contracts\EcfProvider;
use App\Models\FiscalProfile;

/**
 * Estado informativo para backoffice; no autoriza la emisión.
 */
final class EcfReadinessService
{
    public function __construct(private readonly EcfProvider $provider) {}

    /**
     * @return array{provider: string, provider_configured: bool, issuer_authorized: bool, emission_enabled: bool}
     */
    public function summary(?FiscalProfile $profile): array
    {
        return [
            'provider' => $this->provider->name(),
            'provider_configured' => $this->provider->configured(),
            'issuer_authorized' => $profile !== null && $profile->ecf_status === 'certified',
            // No existe aún firma, asignación de e-NCF, transmisión ni conciliación certificadas.
            'emission_enabled' => false,
        ];
    }
}
