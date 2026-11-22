<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\BookingOption;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleMaintenance;
use App\Models\VehicleModel;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantModelRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Matriz de pruebas negativas entre empresas: el administrador de la empresa B no puede ver, editar, borrar,
 * descargar ni accionar NINGÚN recurso de la empresa A, y un invitado no puede tocar ninguno.
 */
final class CrossTenantAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, int> */
    private array $ids = [];

    private User $adminA;

    private User $adminB;

    private Company $companyA;

    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        [$this->companyA, $branchA] = $this->tenant('matriz-a');
        [$this->companyB, $branchB] = $this->tenant('matriz-b');

        $this->adminA = $this->admin($this->companyA, $branchA, 'Admin Alfa');
        $this->adminB = $this->admin($this->companyB, $branchB, 'Admin Beta');

        $this->fixturesForCompanyA($branchA);
    }

    /** @return array{Company, Branch} */
    private function tenant(string $slug): array
    {
        $company = Company::query()->create([
            'name' => 'Empresa '.$slug, 'slug' => $slug, 'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo', 'status' => 'active',
        ]);
        $branch = Branch::query()->create([
            'company_id' => $company->getKey(), 'name' => 'Principal', 'code' => strtoupper($slug),
            'is_primary' => true, 'is_active' => true,
        ]);

        return [$company, $branch];
    }

    private function admin(Company $company, Branch $branch, string $name): User
    {
        $user = User::factory()->create([
            'company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'name' => $name,
        ]);
        $user->assignRole(RoleName::ADMINISTRATOR->value);

        return $user;
    }

    private function fixturesForCompanyA(Branch $branch): void
    {
        $context = app(TenantContext::class);
        $context->set($this->companyA, $branch);

        $customer = Customer::query()->create([
            'document_type' => 'passport', 'document_number' => 'MX-A-1', 'first_name' => 'Cliente',
            'last_name' => 'ZetaAlfa', 'phone' => '8095550001', 'status' => 'active',
        ]);
        $category = VehicleCategory::query()->create(['code' => 'MX-A', 'name' => 'Categoría Alfa', 'daily_rate' => 1000, 'deposit_amount' => 0]);
        $brand = VehicleBrand::query()->create(['name' => 'MarcaAlfa']);
        $model = VehicleModel::query()->create(['vehicle_brand_id' => $brand->getKey(), 'name' => 'ModeloAlfa', 'year' => 2025]);
        $vehicle = Vehicle::query()->create([
            'vehicle_model_id' => $model->getKey(), 'vehicle_category_id' => $category->getKey(),
            'code' => 'MX-CAR-A', 'plate' => 'ZZA9999', 'color' => 'Gris', 'mileage' => 10, 'status' => 'rented',
        ]);
        $reservation = Reservation::query()->create([
            'company_id' => $this->companyA->getKey(), 'branch_id' => $branch->getKey(), 'code' => 'RES-MX-A',
            'customer_id' => $customer->getKey(), 'vehicle_category_id' => $category->getKey(),
            'start_at' => now()->addDays(3), 'end_at' => now()->addDays(5), 'daily_rate' => 1000,
            'estimated_total' => 2000, 'deposit_amount' => 0, 'status' => 'pending', 'source' => 'public',
            'public_token' => 'token-publico-empresa-alfa-0123456789abcdef0123456789',
        ]);
        $rental = Rental::query()->create([
            'code' => 'RENT-MX-A', 'customer_id' => $customer->getKey(), 'vehicle_id' => $vehicle->getKey(),
            'start_at' => now()->subDay(), 'expected_return_at' => now()->addDay(), 'opening_mileage' => 10,
            'daily_rate' => 1000, 'deposit_amount' => 0, 'subtotal' => 1000, 'fees' => 0, 'taxes' => 180,
            'total' => 1180, 'status' => 'open',
        ]);
        $invoice = Invoice::query()->create([
            'number' => 'FAC-MX-A', 'rental_id' => $rental->getKey(), 'customer_id' => $customer->getKey(),
            'issued_at' => now(), 'due_at' => now()->addDays(3), 'subtotal' => 1000, 'tax' => 180, 'discount' => 0,
            'total' => 1180, 'paid_amount' => 100, 'balance' => 1080, 'status' => 'partial',
        ]);
        $payment = Payment::query()->create([
            'receipt_number' => 'REC-MX-A', 'invoice_id' => $invoice->getKey(), 'paid_at' => now(),
            'method' => 'cash', 'amount' => 100,
        ]);
        $inspection = Inspection::query()->create([
            'rental_id' => $rental->getKey(), 'vehicle_id' => $vehicle->getKey(), 'type' => 'delivery',
            'inspected_at' => now(), 'mileage' => 10, 'fuel_level' => 100, 'body_condition' => 'good',
            'interior_condition' => 'good', 'tires_condition' => 'good', 'photos' => ['rentadrive-evidence/x.png'],
            'photo_disk' => 'local',
        ]);
        $maintenance = VehicleMaintenance::query()->create([
            'vehicle_id' => $vehicle->getKey(), 'maintenance_type' => 'Cambio de aceite',
            'scheduled_at' => now()->addDay(), 'cost' => 100, 'status' => 'scheduled',
        ]);
        $option = BookingOption::query()->create([
            'company_id' => $this->companyA->getKey(), 'code' => 'OPT-A', 'name' => 'Opcion Alfa Secreta', 'type' => 'extra',
            'pricing_mode' => 'flat', 'price' => 10, 'is_active' => true,
        ]);
        $employee = User::factory()->create([
            'company_id' => $this->companyA->getKey(), 'branch_id' => $branch->getKey(), 'name' => 'Usuario Alfa Secreto',
        ]);
        $employee->assignRole(RoleName::RENTAL_AGENT->value);

        $context->clear();

        $this->ids = [
            'customer' => $customer->getKey(), 'vehicle' => $vehicle->getKey(), 'reservation' => $reservation->getKey(),
            'rental' => $rental->getKey(), 'invoice' => $invoice->getKey(), 'payment' => $payment->getKey(),
            'inspection' => $inspection->getKey(), 'maintenance' => $maintenance->getKey(),
            'brand' => $brand->getKey(), 'category' => $category->getKey(), 'model' => $model->getKey(),
            'option' => $option->getKey(), 'user' => $employee->getKey(),
        ];
    }

    /**
     * @return array<string, array{string, string, array<string, string>}>
     */
    public static function tenantRoutes(): array
    {
        $routes = [
            ['GET', 'customers.show', ['customer' => 'customer']],
            ['GET', 'customers.edit', ['customer' => 'customer']],
            ['PUT', 'customers.update', ['customer' => 'customer']],
            ['DELETE', 'customers.destroy', ['customer' => 'customer']],
            ['GET', 'vehicles.show', ['vehicle' => 'vehicle']],
            ['GET', 'vehicles.edit', ['vehicle' => 'vehicle']],
            ['PUT', 'vehicles.update', ['vehicle' => 'vehicle']],
            ['DELETE', 'vehicles.destroy', ['vehicle' => 'vehicle']],
            ['GET', 'reservations.show', ['reservation' => 'reservation']],
            ['GET', 'reservations.edit', ['reservation' => 'reservation']],
            ['PUT', 'reservations.update', ['reservation' => 'reservation']],
            ['DELETE', 'reservations.destroy', ['reservation' => 'reservation']],
            ['GET', 'rentals.show', ['rental' => 'rental']],
            ['GET', 'rentals.contract', ['rental' => 'rental']],
            ['GET', 'rentals.contract.download', ['rental' => 'rental']],
            ['POST', 'rentals.acceptance', ['rental' => 'rental']],
            ['PATCH', 'rentals.close', ['rental' => 'rental']],
            ['GET', 'inspections.show', ['inspection' => 'inspection']],
            ['DELETE', 'inspections.destroy', ['inspection' => 'inspection']],
            ['GET', 'inspections.photo', ['inspection' => 'inspection', 'index' => '#0']],
            ['GET', 'invoices.show', ['invoice' => 'invoice']],
            ['GET', 'invoices.download', ['invoice' => 'invoice']],
            ['PUT', 'invoices.update', ['invoice' => 'invoice']],
            ['POST', 'invoices.fiscal-draft', ['invoice' => 'invoice']],
            ['DELETE', 'payments.destroy', ['payment' => 'payment']],
            ['PUT', 'maintenances.update', ['maintenance' => 'maintenance']],
            ['DELETE', 'maintenances.destroy', ['maintenance' => 'maintenance']],
            ['PUT', 'fleet.brands.update', ['brand' => 'brand']],
            ['DELETE', 'fleet.brands.destroy', ['brand' => 'brand']],
            ['PUT', 'fleet.categories.update', ['category' => 'category']],
            ['DELETE', 'fleet.categories.destroy', ['category' => 'category']],
            ['PUT', 'fleet.models.update', ['model' => 'model']],
            ['DELETE', 'fleet.models.destroy', ['model' => 'model']],
            ['PUT', 'booking-options.update', ['booking_option' => 'option']],
            ['DELETE', 'booking-options.destroy', ['booking_option' => 'option']],
            ['GET', 'users.edit', ['user' => 'user']],
            ['PUT', 'users.update', ['user' => 'user']],
            ['DELETE', 'users.destroy', ['user' => 'user']],
        ];

        $cases = [];
        foreach ($routes as [$method, $name, $params]) {
            $cases[$method.' '.$name] = [$method, $name, $params];
        }

        return $cases;
    }

    /** @param array<string, string> $params */
    private function url(string $name, array $params): string
    {
        $resolved = [];
        foreach ($params as $parameter => $key) {
            $resolved[$parameter] = str_starts_with($key, '#') ? (int) substr($key, 1) : $this->ids[$key];
        }

        return route($name, $resolved);
    }

    /** @param array<string, string> $params */
    #[DataProvider('tenantRoutes')]
    public function test_administrator_of_another_company_cannot_reach_the_resource(string $method, string $name, array $params): void
    {
        $response = $this->actingAs($this->adminB)->call($method, $this->url($name, $params));

        $this->assertContains($response->getStatusCode(), [403, 404], "{$method} {$name} respondió {$response->getStatusCode()} a otra empresa.");
        $this->assertSame(1, Customer::withoutGlobalScopes()->whereKey($this->ids['customer'])->count());
        $this->assertSame(1, Payment::withoutGlobalScopes()->whereKey($this->ids['payment'])->whereNull('voided_at')->count());
    }

    /** @param array<string, string> $params */
    #[DataProvider('tenantRoutes')]
    public function test_guests_are_sent_to_login_for_every_tenant_route(string $method, string $name, array $params): void
    {
        $this->call($method, $this->url($name, $params))->assertRedirect(route('login'));
    }

    /** @param array<string, string> $params */
    #[DataProvider('tenantRoutes')]
    public function test_platform_administrator_cannot_operate_inside_a_tenant(string $method, string $name, array $params): void
    {
        $platform = User::factory()->create(['company_id' => null, 'branch_id' => null, 'is_platform_admin' => true]);

        $response = $this->actingAs($platform)->call($method, $this->url($name, $params));

        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    public function test_listings_never_leak_records_of_another_company(): void
    {
        $this->actingAs($this->adminB);

        foreach (['customers.index', 'vehicles.index', 'reservations.index', 'rentals.index', 'invoices.index', 'payments.index', 'users.index', 'booking-options.index', 'audit.index', 'inspections.index'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            foreach (['ZetaAlfa', 'ZZA9999', 'RES-MX-A', 'RENT-MX-A', 'FAC-MX-A', 'Usuario Alfa Secreto', 'Opcion Alfa Secreta'] as $secret) {
                $this->assertStringNotContainsString($secret, $html, "{$route} filtra «{$secret}» de otra empresa.");
            }
        }
    }

    public function test_lookup_endpoints_never_return_records_of_another_company(): void
    {
        foreach (['customers', 'vehicles'] as $type) {
            $content = $this->actingAs($this->adminB)->getJson(route('lookups.search', ['type' => $type, 'q' => 'Alfa']))->getContent();
            $this->assertStringNotContainsString('ZetaAlfa', (string) $content);
            $this->assertStringNotContainsString('ZZA9999', (string) $content);
        }
    }

    public function test_public_reservation_token_only_resolves_under_its_own_company(): void
    {
        $token = 'token-publico-empresa-alfa-0123456789abcdef0123456789';

        $this->get(route('public-booking.confirmation', ['company' => $this->companyA->slug, 'token' => $token]))->assertOk();
        $this->get(route('public-booking.confirmation', ['company' => $this->companyB->slug, 'token' => $token]))->assertNotFound();
        $this->post(route('public-booking.cancel', ['company' => $this->companyB->slug, 'token' => $token]))->assertNotFound();
        $this->post(route('public-booking.deposit.store', ['company' => $this->companyB->slug, 'token' => $token]))->assertNotFound();
    }

    public function test_company_with_suspended_status_cannot_operate(): void
    {
        $this->companyB->update(['status' => 'suspended']);

        $this->actingAs($this->adminB)->get(route('customers.index'))->assertForbidden();
        $this->actingAs($this->adminB)->get(route('dashboard'))->assertForbidden();
    }

    public function test_every_table_with_company_id_is_registered_as_tenant_scoped_or_explicitly_exempt(): void
    {
        $registered = array_map(static fn (string $class): string => (new $class)->getTable(), TenantModelRegistry::models());

        // Tablas con company_id que NO usan TenantScope: se filtran de forma explícita y están cubiertas por pruebas propias.
        $exempt = ['users', 'branches', 'companies', 'company_lifecycle_events'];

        $unprotected = [];
        foreach (Schema::getTables() as $table) {
            $name = $table['name'];

            if (Schema::hasColumn($name, 'company_id') && ! in_array($name, [...$registered, ...$exempt], true)) {
                $unprotected[] = $name;
            }
        }

        $this->assertSame([], $unprotected, 'Registra estos modelos en TenantModelRegistry (o justifica la excepción): '.implode(', ', $unprotected));
    }
}
