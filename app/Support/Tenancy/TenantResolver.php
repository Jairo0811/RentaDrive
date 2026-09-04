<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class TenantResolver
{
    public function __construct(private readonly TenantContext $context) {}

    public function companyId(): ?int
    {
        if ($this->context->hasCompany()) {
            return $this->context->companyId();
        }

        $user = Auth::user();

        return $user instanceof User && $user->company_id !== null
            ? (int) $user->company_id
            : null;
    }

    public function branchId(): ?int
    {
        if ($this->context->hasCompany()) {
            return $this->context->branchId();
        }

        $user = Auth::user();

        return $user instanceof User && $user->branch_id !== null
            ? (int) $user->branch_id
            : null;
    }
}
