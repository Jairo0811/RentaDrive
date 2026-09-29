<?php

declare(strict_types=1);

namespace App\Support\Commercial;

use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;

final class OnboardingChecklist
{
    /**
     * @return array<string, array{label:string,complete:bool,route:?string}>
     */
    public function for(Company $company): array
    {
        return [
            'business_profile' => [
                'label' => 'Completar perfil comercial',
                'complete' => filled($company->name) && filled($company->email) && filled($company->phone),
                'route' => route('settings.edit'),
            ],
            'primary_branch' => [
                'label' => 'Configurar sucursal principal',
                'complete' => $company->branches()->where('is_primary', true)->where('is_active', true)->exists(),
                'route' => null,
            ],
            'administrator' => [
                'label' => 'Tener un administrador activo',
                'complete' => User::query()
                    ->where('company_id', $company->getKey())
                    ->where('is_active', true)
                    ->exists(),
                'route' => route('users.index'),
            ],
            'fleet' => [
                'label' => 'Registrar el primer vehículo',
                'complete' => Vehicle::query()->where('company_id', $company->getKey())->exists(),
                'route' => route('vehicles.create'),
            ],
            'booking' => [
                'label' => 'Revisar configuración de booking y pagos',
                'complete' => is_array($company->settings)
                    && array_key_exists('payments', $company->settings)
                    && array_key_exists('booking', $company->settings),
                'route' => route('settings.edit'),
            ],
        ];
    }

    /**
     * @param  array<string, array{label:string,complete:bool,route:?string}>  $items
     */
    public function progress(array $items): int
    {
        if ($items === []) {
            return 100;
        }

        $completed = collect($items)->where('complete', true)->count();

        return (int) round(($completed / count($items)) * 100);
    }

    /**
     * @param  array<string, array{label:string,complete:bool,route:?string}>  $items
     */
    public function complete(array $items): bool
    {
        return collect($items)->every(fn (array $item): bool => $item['complete']);
    }
}
