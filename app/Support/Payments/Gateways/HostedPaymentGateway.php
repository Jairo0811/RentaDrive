<?php

declare(strict_types=1);

namespace App\Support\Payments\Gateways;

use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Support\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class HostedPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'hosted';
    }

    public function createCheckout(PaymentIntent $intent): array
    {
        [$baseUrl, $token] = $this->credentials();

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout((int) config('services.payment_gateway.timeout', 15))
            ->withHeaders(['Idempotency-Key' => $intent->idempotency_key])
            ->post(rtrim($baseUrl, '/').'/checkout-sessions', [
                'payment_intent_id' => $intent->public_id,
                'amount' => (float) $intent->amount,
                'currency' => $intent->currency,
                'purpose' => $intent->purpose,
                'metadata' => $intent->metadata ?? [],
            ])
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new RuntimeException('La pasarela devolvió una respuesta inválida.');
        }

        return [
            'reference' => isset($response['reference']) ? (string) $response['reference'] : null,
            'checkout_url' => isset($response['checkout_url']) ? (string) $response['checkout_url'] : null,
            'status' => isset($response['status']) ? (string) $response['status'] : 'pending',
            'metadata' => $response,
        ];
    }

    public function refund(Payment $payment, float $amount, ?string $reason = null): array
    {
        [$baseUrl, $token] = $this->credentials();

        if ($payment->gateway_transaction_id === null) {
            throw new RuntimeException('El pago no tiene una transacción de pasarela asociada.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout((int) config('services.payment_gateway.timeout', 15))
            ->withHeaders(['Idempotency-Key' => 'refund-'.$payment->getKey().'-'.number_format($amount, 2, '.', '')])
            ->post(rtrim($baseUrl, '/').'/refunds', [
                'transaction_id' => $payment->gateway_transaction_id,
                'amount' => $amount,
                'currency' => $payment->currency,
                'reason' => $reason,
            ])
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new RuntimeException('La pasarela devolvió una respuesta inválida para el reembolso.');
        }

        return [
            'reference' => isset($response['reference']) ? (string) $response['reference'] : null,
            'status' => isset($response['status']) ? (string) $response['status'] : 'pending',
            'metadata' => $response,
        ];
    }

    /**
     * @return array{string,string}
     */
    private function credentials(): array
    {
        $baseUrl = trim((string) config('services.payment_gateway.base_url'));
        $token = trim((string) config('services.payment_gateway.token'));

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('La pasarela hosted no está configurada en el entorno.');
        }

        return [$baseUrl, $token];
    }
}
