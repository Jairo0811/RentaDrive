<?php

declare(strict_types=1);

namespace App\Support\Commercial;

use App\Models\Company;
use Illuminate\Validation\ValidationException;

final class PlanLimits
{
    /**
     * @return array{name: string, max_users: int, max_branches: int, max_vehicles: int, trial_days?: int}
     */
    public function definition(Company $company): array
    {
        $plans = (array) config('rentadrive.plans');
        $definition = $plans[$company->plan_code] ?? null;

        if (! is_array($definition)) {
            throw ValidationException::withMessages([
                'plan_code' => 'El plan comercial configurado para la empresa no existe.',
            ]);
        }

        /** @var array{name: string, max_users: int, max_branches: int, max_vehicles: int, trial_days?: int} $definition */
        return $definition;
    }

    public function ensureCanAdd(Company $company, string $resource, int $currentCount): void
    {
        $definition = $this->definition($company);

        $key = match ($resource) {
            'users' => 'max_users',
            'branches' => 'max_branches',
            'vehicles' => 'max_vehicles',
            default => throw ValidationException::withMessages([
                'plan_code' => 'El recurso comercial solicitado no está configurado.',
            ]),
        };

        $limit = (int) $definition[$key];

        if ($currentCount >= $limit) {
            $label = match ($resource) {
                'users' => 'usuarios',
                'branches' => 'sucursales',
                'vehicles' => 'vehículos',
            };

            throw ValidationException::withMessages([
                $resource => "El plan {$definition['name']} permite hasta {$limit} {$label}.",
            ]);
        }
    }
}
