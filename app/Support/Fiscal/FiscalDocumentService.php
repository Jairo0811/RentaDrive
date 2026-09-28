<?php

declare(strict_types=1);

namespace App\Support\Fiscal;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class FiscalDocumentService
{
    public function __construct(
        private readonly FiscalConfig $config,
        private readonly FiscalSequenceService $sequences,
        private readonly HostedFiscalProvider $provider,
    ) {}

    public function issue(Invoice $invoice, ?string $requestedType = null): Invoice
    {
        $invoice->loadMissing(['customer', 'rental.vehicle.model.brand']);
        $company = Company::query()->findOrFail($invoice->company_id);

        $this->config->assertIssuerReady($company);

        if ($invoice->rental->status !== 'closed') {
            throw ValidationException::withMessages([
                'fiscal' => 'Cierra el alquiler antes de emitir el comprobante fiscal definitivo.',
            ]);
        }

        if (in_array($invoice->fiscal_status, ['issued', 'pending', 'accepted'], true)) {
            return $invoice;
        }

        $type = strtoupper($requestedType ?: $this->config->defaultDocumentType($company, $invoice->customer));
        $this->config->assertDocumentType($company, $type);

        if (in_array($type, ['B01', 'E31'], true)) {
            $buyerRnc = preg_replace('/\D+/', '', (string) $invoice->customer->document_number);

            if ($invoice->customer->document_type !== 'rnc' || strlen($buyerRnc) !== 9) {
                throw ValidationException::withMessages([
                    'fiscal_document_type' => 'El comprobante de crédito fiscal requiere un cliente con RNC de 9 dígitos.',
                ]);
            }
        }

        $prepared = DB::transaction(function () use ($invoice, $company, $type): Invoice {
            /** @var Invoice $locked */
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            $ncf = $locked->ncf ?: $this->sequences->next($type);
            $taxable = max(0, round((float) $locked->subtotal - (float) $locked->discount, 2));
            $tax = round($taxable * ($this->config->taxRate() / 100), 2);
            $total = round($taxable + $tax + (float) $locked->exempt_amount, 2);
            $balance = max(0, round($total - (float) $locked->paid_amount, 2));

            $payload = [
                'document_type' => $type,
                'ncf' => $ncf,
                'issued_at' => now($company->timezone)->toIso8601String(),
                'issuer' => [
                    'rnc' => preg_replace('/\D+/', '', (string) $company->setting('fiscal.rnc', $company->rnc)),
                    'legal_name' => (string) $company->setting('fiscal.legal_name', $company->legal_name ?: $company->name),
                    'address' => (string) $company->setting('fiscal.address', ''),
                ],
                'buyer' => [
                    'document_type' => $locked->customer->document_type,
                    'document_number' => $locked->customer->document_number,
                    'name' => $locked->customer->full_name,
                    'email' => $locked->customer->email,
                ],
                'amounts' => [
                    'taxable' => $taxable,
                    'exempt' => (float) $locked->exempt_amount,
                    'tax_rate' => $this->config->taxRate(),
                    'tax' => $tax,
                    'discount' => (float) $locked->discount,
                    'total' => $total,
                    'currency' => $company->currency,
                ],
                'reference' => [
                    'invoice' => $locked->number,
                    'rental' => $locked->rental->code,
                ],
            ];

            $locked->update([
                'fiscal_document_type' => $type,
                'ncf' => $ncf,
                'fiscal_status' => str_starts_with($type, 'E') ? 'prepared' : 'issued',
                'fiscal_provider' => str_starts_with($type, 'E') ? 'hosted' : 'local_sequence',
                'tax_rate' => $this->config->taxRate(),
                'taxable_amount' => $taxable,
                'tax' => $tax,
                'total' => $total,
                'balance' => $balance,
                'status' => $balance <= 0 ? 'paid' : ((float) $locked->paid_amount > 0 ? 'partial' : 'pending'),
                'fiscal_payload' => $payload,
                'fiscal_issued_at' => str_starts_with($type, 'E') ? null : now($company->timezone),
            ]);

            return $locked->fresh(['customer', 'rental.vehicle.model.brand']);
        });

        if (! str_starts_with($type, 'E')) {
            return $prepared;
        }

        try {
            $result = $this->provider->submit(
                $prepared->fiscal_payload,
                'invoice-'.$prepared->getKey().'-'.$prepared->ncf,
            );

            $prepared->update([
                'fiscal_status' => $result['status'],
                'fiscal_reference' => $result['reference'],
                'fiscal_response' => $result['response'],
                'fiscal_issued_at' => in_array($result['status'], ['accepted', 'pending'], true)
                    ? now($company->timezone)
                    : null,
            ]);
        } catch (Throwable $exception) {
            $prepared->update([
                'fiscal_status' => 'failed',
                'fiscal_response' => ['error' => $exception->getMessage()],
            ]);

            throw $exception;
        }

        return $prepared->fresh(['customer', 'rental.vehicle.model.brand']);
    }
}
