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
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/** Registrar pagos (mostrador) y anularlos (control) son permisos distintos. */
final class PaymentPermissionSplitTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Branch $branch;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->company = Company::query()->create(['name' => 'Cobros SRL', 'slug' => 'cobros', 'currency' => 'DOP', 'timezone' => 'America/Santo_Domingo', 'status' => 'active']);
        $this->branch = Branch::query()->create(['company_id' => $this->company->getKey(), 'name' => 'Principal', 'code' => 'COB', 'is_primary' => true, 'is_active' => true]);

        app(TenantContext::class)->set($this->company, $this->branch);
        $customer = Customer::query()->create(['document_type' => 'passport', 'document_number' => 'CB-1', 'first_name' => 'Ana', 'last_name' => 'Cobro', 'phone' => '8095550003', 'status' => 'active']);
        $category = VehicleCategory::query()->create(['code' => 'CB', 'name' => 'Cat', 'daily_rate' => 1000, 'deposit_amount' => 0]);
        $brand = VehicleBrand::query()->create(['name' => 'MarcaCb']);
        $model = VehicleModel::query()->create(['vehicle_brand_id' => $brand->getKey(), 'name' => 'ModeloCb', 'year' => 2025]);
        $vehicle = Vehicle::query()->create(['vehicle_model_id' => $model->getKey(), 'vehicle_category_id' => $category->getKey(), 'code' => 'CB-1', 'plate' => 'CB0001', 'color' => 'Gris', 'mileage' => 1, 'status' => 'rented']);
        $rental = Rental::query()->create([
            'code' => 'ALQ-CB-1', 'customer_id' => $customer->getKey(), 'vehicle_id' => $vehicle->getKey(),
            'start_at' => now(), 'expected_return_at' => now()->addDay(), 'opening_mileage' => 1,
            'daily_rate' => 1000, 'deposit_amount' => 0, 'subtotal' => 1000, 'fees' => 0, 'taxes' => 180, 'total' => 1180, 'status' => 'open',
        ]);
        $this->invoice = Invoice::query()->create([
            'number' => 'FAC-CB-1', 'rental_id' => $rental->getKey(), 'customer_id' => $customer->getKey(),
            'issued_at' => now(), 'subtotal' => 1000, 'tax' => 180, 'discount' => 0, 'total' => 1180,
            'paid_amount' => 0, 'balance' => 1180, 'status' => 'pending',
        ]);
    }

    private function userWithRole(RoleName $role): User
    {
        $user = User::factory()->create(['company_id' => $this->company->getKey(), 'branch_id' => $this->branch->getKey()]);
        $user->assignRole($role->value);

        return $user;
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return ['invoice_id' => $this->invoice->getKey(), 'paid_at' => now()->toDateTimeString(), 'method' => 'cash', 'amount' => 100];
    }

    public function test_rental_agent_can_record_a_payment_but_cannot_void_it(): void
    {
        $agent = $this->userWithRole(RoleName::RENTAL_AGENT);

        $this->actingAs($agent)->post(route('payments.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $payment = Payment::query()->firstOrFail();
        $this->assertSame('100.00', $this->invoice->fresh()->paid_amount);

        $this->actingAs($agent)->delete(route('payments.destroy', $payment))->assertForbidden();
        $this->assertNull($payment->fresh()->voided_at);
    }

    public function test_administrator_can_record_and_void(): void
    {
        $admin = $this->userWithRole(RoleName::ADMINISTRATOR);

        $this->actingAs($admin)->post(route('payments.store'), $this->payload())->assertRedirect();
        $this->actingAs($admin)->delete(route('payments.destroy', Payment::query()->firstOrFail()))->assertRedirect();
        $this->assertNotNull(Payment::query()->firstOrFail()->voided_at);
    }

    public function test_manager_and_inspector_cannot_record_payments(): void
    {
        foreach ([RoleName::MANAGER, RoleName::INSPECTOR] as $role) {
            $this->actingAs($this->userWithRole($role))->post(route('payments.store'), $this->payload())->assertForbidden();
        }

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_legacy_manage_payments_holders_keep_working_without_reseeding(): void
    {
        $legacy = $this->userWithRole(RoleName::INSPECTOR);
        $legacy->givePermissionTo(Permission::findByName('manage payments', 'web'));

        $this->actingAs($legacy)->post(route('payments.store'), $this->payload())->assertRedirect();
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_payment_form_and_void_button_follow_the_permissions(): void
    {
        $agent = $this->userWithRole(RoleName::RENTAL_AGENT);
        $this->actingAs($agent)->post(route('payments.store'), $this->payload());

        $html = $this->actingAs($agent)->get(route('payments.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Registrar', $html);
        $this->assertStringNotContainsString('data-confirm-label="Anular"', $html);
    }
}
