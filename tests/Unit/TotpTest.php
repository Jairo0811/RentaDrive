<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\Mfa\Totp;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TotpTest extends TestCase
{
    private const RFC_SECRET = '12345678901234567890';

    /** @return array<string, array{int, string}> Vectores de RFC 6238 (SHA-1), últimos 6 dígitos. */
    public static function rfcVectors(): array
    {
        return [
            't=59' => [59, '287082'],
            't=1111111109' => [1111111109, '081804'],
            't=1111111111' => [1111111111, '050471'],
            't=1234567890' => [1234567890, '005924'],
            't=2000000000' => [2000000000, '279037'],
        ];
    }

    #[DataProvider('rfcVectors')]
    public function test_matches_rfc_6238_vectors(int $timestamp, string $expected): void
    {
        $totp = new Totp;
        $secret = $totp->base32Encode(self::RFC_SECRET);

        $this->assertSame($expected, $totp->codeAt($secret, $timestamp));
    }

    public function test_accepts_one_step_of_clock_drift_but_not_two(): void
    {
        $totp = new Totp;
        $secret = $totp->base32Encode(self::RFC_SECRET);
        $code = $totp->codeAt($secret, 59);

        $this->assertSame(1, $totp->matchingStep($secret, $code, 59));
        $this->assertSame(1, $totp->matchingStep($secret, $code, 59 + 30));
        $this->assertNull($totp->matchingStep($secret, $code, 59 + 90));
    }

    public function test_rejects_malformed_codes(): void
    {
        $totp = new Totp;
        $secret = $totp->base32Encode(self::RFC_SECRET);

        foreach (['', 'abcdef', '12345', '1234567', '28708 2x'] as $bad) {
            $this->assertNull($totp->matchingStep($secret, $bad, 59));
        }

        $this->assertSame(1, $totp->matchingStep($secret, '287 082', 59));
    }

    public function test_generated_secret_is_base32_with_160_bits(): void
    {
        $totp = new Totp;
        $secret = $totp->generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertSame(20, strlen($totp->base32Decode($secret)));
        $this->assertNotSame($secret, $totp->generateSecret());
    }

    public function test_provisioning_uri_contains_issuer_and_secret(): void
    {
        $uri = (new Totp)->provisioningUri('ABCDEFGH', 'ana@example.com', 'RentaDrive');

        $this->assertStringStartsWith('otpauth://totp/RentaDrive:ana%40example.com?', $uri);
        $this->assertStringContainsString('secret=ABCDEFGH', $uri);
        $this->assertStringContainsString('issuer=RentaDrive', $uri);
    }

    public function test_invalid_base32_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Totp)->base32Decode('not base32 !!');
    }
}
