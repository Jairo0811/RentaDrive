<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PaymentIntent;
use App\Models\PaymentWebhookEvent;
use App\Support\Payments\PaymentLedgerService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

final class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $gateway,
        TenantContext $tenant,
        PaymentLedgerService $ledger,
    ): JsonResponse {
        abort_unless($gateway === 'hosted', 404);

        $secret = (string) config('services.payment_gateway.webhook_secret');
        $signature = (string) $request->header('X-RentaDrive-Signature');
        $raw = $request->getContent();

        if ($secret === '' || $signature === '' || ! hash_equals(hash_hmac('sha256', $raw, $secret), $signature)) {
            throw new AccessDeniedHttpException('Firma de webhook inválida.');
        }

        $payload = $request->json()->all();
        $eventId = trim((string) ($payload['event_id'] ?? ''));
        $eventType = trim((string) ($payload['type'] ?? ''));

        abort_if($eventId === '' || $eventType === '', 422, 'Evento inválido.');

        $existing = PaymentWebhookEvent::query()
            ->where('provider', $gateway)
            ->where('event_id', $eventId)
            ->first();

        if ($existing !== null && $existing->status === 'processed') {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $event = $existing ?? PaymentWebhookEvent::query()->create([
            'provider' => $gateway,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payload_hash' => hash('sha256', $raw),
            'payload' => $payload,
            'status' => 'received',
        ]);

        try {
            DB::transaction(function () use ($payload, $eventType, $event, $tenant, $ledger, $gateway): void {
                $intentPublicId = (string) data_get($payload, 'data.payment_intent_id');

                /** @var PaymentIntent $intent */
                $intent = PaymentIntent::withoutGlobalScopes()
                    ->where('public_id', $intentPublicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $company = Company::query()->findOrFail($intent->company_id);
                $tenant->set($company, $intent->branch_id ? $company->branches()->find($intent->branch_id) : null);

                $event->update(['company_id' => $company->getKey()]);

                if ($eventType === 'payment.captured') {
                    $transactionId = trim((string) data_get($payload, 'data.transaction_id'));
                    $amount = round((float) data_get($payload, 'data.amount'), 2);

                    abort_if($transactionId === '' || abs($amount - (float) $intent->amount) >= 0.01, 422, 'Captura inválida.');

                    $ledger->record([
                        'invoice_id' => $intent->invoice_id,
                        'reservation_id' => $intent->reservation_id,
                        'paid_at' => now(),
                        'method' => 'card',
                        'reference' => $transactionId,
                        'amount' => $amount,
                        'gateway' => $gateway,
                        'gateway_transaction_id' => $transactionId,
                        'currency' => $intent->currency,
                        'status' => 'completed',
                        'idempotency_key' => 'webhook:'.$gateway.':'.$event->event_id,
                        'notes' => 'Pago confirmado por webhook.',
                        'received_by' => null,
                    ]);

                    $intent->update([
                        'gateway_reference' => $intent->gateway_reference ?: $transactionId,
                        'status' => 'paid',
                    ]);
                } elseif ($eventType === 'payment.failed') {
                    $intent->update(['status' => 'failed']);
                }

                $event->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'error' => null,
                ]);
            });
        } catch (Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            throw $exception;
        }

        return response()->json(['ok' => true]);
    }
}
