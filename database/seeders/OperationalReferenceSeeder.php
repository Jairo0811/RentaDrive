<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class OperationalReferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->settings() as $key => [$group, $value]) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['group' => $group, 'value' => $value, 'type' => 'string'],
                );
            }

            $categories = collect([
                ['code' => 'ECO', 'name' => 'Económico', 'daily_rate' => 1800, 'deposit_amount' => 5000, 'description' => 'Vehículos compactos y eficientes.'],
                ['code' => 'SED', 'name' => 'Sedán', 'daily_rate' => 2600, 'deposit_amount' => 7000, 'description' => 'Sedanes cómodos para ciudad y carretera.'],
                ['code' => 'SUV', 'name' => 'SUV', 'daily_rate' => 4200, 'deposit_amount' => 10000, 'description' => 'Mayor espacio, altura y capacidad.'],
                ['code' => 'PRE', 'name' => 'Premium', 'daily_rate' => 6500, 'deposit_amount' => 15000, 'description' => 'Vehículos de gama alta.'],
            ])->mapWithKeys(function (array $category): array {
                $model = VehicleCategory::query()->updateOrCreate(
                    ['code' => $category['code']],
                    [...$category, 'is_active' => true],
                );

                return [$model->code => $model];
            });

            $toyota = VehicleBrand::query()->updateOrCreate(['name' => 'Toyota'], ['is_active' => true]);
            $hyundai = VehicleBrand::query()->updateOrCreate(['name' => 'Hyundai'], ['is_active' => true]);

            $models = collect([
                ['key' => 'corolla', 'brand' => $toyota, 'name' => 'Corolla', 'year' => 2025],
                ['key' => 'rav4', 'brand' => $toyota, 'name' => 'RAV4', 'year' => 2025],
                ['key' => 'accent', 'brand' => $hyundai, 'name' => 'Accent', 'year' => 2024],
            ])->mapWithKeys(function (array $item): array {
                $model = VehicleModel::query()->updateOrCreate(
                    [
                        'vehicle_brand_id' => $item['brand']->id,
                        'name' => $item['name'],
                        'year' => $item['year'],
                    ],
                    ['is_active' => true],
                );

                return [$item['key'] => $model];
            });

            if (! app()->environment(['local', 'testing'])) {
                return;
            }

            Customer::query()->firstOrCreate(
                ['document_number' => '001-0000001-1'],
                [
                    'document_type' => 'cedula',
                    'first_name' => 'María',
                    'last_name' => 'Rodríguez',
                    'email' => 'maria@example.test',
                    'phone' => '809-555-0101',
                    'license_number' => 'LIC-DEMO-001',
                    'license_expiry' => now()->addYears(3)->toDateString(),
                    'city' => 'Santo Domingo',
                    'status' => 'active',
                ],
            );

            foreach ([
                ['code' => 'RD-001', 'plate' => 'A123456', 'model' => $models['corolla'], 'category' => $categories['SED'], 'color' => 'Blanco', 'mileage' => 18200],
                ['code' => 'RD-002', 'plate' => 'G234567', 'model' => $models['rav4'], 'category' => $categories['SUV'], 'color' => 'Azul', 'mileage' => 9400],
                ['code' => 'RD-003', 'plate' => 'A345678', 'model' => $models['accent'], 'category' => $categories['ECO'], 'color' => 'Gris', 'mileage' => 22100],
            ] as $vehicle) {
                Vehicle::query()->firstOrCreate(
                    ['code' => $vehicle['code']],
                    [
                        'plate' => $vehicle['plate'],
                        'vehicle_model_id' => $vehicle['model']->id,
                        'vehicle_category_id' => $vehicle['category']->id,
                        'color' => $vehicle['color'],
                        'transmission' => 'automatic',
                        'fuel_type' => 'gasoline',
                        'seats' => 5,
                        'mileage' => $vehicle['mileage'],
                        'status' => 'available',
                    ],
                );
            }
        });
    }

    /**
     * @return array<string, array{string, string}>
     */
    private function settings(): array
    {
        return [
            'business.name' => ['general', 'RentaDrive'],
            'business.rnc' => ['general', ''],
            'business.phone' => ['general', ''],
            'business.email' => ['general', ''],
            'business.address' => ['general', 'Santo Domingo, República Dominicana'],
            'billing.currency' => ['billing', 'DOP'],
            'billing.tax_rate' => ['billing', '18'],
            'operations.default_pickup_location' => ['operations', 'Oficina principal'],
        ];
    }
}
