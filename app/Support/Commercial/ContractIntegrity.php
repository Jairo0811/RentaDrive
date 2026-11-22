<?php

declare(strict_types=1);

namespace App\Support\Commercial;

use App\Models\Rental;
use App\Models\RentalAcceptance;

/**
 * Evidencia de integridad del contrato: huella SHA-256 sobre una instantánea canónica de los términos económicos y
 * de identificación. Un acuse interno NO es firma digital certificada: solo prueba qué términos veía el operador
 * y permite detectar cambios posteriores.
 */
final class ContractIntegrity
{
    public const VERSION = 2;

    public const VALID = 'valid';

    public const MODIFIED = 'modified';

    public const LEGACY = 'legacy';

    /** @return array<string, string|int|null> */
    public static function snapshot(Rental $rental): array
    {
        return [
            'code' => (string) $rental->code,
            'customer_id' => (int) $rental->customer_id,
            'vehicle_id' => (int) $rental->vehicle_id,
            'start_at' => $rental->start_at->toIso8601String(),
            'expected_return_at' => $rental->expected_return_at->toIso8601String(),
            'daily_rate' => number_format((float) $rental->daily_rate, 2, '.', ''),
            'deposit_amount' => number_format((float) $rental->deposit_amount, 2, '.', ''),
            'subtotal' => number_format((float) $rental->subtotal, 2, '.', ''),
            'fees' => number_format((float) $rental->fees, 2, '.', ''),
            'taxes' => number_format((float) $rental->taxes, 2, '.', ''),
            'total' => number_format((float) $rental->total, 2, '.', ''),
        ];
    }

    /** @param array<string, string|int|null> $snapshot */
    public static function hash(array $snapshot): string
    {
        ksort($snapshot);

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public static function status(RentalAcceptance $acceptance): string
    {
        $rental = $acceptance->rental;

        if ((int) $acceptance->hash_version < self::VERSION || $rental === null) {
            return self::LEGACY;
        }

        return hash_equals($acceptance->document_hash, self::hash(self::snapshot($rental))) ? self::VALID : self::MODIFIED;
    }
}
