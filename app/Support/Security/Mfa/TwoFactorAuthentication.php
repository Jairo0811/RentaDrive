<?php

declare(strict_types=1);

namespace App\Support\Security\Mfa;

use App\Models\AuditLog;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Ciclo de vida de MFA TOTP. Nunca registra secretos ni códigos (ni en logs ni en la auditoría).
 */
final class TwoFactorAuthentication
{
    public const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly Totp $totp) {}

    /**
     * Genera un secreto pendiente de confirmar. Mientras no se confirme, el login no exige segundo factor.
     */
    public function beginSetup(User $user): string
    {
        $secret = $this->totp->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
        ])->saveQuietly();

        return $secret;
    }

    public function provisioningUri(User $user): ?string
    {
        if ($user->two_factor_secret === null) {
            return null;
        }

        return $this->totp->provisioningUri(
            $user->two_factor_secret,
            $user->email,
            (string) config('rentadrive.security.mfa.issuer', config('app.name', 'RentaDrive')),
        );
    }

    /**
     * Código QR (SVG en línea, generado en el servidor: el secreto no sale a ningún servicio externo).
     */
    public function qrCodeSvg(User $user): ?string
    {
        $uri = $this->provisioningUri($user);

        if ($uri === null) {
            return null;
        }

        $svg = (new Writer(new ImageRenderer(new RendererStyle(200, 2), new SvgImageBackEnd)))->writeString($uri);

        // Quita la declaración XML para poder incrustarlo en HTML.
        return trim((string) preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
    }

    /**
     * Confirma la activación con un código válido y devuelve los códigos de recuperación en claro (se muestran una sola vez).
     *
     * @return list<string>|null null si el código no es válido
     */
    public function confirmSetup(User $user, string $code): ?array
    {
        if ($user->two_factor_secret === null || $user->two_factor_confirmed_at !== null) {
            return null;
        }

        if (! $this->consumeTotp($user, $code)) {
            return null;
        }

        $plain = $this->newRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->hashAll($plain),
            // Invalida sesiones "recordarme" emitidas antes de activar el segundo factor.
            'remember_token' => Str::random(60),
        ])->saveQuietly();

        $this->record($user, 'mfa_enabled');

        return $plain;
    }

    /** Verifica un código TOTP del usuario con MFA activo (sin reutilización dentro de la misma ventana). */
    public function verifyCode(User $user, string $code): bool
    {
        if (! $user->hasTwoFactorEnabled()) {
            return false;
        }

        return $this->consumeTotp($user, $code);
    }

    /** Verifica y consume (un solo uso) un código de recuperación. */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $normalized = $this->normalizeRecoveryCode($code);

        if ($normalized === '' || ! $user->hasTwoFactorEnabled()) {
            return false;
        }

        $used = DB::transaction(function () use ($user, $normalized): bool {
            /** @var User|null $locked */
            $locked = User::query()->lockForUpdate()->find($user->getKey());
            $codes = $locked?->two_factor_recovery_codes;

            if ($locked === null || ! is_array($codes)) {
                return false;
            }

            $candidate = $this->hash($normalized);
            $remaining = [];
            $matched = false;

            foreach ($codes as $stored) {
                if (! $matched && hash_equals((string) $stored, $candidate)) {
                    $matched = true;

                    continue;
                }

                $remaining[] = $stored;
            }

            if (! $matched) {
                return false;
            }

            $locked->forceFill(['two_factor_recovery_codes' => $remaining])->saveQuietly();

            return true;
        });

        if ($used) {
            $user->refresh();
            $this->record($user, 'mfa_recovery_used', ['remaining' => count($user->two_factor_recovery_codes ?? [])]);
        }

        return $used;
    }

    public function recoveryCodesRemaining(User $user): int
    {
        return count($user->two_factor_recovery_codes ?? []);
    }

    /**
     * Sustituye todos los códigos anteriores; exige un TOTP válido. Devuelve los nuevos en claro o null.
     *
     * @return list<string>|null
     */
    public function regenerateRecoveryCodes(User $user, string $code): ?array
    {
        if (! $this->verifyCode($user, $code)) {
            return null;
        }

        $plain = $this->newRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $this->hashAll($plain)])->saveQuietly();

        $this->record($user, 'mfa_codes_regenerated');

        return $plain;
    }

    public function disable(User $user): void
    {
        $wasEnabled = $user->hasTwoFactorEnabled();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
            'remember_token' => Str::random(60),
        ])->saveQuietly();

        if ($wasEnabled) {
            $this->record($user, 'mfa_disabled');
        }
    }

    /** Registra un evento de seguridad (auditoría + log estructurado) sin datos secretos. */
    public function record(User $user, string $event, array $context = []): void
    {
        AuditLog::withoutEvents(function () use ($user, $event): void {
            AuditLog::query()->create([
                'company_id' => $user->company_id,
                'user_id' => $user->getKey(),
                'event' => $event,
                'auditable_type' => User::class,
                'auditable_id' => $user->getKey(),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        });

        Log::info('security.mfa', [
            'event' => $event,
            'user_id' => $user->getKey(),
            'ip' => request()?->ip(),
            ...array_intersect_key($context, ['remaining' => true]),
        ]);
    }

    /**
     * Valida el TOTP y marca su intervalo como usado de forma atómica (evita repetición y carreras).
     */
    private function consumeTotp(User $user, string $code): bool
    {
        $secret = $user->two_factor_secret;

        if ($secret === null) {
            return false;
        }

        $step = $this->totp->matchingStep($secret, $code);

        if ($step === null) {
            return false;
        }

        $claimed = DB::table('users')
            ->where('id', $user->getKey())
            ->where(fn ($query) => $query
                ->whereNull('two_factor_last_used_step')
                ->orWhere('two_factor_last_used_step', '<', $step))
            ->update(['two_factor_last_used_step' => $step]);

        if ($claimed !== 1) {
            return false;
        }

        $user->setAttribute('two_factor_last_used_step', $step);
        $user->syncOriginalAttribute('two_factor_last_used_step');

        return true;
    }

    /** @return list<string> */
    private function newRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $codes[] = strtolower(Str::random(5)).'-'.strtolower(Str::random(5));
        }

        return $codes;
    }

    /**
     * @param  list<string>  $plain
     * @return list<string>
     */
    private function hashAll(array $plain): array
    {
        return array_map(fn (string $code): string => $this->hash($this->normalizeRecoveryCode($code)), $plain);
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $code) ?? '');
    }

    private function hash(string $normalized): string
    {
        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }
}
