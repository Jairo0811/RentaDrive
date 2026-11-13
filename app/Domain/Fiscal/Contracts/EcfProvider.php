<?php

declare(strict_types=1);

namespace App\Domain\Fiscal\Contracts;

/**
 * Puerto de integración con un proveedor de e-CF autorizado.
 *
 * No se transmite ningún documento sin un XML firmado y una
 * implementación real que valide autorización, idempotencia y respuesta.
 */
interface EcfProvider
{
    public function name(): string;

    public function configured(): bool;

    /**
     * Solo debe llamarse desde un flujo de emisión certificado y habilitado.
     *
     * @return array{reference: string, status: string}
     */
    public function transmitSignedXml(string $signedXml, string $issuerRnc): array;
}
