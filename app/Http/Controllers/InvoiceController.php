<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $invoice->load(['customer', 'rental.vehicle.model.brand', 'payments.receiver']);

        return view('invoices.show', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'due_at' => ['nullable', 'date', 'after_or_equal:'.$invoice->issued_at->format('Y-m-d')],
            'discount' => ['required', 'numeric', 'min:0', 'lte:'.$invoice->subtotal],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $total = max(0, (float) $invoice->subtotal + (float) $invoice->tax - (float) $data['discount']);
        $balance = max(0, $total - (float) $invoice->paid_amount);

        $invoice->update([
            ...$data,
            'total' => $total,
            'balance' => $balance,
            'status' => $balance <= 0 ? 'paid' : ((float) $invoice->paid_amount > 0 ? 'partial' : 'pending'),
        ]);

        return back()->with('status', 'Factura actualizada.');
    }

    public function download(Invoice $invoice): Response
    {
        $invoice->load(['customer', 'rental.vehicle.model.brand', 'payments']);

        return Pdf::loadView('documents.invoice', compact('invoice'))
            ->setPaper('letter')
            ->download($invoice->number.'.pdf');
    }
}
