<?php

declare(strict_types=1);

namespace App\Support\Commercial;

use App\Models\Company;

final class PublicDomainResolver
{
    public function resolve(string $host): ?Company
    {
        $host = strtolower(trim($host));
        $host = explode(':', $host, 2)[0];

        if ($host === '') {
            return null;
        }

        $company = Company::query()
            ->where('public_domain', $host)
            ->whereIn('status', ['active', 'trial'])
            ->first();

        if ($company === null) {
            return null;
        }

        if (
            $company->status === 'trial'
            && $company->trial_ends_at !== null
            && $company->trial_ends_at->isPast()
        ) {
            return null;
        }

        return $company;
    }
}
