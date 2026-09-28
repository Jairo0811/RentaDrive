<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FleetPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_administrator_can_assign_a_vehicle_to_an_active_branch(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);
        $this->actingAs($administrator);

        $category = VehicleCategory::query()->create([
            'code' => 'SUV',
            'name' => 'SUV',
            'daily_rate' => 4200,
            'deposit_amount' => 9000,
            'is_active' => true,
        ]);

        $brand = VehicleBrand::query()->create([
            'name' => 'Hyundai',
            'is_active' => true,
        ]);

        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Tucson',
            'year' => 2026,
            'is_active' => true,
        ]);

        $response = $this->post('/vehicles', [
            'branch_id' => $administrator->branch_id,
            'vehicle_model_id' => $model->id,
            'vehicle_category_id' => $category->id,
            'code' => 'RD-SUV-001',
            'plate' => 'G123456',
            'color' => 'Negro',
            'transmission' => 'automatic',
            'fuel_type' => 'gasoline',
            'seats' => 5,
            'mileage' => 1500,
            'status' => 'available',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('vehicles', [
            'company_id' => $administrator->company_id,
            'branch_id' => $administrator->branch_id,
            'code' => 'RD-SUV-001',
            'plate' => 'G123456',
        ]);
    }

    public function test_fleet_timeline_shows_vehicle_for_the_selected_branch(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);
        $this->actingAs($administrator);

        $category = VehicleCategory::query()->create([
            'code' => 'ECO',
            'name' => 'Económico',
            'daily_rate' => 2200,
            'deposit_amount' => 4000,
            'is_active' => true,
        ]);

        $brand = VehicleBrand::query()->create([
            'name' => 'Kia',
            'is_active' => true,
        ]);

        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Picanto',
            'year' => 2026,
            'is_active' => true,
        ]);

        $vehicle = \App\Models\Vehicle::query()->create([
            'branch_id' => $administrator->branch_id,
            'vehicle_model_id' => $model->id,
            'vehicle_category_id' => $category->id,
            'code' => 'RD-ECO-001',
            'plate' => 'A123456',
            'color' => 'Blanco',
            'transmission' => 'automatic',
            'fuel_type' => 'gasoline',
            'seats' => 5,
            'mileage' => 500,
            'status' => 'available',
        ]);

        $this->get('/fleet/schedule?branch='.$administrator->branch_id)
            ->assertOk()
            ->assertSee('Timeline operativo')
            ->assertSee($vehicle->plate)
            ->assertSee('Disponible');
    }
}
