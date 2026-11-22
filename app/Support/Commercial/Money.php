<?php

declare(strict_types=1);

namespace App\Support\Commercial;

/**
 * Aritmética monetaria en centavos enteros con redondeo "mitad hacia arriba" (sin errores de coma flotante
 * como 1.005 * 100 = 100.49999…). Solo cálculo interno: no emite comprobantes fiscales.
 */
final class Money
{
    public static function cents(float|int|string $amount): int
    {
        $string = is_string($amount) ? trim($amount) : number_format((float) $amount, 6, '.', '');
        $negative = str_starts_with($string, '-');
        $string = ltrim($string, '+-');
        [$whole, $fraction] = array_pad(explode('.', $string, 2), 2, '0');
        $fraction = str_pad($fraction, 3, '0');
        $cents = ((int) $whole) * 100 + (int) substr($fraction, 0, 2);
        if ((int) $fraction[2] >= 5) {
            $cents++;
        }

        return $negative ? -$cents : $cents;
    }

    public static function fromCents(int $cents): float
    {
        return $cents / 100;
    }

    public static function round(float|int|string $amount): float
    {
        return self::fromCents(self::cents($amount));
    }

    /** Porcentaje (p. ej. ITBIS 18) de un monto, redondeado a centavos. */
    public static function percentage(float|int|string $base, float|int|string $rate): float
    {
        $baseCents = self::cents($base);
        $basisPoints = self::cents($rate); // 18 → 1800 (centésimas de punto porcentual)

        return self::fromCents(intdiv($baseCents * $basisPoints + 5000, 10000));
    }

    public static function sum(float|int|string ...$amounts): float
    {
        return self::fromCents(array_sum(array_map(self::cents(...), $amounts)));
    }
}
