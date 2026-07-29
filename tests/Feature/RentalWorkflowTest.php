<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RentalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_rental_marks_vehicle_as_rented_and_creates_invoice(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);
        [$customer, $vehicle] = $this->fixtures();

        $response = $this->actingAs($administrator)->post('/rentals', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->format('Y-m-d H:i:s'),
            'expected_return_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'opening_mileage' => 100,
            'fuel_out' => 100,
            'daily_rate' => 2500,
            'deposit_amount' => 5000,
            'fees' => 0,
        ]);

        $rental = Rental::query()->firstOrFail();
        $response->assertRedirect(route('rentals.show', $rental));
        $this->assertSame('rented', $vehicle->fresh()->status);
        $this->assertSame('open', $rental->status);
        $this->assertSame(1, Invoice::query()->where('rental_id', $rental->id)->count());
        $this->assertEquals(5900.0, (float) $rental->total);
    }

    /**
     * @return array{Customer, Vehicle}
     */
    private function fixtures(): array
    {
        $customer = Customer::query()->create([
            'document_type' => 'cedula',
            'document_number' => '001-WORKFLOW',
            'first_name' => 'Luis',
            'last_name' => 'Santos',
            'phone' => '809-555-0001',
            'status' => 'active',
        ]);
        $category = VehicleCategory::query()->create([
            'code' => 'ECO',
            'name' => 'Económico',
            'daily_rate' => 2500,
            'deposit_amount' => 5000,
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'Hyundai']);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Accent',
            'year' => 2025,
        ]);
        $vehicle = Vehicle::query()->create([
            'vehicle_model_id' => $model->id,
            'vehicle_category_id' => $category->id,
            'code' => 'RD-WF',
            'plate' => 'A000002',
            'color' => 'Gris',
            'seats' => 5,
            'mileage' => 100,
            'status' => 'available',
        ]);

        return [$customer, $vehicle];
    }
}
