<?php

declare(strict_types=1);

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\PaymentEvent;
use App\Models\PaymentIntent;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PaymentIntentService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function beginReservationDeposit(Reservation $reservation): PaymentIntent
    {
        abort_unless($this->gateway->enabled(), 503, 'La pasarela de pagos todavía no está habilitada.');
        abort_unless($reservation->source === 'public', 404);

        return DB::transaction(function () use ($reservation): PaymentIntent {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->getKey());

            if (! in_array($reservation->status, ['pending', 'confirmed'], true) || $reservation->start_at->isPast()) {
                throw ValidationException::withMessages(['reservation' => 'La reserva ya no admite depósitos.']);
            }

            $amount = (float) $reservation->deposit_amount;

            if ($amount <= 0) {
                throw ValidationException::withMessages(['deposit' => 'Esta reserva no requiere depósito.']);
            }

            $existing = PaymentIntent::query()
                ->where('reservation_id', $reservation->getKey())
                ->where('kind', 'deposit')
                ->whereIn('status', ['pending', 'captured'])
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return PaymentIntent::query()->create([
                'company_id' => $reservation->company_id,
                'reservation_id' => $reservation->getKey(),
                'public_id' => (string) Str::uuid(),
                'kind' => 'deposit',
                'gateway' => $this->gateway->name(),
                'status' => 'pending',
                'currency' => 'DOP',
                'amount' => $amount,
            ]);
        }, 3);
    }

    /**
     * Aplica eventos firmados de la pasarela; la página pública nunca puede acreditar dinero.
     *
     * @param  array{event_id:string,intent_id:string,event_type:string,amount:numeric-string|int|float,currency:string,reference?:string}  $event
     */
    public function receive(array $event, string $rawBody, ?string $signature): void
    {
        if (! $this->gateway->validateWebhook($rawBody, $signature)) {
            abort(401, 'Firma de webhook inválida.');
        }

        DB::transaction(function () use ($event, $rawBody): void {
            $existing = PaymentEvent::query()->where('gateway', $this->gateway->name())
                ->where('event_id', $event['event_id'])->first();
            $digest = hash('sha256', $rawBody);

            if ($existing !== null) {
                abort_unless(hash_equals($existing->payload_hash, $digest), 409, 'ID de evento reutilizado.');

                return;
            }

            $intent = PaymentIntent::query()->where('public_id', $event['intent_id'])
                ->lockForUpdate()->firstOrFail();

            if ($intent->gateway !== $this->gateway->name()
                || $intent->currency !== $event['currency']
                || (int) round(((float) $intent->amount) * 100) !== (int) round(((float) $event['amount']) * 100)) {
                abort(422, 'Evento no concuerda con el intento original.');
            }

            if ($event['event_type'] === 'captured') {
                if ($intent->status !== 'pending') {
                    abort(409, 'La captura no está permitida en el estado actual.');
                }
                if ($intent->reservation !== null && $intent->reservation->status === 'cancelled') {
                    abort(409, 'No se puede cobrar una reserva cancelada.');
                }
                $intent->update([
                    'status' => 'captured',
                    'captured_at' => now(),
                    'provider_reference' => $event['reference'] ?? null,
                ]);
            } elseif ($event['event_type'] === 'refunded') {
                if ($intent->status !== 'captured') {
                    abort(409, 'Solo se puede reembolsar un depósito capturado.');
                }
                $intent->update(['status' => 'refunded', 'refunded_at' => now()]);
            } else {
                abort(422, 'Evento desconocido.');
            }

            PaymentEvent::query()->create([
                'company_id' => $intent->company_id,
                'payment_intent_id' => $intent->getKey(),
                'gateway' => $this->gateway->name(),
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type'],
                'payload_hash' => $digest,
                'processed_at' => now(),
            ]);
        }, 3);
    }
}
