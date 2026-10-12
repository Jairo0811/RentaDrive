<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolvePublicCompany
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->route('company');

        abort_unless($company instanceof Company, 404);
        abort_unless(in_array($company->status, ['active', 'trial'], true), 404);

        if ($company->status === 'trial') {
            abort_unless(
                $company->trial_ends_at !== null && $company->trial_ends_at->isFuture(),
                404,
            );
        }

        $this->tenantContext->set($company, null);

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }
}
