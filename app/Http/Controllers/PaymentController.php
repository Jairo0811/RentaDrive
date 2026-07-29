<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Operations\Services\ReferenceNumberService;
use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['invoice.customer', 'receiver'])
            ->when($request->filled('method'), fn ($query) => $query->where('method', $request->string('method')))
            ->latest('paid_at')
            ->paginate(15)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'openInvoices' => Invoice::query()->with('customer')->where('balance', '>', 0)->orderBy('due_at')->get(),
        ]);
    }

    public function store(PaymentRequest $request, ReferenceNumberService $references): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $references): void {
            /** @var Invoice $invoice */
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($data['invoice_id']);

            if ((float) $data['amount'] > (float) $invoice->balance) {
                throw ValidationException::withMessages([
                    'amount' => 'El pago no puede exceder el balance pendiente de la factura.',
                ]);
            }

            Payment::query()->create([
                ...$data,
                'receipt_number' => $references->generate(Payment::class, 'receipt_number', 'REC'),
                'received_by' => auth()->id(),
            ]);

            $paidAmount = (float) $invoice->paid_amount + (float) $data['amount'];
            $balance = max(0, (float) $invoice->total - $paidAmount);

            $invoice->update([
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'status' => $balance <= 0 ? 'paid' : 'partial',
            ]);
        });

        return back()->with('status', 'Pago aplicado correctamente.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        DB::transaction(function () use ($payment): void {
            /** @var Invoice $invoice */
            $invoice = $payment->invoice()->lockForUpdate()->firstOrFail();
            $payment->delete();
            $paidAmount = (float) $invoice->payments()->sum('amount');
            $balance = max(0, (float) $invoice->total - $paidAmount);
            $invoice->update([
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'status' => $balance <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending'),
            ]);
        });

        return back()->with('status', 'Pago anulado y factura recalculada.');
    }
}
