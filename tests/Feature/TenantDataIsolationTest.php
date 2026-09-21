<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantScope;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_business_identifier_can_exist_in_different_companies_but_queries_are_isolated(): void
    {
        [$companyA, $branchA] = $this->createTenant('alfa');
        [$companyB, $branchB] = $this->createTenant('beta');
        $context = app(TenantContext::class);

        $context->set($companyA, $branchA);
        $customerA = $this->createCustomer('DOC-SHARED');

        $context->set($companyB, $branchB);
        $customerB = $this->createCustomer('DOC-SHARED');

        $this->assertNotSame($customerA->getKey(), $customerB->getKey());
        $this->assertSame(1, Customer::query()->where('document_number', 'DOC-SHARED')->count());
        $this->assertSame($customerB->getKey(), Customer::query()->where('document_number', 'DOC-SHARED')->firstOrFail()->getKey());

        $context->set($companyA, $branchA);
        $this->assertSame(1, Customer::query()->where('document_number', 'DOC-SHARED')->count());
        $this->assertSame($customerA->getKey(), Customer::query()->where('document_number', 'DOC-SHARED')->firstOrFail()->getKey());
        $this->assertSame(
            2,
            Customer::withoutGlobalScope(TenantScope::class)
                ->where('document_number', 'DOC-SHARED')
                ->count(),
        );

        $context->clear();
    }

    public function test_route_model_binding_hides_customer_from_another_company(): void
    {
        $this->seed(RolePermissionSeeder::class);
        [$companyA, $branchA] = $this->createTenant('binding-a');
        [$companyB, $branchB] = $this->createTenant('binding-b');
        $context = app(TenantContext::class);

        $context->set($companyB, $branchB);
        $foreignCustomer = $this->createCustomer('FOREIGN-CUSTOMER');
        $context->clear();

        $administrator = User::factory()->create([
            'company_id' => $companyA->getKey(),
            'branch_id' => $branchA->getKey(),
        ]);
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->get(route('customers.show', $foreignCustomer->getKey()))
            ->assertNotFound();
    }

    public function test_reservation_validation_rejects_customer_from_another_company(): void
    {
        $this->seed(RolePermissionSeeder::class);
        [$companyA, $branchA] = $this->createTenant('validation-a');
        [$companyB, $branchB] = $this->createTenant('validation-b');
        $context = app(TenantContext::class);

        $context->set($companyA, $branchA);
        $category = VehicleCategory::query()->create([
            'code' => 'SUV',
            'name' => 'SUV',
            'daily_rate' => 4000,
            'deposit_amount' => 10000,
            'is_active' => true,
        ]);

        $context->set($companyB, $branchB);
        $foreignCustomer = $this->createCustomer('FOREIGN-VALIDATION');
        $context->clear();

        $administrator = User::factory()->create([
            'company_id' => $companyA->getKey(),
            'branch_id' => $branchA->getKey(),
        ]);
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->post(route('reservations.store'), [
                'customer_id' => $foreignCustomer->getKey(),
                'vehicle_category_id' => $category->getKey(),
                'vehicle_id' => null,
                'start_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'end_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'pickup_location' => 'Sucursal Principal',
                'return_location' => 'Sucursal Principal',
                'daily_rate' => 4000,
                'status' => 'confirmed',
            ])
            ->assertSessionHasErrors('customer_id');
    }

    public function test_operational_records_inherit_company_and_branch_from_tenant_context(): void
    {
        [$company, $branch] = $this->createTenant('ownership');
        $context = app(TenantContext::class);
        $context->set($company, $branch);

        $brand = VehicleBrand::query()->create([
            'name' => 'Toyota',
            'is_active' => true,
        ]);
        $category = VehicleCategory::query()->create([
            'code' => 'SED',
            'name' => 'Sedán',
            'daily_rate' => 2500,
            'deposit_amount' => 5000,
            'is_active' => true,
        ]);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->getKey(),
            'name' => 'Corolla',
            'year' => 2026,
            'is_active' => true,
        ]);
        $vehicle = Vehicle::query()->create([
            'vehicle_model_id' => $model->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'code' => 'OWN-001',
            'plate' => 'A999999',
            'color' => 'Blanco',
            'transmission' => 'automatic',
            'fuel_type' => 'gasoline',
            'seats' => 5,
            'mileage' => 0,
            'status' => 'available',
        ]);

        $this->assertSame($company->getKey(), (int) $brand->company_id);
        $this->assertSame($company->getKey(), (int) $category->company_id);
        $this->assertSame($company->getKey(), (int) $model->company_id);
        $this->assertSame($company->getKey(), (int) $vehicle->company_id);
        $this->assertSame($branch->getKey(), (int) $vehicle->branch_id);

        $context->clear();
    }

    /**
     * @return array{Company, Branch}
     */
    private function createTenant(string $slug): array
    {
        $company = Company::query()->create([
            'name' => 'Rent a Car '.strtoupper($slug),
            'slug' => $slug,
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
        ]);

        $branch = Branch::query()->create([
            'company_id' => $company->getKey(),
            'name' => 'Sucursal Principal',
            'code' => 'PRINCIPAL',
            'is_primary' => true,
            'is_active' => true,
        ]);

        return [$company, $branch];
    }

    private function createCustomer(string $documentNumber): Customer
    {
        return Customer::query()->create([
            'document_type' => 'other',
            'document_number' => $documentNumber,
            'first_name' => 'Cliente',
            'last_name' => 'Tenant',
            'phone' => '809-555-0100',
            'status' => 'active',
        ]);
    }
}
