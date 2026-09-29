<?php

declare(strict_types=1);

namespace App\Support\Fiscal;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Validation\ValidationException;

final class FiscalConfig
{
    public const ITBIS_RATE = 18.0;

    /** @var list<string> */
    public const DOCUMENT_TYPES = ['B01', 'B02', 'E31', 'E32'];

    public function enabled(Company $company): bool
    {
        return (bool) $company->setting('fiscal.enabled', false);
    }

    public function mode(Company $company): string
    {
        $mode = (string) $company->setting('fiscal.mode', 'electronic');

        return in_array($mode, ['paper', 'electronic'], true) ? $mode : 'electronic';
    }

    public function authorizedElectronicIssuer(Company $company): bool
    {
        return (bool) $company->setting('fiscal.authorized_electronic_issuer', false);
    }

    public function taxRate(): float
    {
        return self::ITBIS_RATE;
    }

    public function defaultDocumentType(Company $company, Customer $customer): string
    {
        $creditFiscal = $customer->document_type === 'rnc';

        return match ($this->mode($company)) {
            'paper' => $creditFiscal ? 'B01' : 'B02',
            default => $creditFiscal ? 'E31' : 'E32',
        };
    }

    public function assertDocumentType(Company $company, string $type): void
    {
        if (! in_array($type, self::DOCUMENT_TYPES, true)) {
            throw ValidationException::withMessages([
                'fiscal_document_type' => 'Tipo de comprobante fiscal no soportado.',
            ]);
        }

        $electronic = str_starts_with($type, 'E');

        if ($this->mode($company) === 'electronic' && ! $electronic) {
            throw ValidationException::withMessages([
                'fiscal_document_type' => 'La empresa está configurada para e-CF y requiere una secuencia electrónica.',
            ]);
        }

        if ($this->mode($company) === 'paper' && $electronic) {
            throw ValidationException::withMessages([
                'fiscal_document_type' => 'La empresa está configurada para NCF no electrónico.',
            ]);
        }
    }

    public function assertIssuerReady(Company $company): void
    {
        if (! $this->enabled($company)) {
            throw ValidationException::withMessages([
                'fiscal' => 'La facturación fiscal no está habilitada para esta empresa.',
            ]);
        }

        $rnc = preg_replace('/\D+/', '', (string) $company->setting('fiscal.rnc', $company->rnc));

        if (strlen($rnc) !== 9) {
            throw ValidationException::withMessages([
                'fiscal' => 'Configura un RNC emisor válido de 9 dígitos.',
            ]);
        }

        if (
            $this->mode($company) === 'electronic'
            && ! $this->authorizedElectronicIssuer($company)
        ) {
            throw ValidationException::withMessages([
                'fiscal' => 'La empresa debe marcarse como emisor electrónico autorizado antes de emitir e-CF.',
            ]);
        }
    }
}
