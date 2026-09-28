<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\Fiscal\FiscalConfig;
use App\Support\Fiscal\FiscalDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->with(['customer', 'rental.vehicle'])
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request): void {
                $search = '%'.$request->string('q')->value().'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('number', 'like', $search)
                        ->orWhere('ncf', 'like', $search)
                        ->orWhereHas('customer', fn ($query) => $query
                            ->where('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('issued_at')
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['company', 'customer', 'rental.vehicle.model.brand', 'payments.receiver', 'payments.refunds']);

        return view('invoices.show', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice, FiscalConfig $fiscal): RedirectResponse
    {
        $data = $request->validate([
            'due_at' => ['nullable', 'date', 'after_or_equal:'.$invoice->issued_at->format('Y-m-d')],
            'discount' => ['required', 'numeric', 'min:0', 'lte:'.$invoice->subtotal],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (
            in_array($invoice->fiscal_status, ['issued', 'pending', 'accepted'], true)
            && abs((float) $data['discount'] - (float) $invoice->discount) >= 0.01
        ) {
            throw ValidationException::withMessages([
                'discount' => 'No puedes alterar importes después de emitir el comprobante fiscal.',
            ]);
        }

        $taxable = max(0, round((float) $invoice->subtotal - (float) $data['discount'], 2));
        $tax = round($taxable * ($fiscal->taxRate() / 100), 2);
        $total = round($taxable + $tax + (float) $invoice->exempt_amount, 2);
        $balance = max(0, round($total - (float) $invoice->paid_amount, 2));

        $invoice->update([
            ...$data,
            'tax_rate' => $fiscal->taxRate(),
            'taxable_amount' => $taxable,
            'tax' => $tax,
            'total' => $total,
            'balance' => $balance,
            'status' => $balance <= 0 ? 'paid' : ((float) $invoice->paid_amount > 0 ? 'partial' : 'pending'),
        ]);

        return back()->with('status', 'Factura actualizada.');
    }

    public function issueFiscal(
        Request $request,
        Invoice $invoice,
        FiscalDocumentService $fiscalDocuments,
    ): RedirectResponse {
        $data = $request->validate([
            'fiscal_document_type' => ['nullable', Rule::in(FiscalConfig::DOCUMENT_TYPES)],
        ]);

        $issued = $fiscalDocuments->issue(
            $invoice,
            isset($data['fiscal_document_type']) ? (string) $data['fiscal_document_type'] : null,
        );

        return back()->with(
            'status',
            'Comprobante fiscal '.$issued->ncf.' procesado con estado '.$issued->fiscal_status.'.',
        );
    }

    public function download(Invoice $invoice): Response
    {
        $invoice->load(['company', 'customer', 'rental.vehicle.model.brand', 'payments']);

        return Pdf::loadView('documents.invoice', compact('invoice'))
            ->setPaper('letter')
            ->download($invoice->number.'.pdf');
    }
}
