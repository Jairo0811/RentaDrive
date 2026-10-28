<?php

declare(strict_types=1);

namespace App\Domain\Fiscal\Services;

use App\Models\FiscalDraft;
use App\Models\FiscalProfile;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FiscalDraftService
{
    public function prepare(Invoice $invoice, string $kind): FiscalDraft
    {
        if (! in_array($kind, ['consumer', 'credit'], true)) {
            throw ValidationException::withMessages(['kind' => 'Tipo de borrador inválido.']);
        }

        return DB::transaction(function () use ($invoice, $kind): FiscalDraft {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            $profile = FiscalProfile::query()->where('company_id', $invoice->company_id)->first();

            if ($profile === null) {
                throw ValidationException::withMessages([
                    'fiscal' => 'Configure primero los datos fiscales de la empresa.',
                ]);
            }

            if ($profile->ecf_status !== 'not_certified') {
                throw ValidationException::withMessages(['fiscal' => 'La emisión electrónica necesita una integración certificada.']);
            }

            $snapshot = [
                'invoice_number' => $invoice->number,
                'invoice_date' => $invoice->issued_at->format('Y-m-d'),
                'issuer_name' => $profile->legal_name,
                'issuer_rnc' => $profile->rnc,
                'customer_name' => $invoice->customer->full_name,
                'customer_document' => $invoice->customer->document_number,
                'currency' => 'DOP',
                'subtotal' => $invoice->subtotal,
                'tax' => $invoice->tax,
                'discount' => $invoice->discount,
                'total' => $invoice->total,
            ];

            return FiscalDraft::query()->updateOrCreate(
                ['company_id' => $invoice->company_id, 'invoice_id' => $invoice->getKey()],
                [
                    'kind' => $kind,
                    'status' => 'draft',
                    'issuer_rnc' => $profile->rnc,
                    'buyer_document' => $invoice->customer->document_number,
                    'subtotal' => $invoice->subtotal,
                    'tax' => $invoice->tax,
                    'total' => $invoice->total,
                    'snapshot' => $snapshot,
                ],
            );
        }, 3);
    }
}
