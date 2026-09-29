<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Commercial\SubscriptionService;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null, 403);
        abort_if($user->isPlatformAdmin(), 403, 'El SuperAdmin de plataforma no opera dentro de un tenant.');
        abort_unless($user->company_id !== null, 403, 'Tu usuario no tiene una empresa asignada.');

        $user->loadMissing(['company', 'branch']);
        $company = $user->company;

        abort_unless($company !== null, 403, 'La empresa asociada a tu usuario no existe.');
        abort_unless(
            in_array($company->status, ['active', 'trial'], true),
            403,
            'La empresa asociada a tu usuario no está habilitada.',
        );

        abort_unless(
            $this->subscriptions->entitled($company),
            403,
            'La suscripción de tu empresa no está habilitada o su período de gracia venció.',
        );

        if ($user->branch !== null) {
            abort_unless($user->branch->company_id === $user->company_id, 403, 'La sucursal asignada no pertenece a tu empresa.');
            abort_unless($user->branch->is_active, 403, 'La sucursal asociada a tu usuario no está activa.');
        }

        $this->tenantContext->set($company, $user->branch);

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }
}
