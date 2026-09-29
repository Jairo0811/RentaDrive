<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Models\Invoice;
use App\Models\Reservation;

final class PaymentReconciliationService
{
    public function __construct(private readonly PaymentLedgerService $ledger) {}

    /**
     * @return array{invoice_mismatches:int,reservation_mismatches:int}
     */
    public function reconcile(bool $repair = false): array
    {
        $invoiceMismatches = 0;
        $reservationMismatches = 0;

        Invoice::query()->with('payments')->chunkById(100, function ($invoices) use ($repair, &$invoiceMismatches): void {
            foreach ($invoices as $invoice) {
                $expected = round((float) $invoice->payments->sum(
                    fn ($payment): float => $payment->netAmount(),
                ), 2);

                if (abs($expected - (float) $invoice->paid_amount) < 0.01) {
                    continue;
                }

                $invoiceMismatches++;

                if ($repair) {
                    $this->ledger->recalculateInvoice($invoice->getKey());
                }
            }
        });

        Reservation::query()
            ->where('deposit_required', '>', 0)
            ->with('payments')
            ->chunkById(100, function ($reservations) use ($repair, &$reservationMismatches): void {
                foreach ($reservations as $reservation) {
                    $expected = min(
                        (float) $reservation->deposit_required,
                        round((float) $reservation->payments->sum(
                            fn ($payment): float => $payment->netAmount(),
                        ), 2),
                    );

                    if (abs($expected - (float) $reservation->deposit_paid) < 0.01) {
                        continue;
                    }

                    $reservationMismatches++;

                    if ($repair) {
                        $this->ledger->recalculateReservation($reservation->getKey());
                    }
                }
            });

        return [
            'invoice_mismatches' => $invoiceMismatches,
            'reservation_mismatches' => $reservationMismatches,
        ];
    }
}
