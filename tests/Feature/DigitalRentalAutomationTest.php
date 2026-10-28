<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Automation\Services\AutomationDispatcher;
use App\Domain\Security\Enums\RoleName;
use App\Models\AutomationDelivery;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalAcceptance;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class DigitalRentalAutomationTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(RoleName::ADMINISTRATOR->value);
        $this->actingAs($user);

        return $user;
    }

    private function rental(): Rental
    {
        $customer = Customer::query()->create([
            'document_type' => 'passport', 'document_number' => 'DR551200',
            'first_name' => 'Ana', 'last_name' => 'Pérez', 'phone' => '8095551010',
            'status' => 'active',
        ]);
        $category = VehicleCategory::query()->create([
            'code' => 'F5TEST', 'name' => 'Fase 5', 'daily_rate' => 1000, 'deposit_amount' => 0,
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'Hyundai']);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->getKey(), 'name' => 'Sonata', 'year' => 2025,
        ]);
        $vehicle = Vehicle::query()->create([
            'vehicle_model_id' => $model->getKey(), 'vehicle_category_id' => $category->getKey(),
            'code' => 'F5-CAR', 'plate' => 'F5A1000', 'color' => 'Gris',
            'mileage' => 100, 'status' => 'rented',
        ]);

        return Rental::query()->create([
            'code' => 'F5-RENT', 'customer_id' => $customer->getKey(),
            'vehicle_id' => $vehicle->getKey(), 'start_at' => now()->subDay(),
            'expected_return_at' => now()->addDay(), 'opening_mileage' => 100,
            'daily_rate' => 1000, 'deposit_amount' => 0,
            'subtotal' => 1000, 'fees' => 0, 'taxes' => 180, 'total' => 1180,
            'status' => 'open',
        ]);
    }

    public function test_operator_acceptance_records_hash_and_cannot_be_repeated(): void
    {
        $user = $this->administrator();
        $rental = $this->rental();

        $this->post(route('rentals.acceptance', $rental), [
            'signer_name' => 'Another Person',
            'acknowledge' => '1',
        ])->assertSessionHasErrors('signer_name');

        $this->post(route('rentals.acceptance', $rental), [
            'signer_name' => $user->name,
            'acknowledge' => '1',
        ])->assertRedirect();

        $record = RentalAcceptance::query()->firstOrFail();
        $this->assertSame($user->company_id, $record->company_id);
        $this->assertSame(64, strlen($record->document_hash));
        $this->assertSame('operator', $record->signer_role);

        $this->post(route('rentals.acceptance', $rental), [
            'signer_name' => $user->name,
            'acknowledge' => '1',
        ])->assertSessionHasErrors('rental');
        $this->assertSame(1, RentalAcceptance::query()->count());
    }

    public function test_reminder_dispatch_is_disabled_by_default_and_requires_tenant_opt_in(): void
    {
        $user = $this->administrator();
        $user->company->update(['email' => 'rentadora@example.test']);
        $this->rental()->update(['expected_return_at' => now()->subDay()]);
        Mail::fake();

        $this->assertSame(0, app(AutomationDispatcher::class)->run());
        $this->assertSame(0, AutomationDelivery::query()->count());

        config()->set('rentadrive.automation.enabled', true);
        $this->assertSame(0, app(AutomationDispatcher::class)->run());

        Setting::query()->create([
            'group' => 'automation',
            'key' => 'automation.email_opt_in',
            'value' => '1',
            'type' => 'string',
        ]);

        $this->assertSame(1, app(AutomationDispatcher::class)->run());
        $this->assertSame(0, app(AutomationDispatcher::class)->run());
        $this->assertSame(1, AutomationDelivery::query()->count());
        $this->assertSame('sent', AutomationDelivery::query()->firstOrFail()->status);
    }
}
