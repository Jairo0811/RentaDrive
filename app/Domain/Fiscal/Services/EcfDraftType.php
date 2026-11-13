<?php

declare(strict_types=1);

namespace App\Domain\Fiscal\Services;

use InvalidArgumentException;

/**
 * Identifica la familia de comprobante; NO genera ni reserva un e-NCF.
 */
final class EcfDraftType
{
    public static function fromKind(string $kind): string
    {
        return match ($kind) {
            'credit' => 'E31',
            'consumer' => 'E32',
            default => throw new InvalidArgumentException('Tipo de borrador fiscal no soportado.'),
        };
    }
}
