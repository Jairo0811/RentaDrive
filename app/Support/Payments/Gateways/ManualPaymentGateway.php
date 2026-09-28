<?php

declare(strict_types=1);

namespace App\Support\Payments\Gateways;

use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Support\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Str;

final class ManualPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function createCheckout(PaymentIntent $intent): array
    {
        return [
            'reference' => null,
            'checkout_url' => null,
            'status' => 'pending',
            'metadata' => ['message' => 'Cobro manual pendiente de confirmación administrativa.'],
        ];
    }

    public function refund(Payment $payment, float $amount, ?string $reason = null): array
    {
        return [
            'reference' => 'MANUAL-'.Str::upper(Str::random(10)),
            'status' => 'completed',
            'metadata' => ['reason' => $reason],
        ];
    }
}
