<?php

declare(strict_types=1);

namespace App\Support\Security\Mfa;

use InvalidArgumentException;

/**
 * TOTP (RFC 6238) con HMAC-SHA1, 6 dígitos y paso de 30 s: el perfil compatible con
 * Google Authenticator, Microsoft Authenticator, Authy, 1Password, etc.
 * Implementación propia para no añadir dependencias a una ruta de autenticación.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public const PERIOD = 30;

    public const DIGITS = 6;

    /** Secreto aleatorio de 160 bits en Base32 (32 caracteres con 20 bytes → 32). */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function codeAt(string $secret, int $timestamp): string
    {
        return $this->codeForStep($secret, intdiv($timestamp, self::PERIOD));
    }

    public function codeForStep(string $secret, int $step): string
    {
        $key = $this->base32Decode($secret);
        $hash = hash_hmac('sha1', pack('J', $step), $key, true);

        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Devuelve el paso (time-step) que coincide, o null. Tolera ±$window pasos por desfase de reloj.
     * El llamador debe rechazar pasos ya usados para impedir la repetición de un código.
     */
    public function matchingStep(string $secret, string $code, ?int $timestamp = null, int $window = 1): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (preg_match('/^\d{'.self::DIGITS.'}$/', $code) !== 1) {
            return null;
        }

        $current = intdiv($timestamp ?? time(), self::PERIOD);
        $matched = null;

        // Se recorren todas las ventanas sin cortar en el primer acierto (comparación en tiempo constante).
        for ($offset = -$window; $offset <= $window; $offset++) {
            $step = $current + $offset;

            if (hash_equals($this->codeForStep($secret, $step), $code) && $matched === null) {
                $matched = $step;
            }
        }

        return $matched;
    }

    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer).':'.rawurlencode($account);

        return 'otpauth://totp/'.$label.'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            $output .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $output;
    }

    public function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(rtrim(preg_replace('/[\s-]+/', '', $encoded) ?? '', '='));

        if ($encoded === '' || strspn($encoded, self::ALPHABET) !== strlen($encoded)) {
            throw new InvalidArgumentException('Secreto TOTP Base32 inválido.');
        }

        $bits = '';
        foreach (str_split($encoded) as $char) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }

        return $output;
    }
}
