<?php

declare(strict_types=1);

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;

final class SandboxGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'sandbox';
    }

    public function enabled(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    public function validateWebhook(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('services.rentadrive_gateway.webhook_secret', '');

        return $this->enabled()
            && $secret !== ''
            && is_string($signature)
            && hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }
}
