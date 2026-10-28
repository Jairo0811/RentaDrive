<?php

declare(strict_types=1);

namespace App\Domain\Payments\Contracts;

interface PaymentGateway
{
    public function name(): string;

    public function enabled(): bool;

    public function validateWebhook(string $rawBody, ?string $signature): bool;
}
