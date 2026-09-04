<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Branch;
use App\Models\Company;
use LogicException;

final class TenantContext
{
    private ?Company $company = null;

    private ?Branch $branch = null;

    public function set(Company $company, ?Branch $branch = null): void
    {
        if ($branch !== null && $branch->company_id !== $company->getKey()) {
            throw new LogicException('La sucursal no pertenece a la empresa activa.');
        }

        $this->company = $company;
        $this->branch = $branch;
    }

    public function clear(): void
    {
        $this->company = null;
        $this->branch = null;
    }

    public function hasCompany(): bool
    {
        return $this->company !== null;
    }

    public function company(): Company
    {
        return $this->company ?? throw new LogicException('No hay una empresa activa en el contexto actual.');
    }

    public function branch(): ?Branch
    {
        return $this->branch;
    }

    public function companyId(): int
    {
        return (int) $this->company()->getKey();
    }

    public function branchId(): ?int
    {
        return $this->branch?->getKey();
    }
}
