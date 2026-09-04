<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Operations\Services\ReservationAvailabilityService;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReservationAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_reservation_blocks_the_same_vehicle(): void
    {
        $this->actingAs(User::factory()->create());
        [$customer, $vehicle, $category] = $this->fixtures();
        $service = app(ReservationAvailabilityService::class);

        Reservation::query()->create([
            'code' => 'RES-TEST-001',
            'customer_id' => $customer->id,
            'vehicle_category_id' => $category->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'pickup_location' => 'Oficina',
            'return_location' => 'Oficina',
            'daily_rate' => 2500,
            'estimated_total' => 5000,
            'status' => 'confirmed',
        ]);

        $this->assertFalse($service->isVehicleAvailable(
            $vehicle,
            now()->addDays(2),
            now()->addDays(4),
        ));

        $this->assertTrue($service->isVehicleAvailable(
            $vehicle,
            now()->addDays(4),
            now()->addDays(5),
        ));
    }

    /**
     * @return array{Customer, Vehicle, VehicleCategory}
     */
    private function fixtures(): array
    {
        $customer = Customer::query()->create([
            'document_type' => 'cedula',
            'document_number' => '001-TEST',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'phone' => '809-555-0000',
            'status' => 'active',
        ]);
        $category = VehicleCategory::query()->create([
            'code' => 'SED',
            'name' => 'Sedán',
            'daily_rate' => 2500,
            'deposit_amount' => 5000,
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'Toyota']);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Corolla',
            'year' => 2025,
        ]);
        $vehicle = Vehicle::query()->create([
            'vehicle_model_id' => $model->id,
            'vehicle_category_id' => $category->id,
            'code' => 'RD-TEST',
            'plate' => 'A000001',
            'color' => 'Azul',
            'seats' => 5,
            'mileage' => 100,
            'status' => 'available',
        ]);

        return [$customer, $vehicle, $category];
    }
}
