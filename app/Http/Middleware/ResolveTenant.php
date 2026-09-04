<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null && $user->company_id !== null, 403, 'Tu usuario no tiene una empresa asignada.');

        $user->loadMissing(['company', 'branch']);

        abort_unless($user->company !== null && $user->company->status === 'active', 403, 'La empresa asociada a tu usuario no está activa.');

        if ($user->branch !== null) {
            abort_unless($user->branch->company_id === $user->company_id, 403, 'La sucursal asignada no pertenece a tu empresa.');
            abort_unless($user->branch->is_active, 403, 'La sucursal asociada a tu usuario no está activa.');
        }

        $this->tenantContext->set($user->company, $user->branch);

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }
}
