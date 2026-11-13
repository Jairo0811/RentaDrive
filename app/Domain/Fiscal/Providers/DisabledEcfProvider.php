<?php

declare(strict_types=1);

namespace App\Domain\Fiscal\Providers;

use App\Domain\Fiscal\Contracts\EcfProvider;
use LogicException;

final class DisabledEcfProvider implements EcfProvider
{
    public function name(): string
    {
        return 'No configurado';
    }

    public function configured(): bool
    {
        return false;
    }

    public function transmitSignedXml(string $signedXml, string $issuerRnc): array
    {
        throw new LogicException('La transmisión e-CF está deshabilitada: no existe un proveedor autorizado integrado.');
    }
}
