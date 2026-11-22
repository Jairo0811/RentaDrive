<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
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
use App\Models\VehicleModel;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Agente e Inspector solo ven su sucursal; Administrador y Gerente ven toda la empresa. */
final class BranchVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Branch $north;

    private Branch $south;

    /** @var array<string, array<string, int>> */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->company = Company::query()->create(['name' => 'Sucursales SRL', 'slug' => 'sucursales', 'currency' => 'DOP', 'timezone' => 'America/Santo_Domingo', 'status' => 'active']);
        $this->north = Branch::query()->create(['company_id' => $this->company->getKey(), 'name' => 'Norte', 'code' => 'NOR', 'is_primary' => true, 'is_active' => true]);
        $this->south = Branch::query()->create(['company_id' => $this->company->getKey(), 'name' => 'Sur', 'code' => 'SUR', 'is_primary' => false, 'is_active' => true]);

        app(TenantContext::class)->set($this->company, $this->north);
        $customer = Customer::query()->create(['document_type' => 'passport', 'document_number' => 'SB-1', 'first_name' => 'Ana', 'last_name' => 'Sucursal', 'phone' => '8095550004', 'status' => 'active']);
        $category = VehicleCategory::query()->create(['code' => 'SB', 'name' => 'Cat', 'daily_rate' => 1000, 'deposit_amount' => 0]);
        $brand = VehicleBrand::query()->create(['name' => 'MarcaSb']);
        $model = VehicleModel::query()->create(['vehicle_brand_id' => $brand->getKey(), 'name' => 'ModeloSb', 'year' => 2025]);

        foreach (['north' => $this->north, 'south' => $this->south] as $key => $branch) {
            app(TenantContext::class)->set($this->company, $branch);
            $n = strtoupper($key);
            $vehicle = Vehicle::query()->create(['vehicle_model_id' => $model->getKey(), 'vehicle_category_id' => $category->getKey(), 'code' => 'V-'.$n, 'plate' => 'PL'.$n, 'color' => 'Gris', 'mileage' => 1, 'status' => 'rented']);
            $reservation = Reservation::query()->create([
                'company_id' => $this->company->getKey(), 'branch_id' => $branch->getKey(), 'code' => 'RES-'.$n, 'customer_id' => $customer->getKey(),
                'vehicle_category_id' => $category->getKey(), 'start_at' => now()->addDays(3), 'end_at' => now()->addDays(5), 'daily_rate' => 1000,
                'estimated_total' => 2000, 'deposit_amount' => 0, 'status' => 'pending', 'source' => 'internal',
            ]);
            $rental = Rental::query()->create([
                'code' => 'ALQ-'.$n, 'customer_id' => $customer->getKey(), 'vehicle_id' => $vehicle->getKey(), 'start_at' => now(), 'expected_return_at' => now()->addDay(),
                'opening_mileage' => 1, 'daily_rate' => 1000, 'deposit_amount' => 0, 'subtotal' => 1000, 'fees' => 0, 'taxes' => 180, 'total' => 1180, 'status' => 'open',
            ]);
            $invoice = Invoice::query()->create(['number' => 'FAC-'.$n, 'rental_id' => $rental->getKey(), 'customer_id' => $customer->getKey(), 'issued_at' => now(), 'subtotal' => 1000, 'tax' => 180, 'discount' => 0, 'total' => 1180, 'paid_amount' => 100, 'balance' => 1080, 'status' => 'partial']);
            $payment = Payment::query()->create(['receipt_number' => 'REC-'.$n, 'invoice_id' => $invoice->getKey(), 'paid_at' => now(), 'method' => 'cash', 'amount' => 100]);
            $inspection = Inspection::query()->create(['rental_id' => $rental->getKey(), 'vehicle_id' => $vehicle->getKey(), 'type' => 'delivery', 'inspected_at' => now(), 'mileage' => 1, 'fuel_level' => 100, 'body_condition' => 'good', 'interior_condition' => 'good', 'tires_condition' => 'good']);

            $this->ids[$key] = ['vehicle' => $vehicle->getKey(), 'reservation' => $reservation->getKey(), 'rental' => $rental->getKey(), 'invoice' => $invoice->getKey(), 'payment' => $payment->getKey(), 'inspection' => $inspection->getKey()];
        }
        app(TenantContext::class)->clear();
    }

    private function userIn(Branch $branch, RoleName $role): User
    {
        $user = User::factory()->create(['company_id' => $this->company->getKey(), 'branch_id' => $branch->getKey()]);
        $user->assignRole($role->value);

        return $user;
    }

    /** @return array<string, array{string, string, string}> */
    public static function listings(): array
    {
        return [
            'vehículos' => ['vehicles.index', 'PLNORTH', 'PLSOUTH'],
            'reservas' => ['reservations.index', 'RES-NORTH', 'RES-SOUTH'],
            'alquileres' => ['rentals.index', 'ALQ-NORTH', 'ALQ-SOUTH'],
            'facturas' => ['invoices.index', 'FAC-NORTH', 'FAC-SOUTH'],
            'pagos' => ['payments.index', 'REC-NORTH', 'REC-SOUTH'],
        ];
    }

    #[DataProvider('listings')]
    public function test_rental_agent_only_sees_own_branch_in_listings(string $route, string $own, string $other): void
    {
        $html = $this->actingAs($this->userIn($this->north, RoleName::RENTAL_AGENT))->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString($own, $html);
        $this->assertStringNotContainsString($other, $html);
    }

    #[DataProvider('listings')]
    public function test_administrator_and_manager_see_every_branch(string $route, string $own, string $other): void
    {
        foreach ([RoleName::ADMINISTRATOR, RoleName::MANAGER] as $role) {
            $html = $this->actingAs($this->userIn($this->north, $role))->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString($own, $html);
            $this->assertStringContainsString($other, $html, "{$role->value} debe ver ambas sucursales en {$route}");
        }
    }

    public function test_agent_gets_404_when_opening_records_of_another_branch(): void
    {
        $agent = $this->actingAs($this->userIn($this->north, RoleName::RENTAL_AGENT));
        $south = $this->ids['south'];

        $agent->get(route('vehicles.show', $south['vehicle']))->assertNotFound();
        $agent->get(route('reservations.show', $south['reservation']))->assertNotFound();
        $agent->get(route('rentals.show', $south['rental']))->assertNotFound();
        $agent->get(route('rentals.contract', $south['rental']))->assertNotFound();
        $agent->get(route('invoices.show', $south['invoice']))->assertNotFound();

        $agent->get(route('rentals.show', $this->ids['north']['rental']))->assertOk();
    }

    public function test_inspector_only_sees_own_branch_inspections_and_vehicles(): void
    {
        $inspector = $this->actingAs($this->userIn($this->south, RoleName::INSPECTOR));

        $html = $inspector->get(route('inspections.index'))->assertOk()->getContent();
        $this->assertStringContainsString('ALQ-SOUTH', $html);
        $this->assertStringNotContainsString('ALQ-NORTH', $html);

        $inspector->get(route('inspections.show', $this->ids['north']['inspection']))->assertNotFound();
        $inspector->get(route('vehicles.show', $this->ids['north']['vehicle']))->assertNotFound();
    }

    public function test_lookup_selectors_respect_the_branch_for_restricted_roles_only(): void
    {
        $agent = $this->userIn($this->north, RoleName::RENTAL_AGENT);
        $content = $this->actingAs($agent)->getJson(route('lookups.search', ['type' => 'vehicles', 'q' => 'V-']))->getContent();
        $this->assertStringContainsString('V-NORTH', (string) $content);
        $this->assertStringNotContainsString('V-SOUTH', (string) $content);

        $admin = $this->userIn($this->north, RoleName::ADMINISTRATOR);
        $content = $this->actingAs($admin)->getJson(route('lookups.search', ['type' => 'vehicles', 'q' => 'V-']))->getContent();
        $this->assertStringContainsString('V-SOUTH', (string) $content);
    }

    public function test_users_without_a_branch_are_not_restricted(): void
    {
        $this->assertNull($this->userIn($this->north, RoleName::RENTAL_AGENT)->fill(['branch_id' => null])->branchRestriction());
    }

    public function test_availability_still_counts_records_of_every_branch(): void
    {
        // El scope de visibilidad no es global: los servicios (disponibilidad, cierres) siguen viendo toda la empresa.
        $this->actingAs($this->userIn($this->north, RoleName::RENTAL_AGENT));
        app(TenantContext::class)->set($this->company, $this->north);

        $this->assertSame(2, Rental::query()->count());
        $this->assertSame(1, Rental::query()->visibleToBranch()->count());
    }
}
