<?php

declare(strict_types=1);

namespace App\Support\Payments\Contracts;

use App\Models\Payment;
use App\Models\PaymentIntent;

interface PaymentGateway
{
    public function name(): string;

    /**
     * @return array{reference:?string,checkout_url:?string,status:string,metadata:array<string,mixed>}
     */
    public function createCheckout(PaymentIntent $intent): array;

    /**
     * @return array{reference:?string,status:string,metadata:array<string,mixed>}
     */
    public function refund(Payment $payment, float $amount, ?string $reason = null): array;
}
