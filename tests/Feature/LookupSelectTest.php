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
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LookupSelectTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;

    private Company $companyB;

    private Branch $branchA;

    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        [$this->companyA, $this->branchA] = $this->tenant('lookup-a');
        [$this->companyB, $this->branchB] = $this->tenant('lookup-b');
    }

    /**
     * @return array{0: Company, 1: Branch}
     */
    private function tenant(string $slug): array
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

    private function inTenant(Company $company, Branch $branch, callable $callback): mixed
    {
        $context = app(TenantContext::class);
        $context->set($company, $branch);

        try {
            return $callback();
        } finally {
            $context->clear();
        }
    }

    private function customer(string $first, string $document, string $status = 'active'): Customer
    {
        return Customer::query()->create([
            'document_type' => 'other',
            'document_number' => $document,
            'first_name' => $first,
            'last_name' => 'Prueba',
            'phone' => '809-555-0100',
            'status' => $status,
        ]);
    }

    private function user(Company $company, Branch $branch, RoleName $role): User
    {
        $user = User::factory()->create([
            'company_id' => $company->getKey(),
            'branch_id' => $branch->getKey(),
        ]);
        $user->assignRole($role->value);

        return $user;
    }

    private function vehicle(string $code, string $plate, string $status): Vehicle
    {
        $brand = VehicleBrand::query()->firstOrCreate(['name' => 'Toyota'], ['is_active' => true]);
        $category = VehicleCategory::query()->firstOrCreate(
            ['code' => 'SED'],
            ['name' => 'Sedán', 'daily_rate' => 2500, 'deposit_amount' => 5000, 'is_active' => true],
        );
        $model = VehicleModel::query()->firstOrCreate(
            ['vehicle_brand_id' => $brand->getKey(), 'name' => 'Corolla', 'year' => 2026],
            ['is_active' => true],
        );

        return Vehicle::query()->create([
            'vehicle_model_id' => $model->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'code' => $code,
            'plate' => $plate,
            'color' => 'Blanco',
            'transmission' => 'automatic',
            'fuel_type' => 'gasoline',
            'seats' => 5,
            'mileage' => 1000,
            'status' => $status,
        ]);
    }

    public function test_customer_lookup_never_returns_records_from_another_company(): void
    {
        $this->inTenant($this->companyA, $this->branchA, fn () => $this->customer('Alberto', 'A-0001-1234'));
        $this->inTenant($this->companyB, $this->branchB, fn () => $this->customer('Belinda', 'B-0001-9999'));
        $admin = $this->user($this->companyA, $this->branchA, RoleName::ADMINISTRATOR);

        $this->actingAs($admin)->getJson('/lookups/customers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Alberto Prueba');

        $this->actingAs($admin)->getJson('/lookups/customers?q=Belinda')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($admin)->getJson('/lookups/customers?q=9999')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_customer_lookup_masks_the_document_number(): void
    {
        $this->inTenant($this->companyA, $this->branchA, fn () => $this->customer('Alberto', '00112345678'));
        $admin = $this->user($this->companyA, $this->branchA, RoleName::ADMINISTRATOR);

        $response = $this->actingAs($admin)->getJson('/lookups/customers?q=Alberto')->assertOk();

        $this->assertStringNotContainsString('00112345678', $response->getContent());
        $response->assertJsonPath('data.0.detail', '•••••••5678');
    }

    public function test_lookup_is_limited_and_reports_that_more_results_exist(): void
    {
        $this->inTenant($this->companyA, $this->branchA, function (): void {
            for ($index = 1; $index <= 17; $index++) {
                $this->customer('Cliente'.$index, 'DOC-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT));
            }
        });
        $admin = $this->user($this->companyA, $this->branchA, RoleName::ADMINISTRATOR);

        $this->actingAs($admin)->getJson('/lookups/customers')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('more', true);

        $this->actingAs($admin)->getJson('/lookups/customers?q=Cliente17')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('more', false);
    }

    public function test_inactive_customers_are_not_offered(): void
    {
        $this->inTenant($this->companyA, $this->branchA, fn () => $this->customer('Inactivo', 'DOC-INACT', 'suspended'));
        $admin = $this->user($this->companyA, $this->branchA, RoleName::ADMINISTRATOR);

        $this->actingAs($admin)->getJson('/lookups/customers?q=Inactivo')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_vehicle_lookup_depends_on_scope_and_stays_inside_the_company(): void
    {
        $this->inTenant($this->companyA, $this->branchA, function (): void {
            $this->vehicle('A-LIB', 'A100001', 'available');
            $this->vehicle('A-RENT', 'A100002', 'rented');
            $this->vehicle('A-MANT', 'A100003', 'maintenance');
        });
        $this->inTenant($this->companyB, $this->branchB, fn () => $this->vehicle('B-LIB', 'B100001', 'available'));
        $admin = $this->user($this->companyA, $this->branchA, RoleName::ADMINISTRATOR);

        $rental = $this->actingAs($admin)->getJson('/lookups/vehicles?scope=rental')->assertOk();
        $this->assertSame(1, count($rental->json('data')));

        $reservation = $this->actingAs($admin)->getJson('/lookups/vehicles?scope=reservation')->assertOk();
        $this->assertSame(2, count($reservation->json('data')));

        $this->actingAs($admin)->getJson('/lookups/vehicles?q=B100001')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($admin)->getJson('/lookups/vehicles?q=A100001')->assertOk()
            ->assertJsonPath('data.0.meta.rate', 2500.0)
            ->assertJsonPath('data.0.meta.deposit', 5000.0);
    }

    public function test_lookup_requires_the_permission_of_the_operation(): void
    {
        $manager = $this->user($this->companyA, $this->branchA, RoleName::MANAGER);
        $inspector = $this->user($this->companyA, $this->branchA, RoleName::INSPECTOR);

        $this->actingAs($manager)->getJson('/lookups/customers')->assertForbidden();
        $this->actingAs($manager)->getJson('/lookups/invoices')->assertForbidden();
        $this->actingAs($inspector)->getJson('/lookups/customers')->assertForbidden();
        $this->actingAs($inspector)->getJson('/lookups/rentals')->assertOk();
    }

    public function test_lookup_rejects_unknown_types_and_guests(): void
    {
        $this->getJson('/lookups/customers')->assertUnauthorized();

        $admin = $this->user($this->companyA, $this->branchA, RoleName::ADMINISTRATOR);

        $this->actingAs($admin)->getJson('/lookups/users')->assertNotFound();
        $this->actingAs($admin)->getJson('/lookups/customers?scope=drop')->assertUnprocessable();
    }

    public function test_a_forged_foreign_customer_id_does_not_leak_its_name_into_the_form(): void
    {
        $foreign = $this->inTenant($this->companyB, $this->branchB, fn () => $this->customer('Forastero', 'FOREIGN-1'));
        $admin = $this->user($this->companyA, $this->branchA, RoleName::ADMINISTRATOR);

        $this->actingAs($admin)
            ->get('/reservations/create?customer='.$foreign->getKey())
            ->assertOk()
            ->assertSee('role="combobox"', false)
            ->assertDontSee('Forastero');
    }

    public function test_forms_render_the_searchable_selector_instead_of_every_record(): void
    {
        $this->inTenant($this->companyA, $this->branchA, fn () => $this->customer('Visible', 'DOC-VIS'));
        $admin = $this->user($this->companyA, $this->branchA, RoleName::ADMINISTRATOR);

        foreach (['/reservations/create', '/rentals/create', '/inspections/create'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk()->assertSee('role="combobox"', false)->assertDontSee('Visible Prueba');
        }
    }
}
