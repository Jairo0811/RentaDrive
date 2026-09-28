<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Payments\PaymentLedgerService;
use App\Support\Payments\PaymentReconciliationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['invoice.customer', 'reservation.customer', 'receiver', 'refunds'])
            ->when($request->filled('method'), fn ($query) => $query->where('method', $request->string('method')))
            ->latest('paid_at')
            ->paginate(15)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'openInvoices' => Invoice::query()->with(['customer', 'company'])->where('balance', '>', 0)->orderBy('due_at')->get(),
        ]);
    }

    public function store(PaymentRequest $request, PaymentLedgerService $ledger): RedirectResponse
    {
        $data = $request->validated();

        /** @var Invoice $invoice */
        $invoice = Invoice::query()->findOrFail($data['invoice_id']);

        if ((float) $data['amount'] > (float) $invoice->balance) {
            throw ValidationException::withMessages([
                'amount' => 'El pago no puede exceder el balance pendiente de la factura.',
            ]);
        }

        $ledger->record([
            ...$data,
            'gateway' => 'manual',
            'currency' => $request->user()?->company?->currency ?? 'DOP',
            'status' => 'completed',
            'received_by' => auth()->id(),
        ]);

        return back()->with('status', 'Pago aplicado correctamente.');
    }

    public function refund(
        Request $request,
        Payment $payment,
        PaymentLedgerService $ledger,
    ): RedirectResponse {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $refund = $ledger->refund(
            $payment,
            round((float) $data['amount'], 2),
            $data['reason'] ?? null,
        );

        return back()->with('status', 'Reembolso registrado con estado '.$refund->status.'.');
    }

    public function destroy(Payment $payment, PaymentLedgerService $ledger): RedirectResponse
    {
        $net = $payment->netAmount();

        if ($net <= 0) {
            throw ValidationException::withMessages([
                'payment' => 'El pago ya está totalmente reembolsado.',
            ]);
        }

        $ledger->refund($payment, $net, 'Anulación administrativa del pago.');

        return back()->with('status', 'Pago anulado mediante reembolso íntegro; se preservó la trazabilidad.');
    }

    public function reconcile(PaymentReconciliationService $reconciliation): RedirectResponse
    {
        $result = $reconciliation->reconcile(true);

        return back()->with(
            'status',
            sprintf(
                'Conciliación completada: %d factura(s) y %d reserva(s) reparadas.',
                $result['invoice_mismatches'],
                $result['reservation_mismatches'],
            ),
        );
    }
}
