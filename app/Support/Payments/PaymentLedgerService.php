<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Domain\Operations\Services\ReferenceNumberService;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PaymentLedgerService
{
    public function __construct(
        private readonly ReferenceNumberService $references,
        private readonly PaymentGatewayManager $gateways,
    ) {}

    /**
     * @param array<string,mixed> $data
     */
    public function record(array $data): Payment
    {
        return DB::transaction(function () use ($data): Payment {
            $idempotencyKey = isset($data['idempotency_key']) ? (string) $data['idempotency_key'] : null;

            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $payment = Payment::query()->create([
                ...$data,
                'receipt_number' => $data['receipt_number'] ?? $this->references->generate(Payment::class, 'receipt_number', 'REC'),
                'paid_at' => $data['paid_at'] ?? now(),
                'gateway' => $data['gateway'] ?? 'manual',
                'currency' => $data['currency'] ?? 'DOP',
                'status' => $data['status'] ?? 'completed',
                'refunded_amount' => 0,
            ]);

            $this->recalculateInvoice($payment->invoice_id);
            $this->recalculateReservation($payment->reservation_id);

            return $payment;
        });
    }

    public function refund(Payment $payment, float $amount, ?string $reason = null): PaymentRefund
    {
        if ($amount <= 0 || $amount > $payment->netAmount()) {
            throw ValidationException::withMessages([
                'amount' => 'El reembolso debe ser mayor que cero y no exceder el monto neto disponible.',
            ]);
        }

        $company = Company::query()->findOrFail($payment->company_id);
        $result = $this->gateways->forCompany($company)->refund($payment, $amount, $reason);

        if (! in_array($result['status'], ['completed', 'pending'], true)) {
            throw ValidationException::withMessages([
                'amount' => 'La pasarela rechazó el reembolso.',
            ]);
        }

        return DB::transaction(function () use ($payment, $amount, $reason, $result): PaymentRefund {
            $refund = PaymentRefund::query()->create([
                'payment_id' => $payment->getKey(),
                'amount' => $amount,
                'status' => $result['status'],
                'reference' => $result['reference'],
                'reason' => $reason,
                'processed_by' => auth()->id(),
                'refunded_at' => now(),
                'metadata' => $result['metadata'],
            ]);

            if ($refund->status === 'completed') {
                $newRefunded = round((float) $payment->refunded_amount + $amount, 2);
                $payment->update([
                    'refunded_amount' => $newRefunded,
                    'status' => $newRefunded >= (float) $payment->amount ? 'refunded' : 'partial_refund',
                ]);

                $this->recalculateInvoice($payment->invoice_id);
                $this->recalculateReservation($payment->reservation_id);
            }

            return $refund;
        });
    }

    public function recalculateInvoice(?int $invoiceId): void
    {
        if ($invoiceId === null) {
            return;
        }

        /** @var Invoice|null $invoice */
        $invoice = Invoice::query()->lockForUpdate()->find($invoiceId);

        if ($invoice === null) {
            return;
        }

        $paidAmount = round((float) $invoice->payments()
            ->whereIn('status', ['completed', 'partial_refund', 'refunded'])
            ->selectRaw('COALESCE(SUM(amount - refunded_amount), 0) AS net_paid')
            ->value('net_paid'), 2);

        $balance = max(0, round((float) $invoice->total - $paidAmount, 2));

        $invoice->update([
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'status' => $balance <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending'),
        ]);
    }

    public function recalculateReservation(?int $reservationId): void
    {
        if ($reservationId === null) {
            return;
        }

        /** @var Reservation|null $reservation */
        $reservation = Reservation::query()->lockForUpdate()->find($reservationId);

        if ($reservation === null) {
            return;
        }

        $paid = round((float) $reservation->payments()
            ->whereIn('status', ['completed', 'partial_refund', 'refunded'])
            ->selectRaw('COALESCE(SUM(amount - refunded_amount), 0) AS net_paid')
            ->value('net_paid'), 2);

        $reservation->update([
            'deposit_paid' => min((float) $reservation->deposit_required, $paid),
        ]);
    }
}
