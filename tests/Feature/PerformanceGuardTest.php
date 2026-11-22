<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guardas de rendimiento con datos 100 % ficticios (base de pruebas, nunca la real):
 * 1) detectan N+1: el número de consultas de cada listado no puede crecer al pasar de 2 a cientos de filas;
 * 2) fijan un techo de tiempo generoso por pantalla con un volumen realista de una empresa mediana.
 */
final class PerformanceGuardTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTES = [
        'dashboard', 'customers.index', 'vehicles.index', 'reservations.index', 'rentals.index',
        'invoices.index', 'payments.index', 'reports.index', 'inspections.index', 'audit.index',
    ];

    private const SCALE = 250;

    private User $admin;

    private Company $company;

    private Branch $branch;

    private VehicleCategory $category;

    private VehicleModel $model;

    private int $serial = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->company = Company::query()->create(['name' => 'Rendimiento SRL', 'slug' => 'rendimiento', 'currency' => 'DOP', 'timezone' => 'America/Santo_Domingo', 'status' => 'active']);
        $this->branch = Branch::query()->create(['company_id' => $this->company->getKey(), 'name' => 'Principal', 'code' => 'PRF', 'is_primary' => true, 'is_active' => true]);
        foreach (['Norte', 'Sur', 'Este'] as $i => $name) {
            Branch::query()->create(['company_id' => $this->company->getKey(), 'name' => $name, 'code' => 'PR'.$i, 'is_primary' => false, 'is_active' => true]);
        }
        $this->admin = User::factory()->create(['company_id' => $this->company->getKey(), 'branch_id' => $this->branch->getKey()]);
        $this->admin->assignRole(RoleName::ADMINISTRATOR->value);

        app(TenantContext::class)->set($this->company, $this->branch);
        $this->category = VehicleCategory::query()->create(['code' => 'PRF', 'name' => 'Económico', 'daily_rate' => 1500, 'deposit_amount' => 0]);
        $brand = VehicleBrand::query()->create(['name' => 'MarcaPrf']);
        $this->model = VehicleModel::query()->create(['vehicle_brand_id' => $brand->getKey(), 'name' => 'ModeloPrf', 'year' => 2025]);
    }

    private function seedRows(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $n = ++$this->serial;
            $customer = Customer::query()->create(['document_type' => 'passport', 'document_number' => 'PF'.$n, 'first_name' => 'Cliente'.$n, 'last_name' => 'Ficticio', 'phone' => '809555'.str_pad((string) ($n % 10000), 4, '0', STR_PAD_LEFT), 'status' => 'active']);
            $vehicle = Vehicle::query()->create(['vehicle_model_id' => $this->model->getKey(), 'vehicle_category_id' => $this->category->getKey(), 'code' => 'PV'.$n, 'plate' => 'PF'.str_pad((string) $n, 5, '0', STR_PAD_LEFT), 'color' => 'Gris', 'mileage' => $n, 'status' => 'available']);
            Reservation::query()->create([
                'company_id' => $this->company->getKey(), 'branch_id' => $this->branch->getKey(), 'code' => 'RES-PF-'.$n,
                'customer_id' => $customer->getKey(), 'vehicle_category_id' => $this->category->getKey(),
                'start_at' => now()->addDays($n % 30 + 1), 'end_at' => now()->addDays($n % 30 + 3), 'daily_rate' => 1500,
                'estimated_total' => 3000, 'deposit_amount' => 0, 'status' => 'pending', 'source' => 'internal',
            ]);
            $rental = Rental::query()->create([
                'code' => 'ALQ-PF-'.$n, 'customer_id' => $customer->getKey(), 'vehicle_id' => $vehicle->getKey(),
                'start_at' => now()->subDays($n % 20 + 2), 'expected_return_at' => now()->subDay(), 'opening_mileage' => $n,
                'daily_rate' => 1500, 'deposit_amount' => 0, 'subtotal' => 3000, 'fees' => 0, 'taxes' => 540, 'total' => 3540, 'status' => 'closed',
            ]);
            $invoice = Invoice::query()->create([
                'number' => 'FAC-PF-'.$n, 'rental_id' => $rental->getKey(), 'customer_id' => $customer->getKey(),
                'issued_at' => now()->subDays($n % 90), 'due_at' => now()->addDays(5), 'subtotal' => 3000, 'tax' => 540, 'discount' => 0,
                'total' => 3540, 'paid_amount' => 1000, 'balance' => 2540, 'status' => 'partial',
            ]);
            Payment::query()->create(['receipt_number' => 'REC-PF-'.$n, 'invoice_id' => $invoice->getKey(), 'paid_at' => now()->subDays($n % 60), 'method' => 'cash', 'amount' => 1000]);
        }
    }

    /** @return array<string, array{queries: int, ms: float}> */
    private function measure(): array
    {
        $this->actingAs($this->admin);
        $result = [];

        foreach (self::ROUTES as $route) {
            $queries = 0;
            DB::listen(static function () use (&$queries): void {
                $queries++;
            });
            $start = hrtime(true);
            $this->get(route($route))->assertOk();
            $result[$route] = ['queries' => $queries, 'ms' => (hrtime(true) - $start) / 1e6];
        }

        return $result;
    }

    public function test_query_count_does_not_grow_with_data_volume_and_pages_stay_fast(): void
    {
        $this->seedRows(2);
        $small = $this->measure();

        $this->seedRows(self::SCALE);
        $large = $this->measure();

        $lines = [];
        foreach (self::ROUTES as $route) {
            $lines[] = sprintf('%-20s consultas %3d → %3d   tiempo %6.0f ms', $route, $small[$route]['queries'], $large[$route]['queries'], $large[$route]['ms']);
        }
        fwrite(STDERR, "\n[rendimiento] ".(self::SCALE + 2)." filas por entidad\n".implode("\n", $lines)."\n");

        foreach (self::ROUTES as $route) {
            $this->assertLessThanOrEqual(
                $small[$route]['queries'] + 3,
                $large[$route]['queries'],
                "Posible N+1 en {$route}: {$small[$route]['queries']} consultas con pocos datos y {$large[$route]['queries']} con muchos.",
            );
            $this->assertLessThan(5000, $large[$route]['ms'], "{$route} tardó demasiado con ".(self::SCALE + 2).' filas.');
        }
    }
}
