<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FleetResponsiveViewTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private VehicleModel $model;

    private VehicleCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
                $this->seed(RolePermissionSeeder::class);

        $this->company = Company::query()->create([
            'name' => 'Rent a Car VISTA',
            'slug' => 'vista',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
        ]);
        $branch = Branch::query()->create([
            'company_id' => $this->company->getKey(),
            'name' => 'Sucursal Principal',
            'code' => 'PRINCIPAL',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $context = app(TenantContext::class);
        $context->set($this->company, $branch);
        $brand = VehicleBrand::query()->create(['name' => 'Toyota', 'is_active' => true]);
        $this->category = VehicleCategory::query()->create(['code' => 'SED', 'name' => 'Sedán', 'daily_rate' => 2500, 'deposit_amount' => 5000, 'is_active' => true]);
        $this->model = VehicleModel::query()->create(['vehicle_brand_id' => $brand->getKey(), 'name' => 'Corolla', 'year' => 2026, 'is_active' => true]);
        $context->clear();
    }

    private function userWithRole(RoleName $role): User
    {
        $user = User::factory()->create([
            'company_id' => $this->company->getKey(),
            'branch_id' => $this->company->branches()->value('id'),
        ]);
        $user->assignRole($role->value);

        return $user;
    }

    private function createVehicle(): void
    {
        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->post('/vehicles', [
                'vehicle_model_id' => $this->model->getKey(),
                'vehicle_category_id' => $this->category->getKey(),
                'code' => 'VISTA-001',
                'plate' => 'A200001',
                'color' => 'Blanco',
                'transmission' => 'automatic',
                'fuel_type' => 'gasoline',
                'seats' => 5,
                'mileage' => 1000,
                'status' => 'available',
            ])
            ->assertRedirect();
    }

    public function test_default_view_renders_cards_for_small_screens_and_table_from_md(): void
    {
        $this->createVehicle();

        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->get('/vehicles')
            ->assertOk()
            ->assertSee('md:hidden', false)
            ->assertSee('hidden md:block', false)
            ->assertSee('<table', false);
    }

    public function test_explicit_table_view_does_not_render_cards(): void
    {
        $this->createVehicle();

        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->get('/vehicles?view=table')
            ->assertOk()
            ->assertSee('<table', false)
            ->assertDontSee('hidden md:block', false)
            ->assertDontSee('Tarifa diaria</dt>', false);
    }

    public function test_explicit_card_view_does_not_render_the_table(): void
    {
        $this->createVehicle();

        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->get('/vehicles?view=cards')
            ->assertOk()
            ->assertSee('Tarifa diaria</dt>', false)
            ->assertDontSee('<table', false);
    }
}
