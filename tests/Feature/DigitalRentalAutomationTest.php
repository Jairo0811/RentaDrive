<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Operations\Services\RentalWorkflowService;
use App\Domain\Security\Enums\RoleName;
use App\Jobs\ScanOperationalAlertsJob;
use App\Jobs\SendAutomationDeliveryJob;
use App\Mail\AutomationAlertMail;
use App\Models\AutomationDelivery;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Inspection;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleMaintenance;
use App\Models\VehicleModel;
use App\Support\Automation\AutomationChannelManager;
use App\Support\Automation\AutomationDispatchService;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DigitalRentalAutomationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Branch $branch;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->company = Company::query()->create([
            'name' => 'Digital Rent RD',
            'slug' => 'digital-rent-rd',
            'email' => 'operaciones@digital-rent.test',
            'phone' => '8095550100',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
            'settings' => [
                'automation' => [
                    'email_enabled' => true,
                    'whatsapp_enabled' => false,
                    'reservation_reminder_hours' => 24,
                    'return_reminder_hours' => 4,
                    'maintenance_reminder_days' => 7,
                    'document_reminder_days' => 30,
                ],
            ],
        ]);

        $this->branch = Branch::query()->create([
            'company_id' => $this->company->getKey(),
            'name' => 'Sucursal Principal',
            'code' => 'MAIN',
            'city' => 'Santo Domingo',
            'is_primary' => true,
            'is_active' => true,
        ]);

        app(TenantContext::class)->set($this->company, $this->branch);

        $this->administrator = User::factory()->create([
            'company_id' => $this->company->getKey(),
            'branch_id' => $this->branch->getKey(),
        ]);
        $this->administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($this->administrator);
    }

    public function test_full_digital_rental_flow_signs_inspects_and_closes_from_sealed_return(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        [$customer, $vehicle] = $this->fixtures();
        $rental = $this->openRental($customer, $vehicle);

        $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $this->post(route('rentals.signature.store', $rental), [
            'signed_name' => $customer->full_name,
            'signature_data' => $signature,
            'accept_terms' => '1',
        ])->assertRedirect(route('rentals.show', $rental));

        $this->assertDatabaseHas('rental_signatures', [
            'rental_id' => $rental->getKey(),
            'role' => 'renter',
            'signer_name' => $customer->full_name,
        ]);

        $this->post(route('inspections.store'), [
            'rental_id' => $rental->getKey(),
            'type' => 'delivery',
            'mode' => 'mobile',
            'inspected_at' => now()->addMinute()->format('Y-m-d H:i:s'),
            'mileage' => 1000,
            'fuel_level' => 100,
            'body_condition' => 'good',
            'interior_condition' => 'good',
            'tires_condition' => 'good',
            'accessories_checklist' => ['spare_tire', 'jack', 'documents'],
            'damage_area' => ['Parachoques delantero'],
            'damage_severity' => ['minor'],
            'damage_description' => ['Rayón previo documentado'],
            'photos' => [UploadedFile::fake()->image('delivery.jpg', 800, 600)],
        ])->assertRedirect();

        $delivery = Inspection::query()->where('rental_id', $rental->getKey())->where('type', 'delivery')->firstOrFail();
        $this->assertTrue($delivery->isSealed());
        $this->assertNotEmpty($delivery->evidence_hash);
        $this->assertSame('spare_tire', $delivery->accessories_checklist[0]);
        $this->assertSame('minor', $delivery->damage_items[0]['severity']);

        $this->post(route('inspections.store'), [
            'rental_id' => $rental->getKey(),
            'type' => 'return',
            'mode' => 'mobile',
            'inspected_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'mileage' => 1125,
            'fuel_level' => 72,
            'body_condition' => 'good',
            'interior_condition' => 'good',
            'tires_condition' => 'good',
            'accessories_checklist' => ['spare_tire', 'jack', 'documents'],
            'photos' => [UploadedFile::fake()->image('return.jpg', 800, 600)],
        ])->assertRedirect();

        $return = Inspection::query()->where('rental_id', $rental->getKey())->where('type', 'return')->firstOrFail();
        $this->assertTrue($return->isSealed());

        $this->patch(route('rentals.close', $rental), [
            'fees' => 250,
            'vehicle_status' => 'available',
            'notes' => 'Cierre digital completado.',
        ])->assertRedirect(route('rentals.show', $rental));

        $rental->refresh();

        $this->assertSame('closed', $rental->status);
        $this->assertSame(1125, $rental->closing_mileage);
        $this->assertSame(72.0, (float) $rental->fuel_in);
        $this->assertSame(
            $return->inspected_at->format('Y-m-d H:i:s'),
            $rental->returned_at->format('Y-m-d H:i:s'),
        );
    }

    public function test_sealed_inspection_cannot_be_deleted(): void
    {
        Storage::fake('public');

        [$customer, $vehicle] = $this->fixtures('LOCK');
        $rental = $this->openRental($customer, $vehicle);

        $this->post(route('inspections.store'), [
            'rental_id' => $rental->getKey(),
            'type' => 'delivery',
            'mode' => 'mobile',
            'inspected_at' => now()->addMinute()->format('Y-m-d H:i:s'),
            'mileage' => 1000,
            'fuel_level' => 100,
            'body_condition' => 'good',
            'interior_condition' => 'good',
            'tires_condition' => 'good',
            'photos' => [UploadedFile::fake()->image('sealed.jpg', 640, 480)],
        ])->assertRedirect();

        $inspection = Inspection::query()->firstOrFail();

        $this->from(route('inspections.show', $inspection))
            ->delete(route('inspections.destroy', $inspection))
            ->assertRedirect(route('inspections.show', $inspection));

        $this->assertDatabaseHas('inspections', ['id' => $inspection->getKey()]);
    }

    public function test_scanner_queues_each_operational_alert_only_once(): void
    {
        Queue::fake();

        [$customer, $vehicle] = $this->fixtures('AUTO');
        $customer->update(['license_expiry' => now()->addDays(10)->toDateString()]);
        $vehicle->update([
            'insurance_expires_at' => now()->addDays(5)->toDateString(),
            'next_maintenance_at' => 1400,
            'mileage' => 1000,
        ]);

        Reservation::query()->create([
            'code' => 'RES-AUTO-001',
            'customer_id' => $customer->getKey(),
            'vehicle_category_id' => $vehicle->vehicle_category_id,
            'vehicle_id' => $vehicle->getKey(),
            'start_at' => now()->addHours(12),
            'end_at' => now()->addDays(2),
            'pickup_location' => $this->branch->name,
            'return_location' => $this->branch->name,
            'daily_rate' => 2500,
            'estimated_total' => 5000,
            'status' => 'confirmed',
        ]);

        $rental = $this->openRental($customer, $vehicle, now()->subDay(), now()->addHours(2));

        VehicleMaintenance::query()->create([
            'vehicle_id' => $vehicle->getKey(),
            'maintenance_type' => 'Preventivo',
            'scheduled_at' => now()->addDays(2),
            'mileage' => 1500,
            'cost' => 0,
            'provider' => 'Taller Demo',
            'status' => 'scheduled',
            'description' => 'Cambio de aceite.',
        ]);

        $scanner = new ScanOperationalAlertsJob;
        $scanner->handle(
            app(TenantContext::class),
            app(AutomationDispatchService::class),
        );

        $this->assertDatabaseHas('automation_deliveries', [
            'event_key' => 'reservation-reminder:'.Reservation::query()->where('code', 'RES-AUTO-001')->firstOrFail()->getKey().':'.Reservation::query()->where('code', 'RES-AUTO-001')->firstOrFail()->start_at->timestamp,
            'channel' => 'email',
        ]);
        $this->assertDatabaseHas('automation_deliveries', [
            'event_key' => 'rental-return:'.$rental->getKey().':'.$rental->expected_return_at->timestamp,
            'channel' => 'email',
        ]);

        $firstCount = AutomationDelivery::query()->count();
        $this->assertGreaterThanOrEqual(5, $firstCount);

        $scanner->handle(
            app(TenantContext::class),
            app(AutomationDispatchService::class),
        );

        $this->assertSame($firstCount, AutomationDelivery::query()->count());

        Queue::assertPushed(SendAutomationDeliveryJob::class, $firstCount);
    }

    public function test_queued_email_delivery_marks_alert_as_sent(): void
    {
        Mail::fake();

        $delivery = AutomationDelivery::query()->create([
            'event_key' => 'test-email:1',
            'channel' => 'email',
            'subject_type' => Reservation::class,
            'subject_id' => 1,
            'recipient' => 'cliente@example.com',
            'title' => 'Recordatorio de prueba',
            'message' => 'Mensaje de prueba',
            'status' => 'pending',
            'attempts' => 0,
        ]);

        app(TenantContext::class)->clear();

        $job = new SendAutomationDeliveryJob(
            (int) $this->company->getKey(),
            (int) $delivery->getKey(),
        );
        $job->handle(
            app(TenantContext::class),
            app(AutomationChannelManager::class),
        );

        Mail::assertSent(AutomationAlertMail::class, 1);

        app(TenantContext::class)->set($this->company, $this->branch);
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->sent_at);
    }

    /**
     * @return array{Customer, Vehicle}
     */
    private function fixtures(string $suffix = 'DIG'): array
    {
        $customer = Customer::query()->create([
            'document_type' => 'passport',
            'document_number' => 'PASS-'.$suffix,
            'first_name' => 'Cliente',
            'last_name' => $suffix,
            'email' => strtolower($suffix).'@example.com',
            'phone' => '8095550199',
            'license_number' => 'LIC-'.$suffix,
            'license_expiry' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $category = VehicleCategory::query()->create([
            'code' => 'CAT-'.$suffix,
            'name' => 'Categoría '.$suffix,
            'daily_rate' => 2500,
            'deposit_amount' => 5000,
            'is_active' => true,
        ]);

        $brand = VehicleBrand::query()->create([
            'name' => 'Marca '.$suffix,
            'is_active' => true,
        ]);

        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->getKey(),
            'name' => 'Modelo '.$suffix,
            'year' => 2026,
            'is_active' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'vehicle_model_id' => $model->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'code' => 'VEH-'.$suffix,
            'plate' => 'P-'.$suffix,
            'color' => 'Azul',
            'transmission' => 'automatic',
            'fuel_type' => 'gasoline',
            'seats' => 5,
            'mileage' => 1000,
            'status' => 'available',
        ]);

        return [$customer, $vehicle];
    }

    private function openRental(
        Customer $customer,
        Vehicle $vehicle,
        mixed $startAt = null,
        mixed $expectedReturnAt = null,
    ): Rental {
        return app(RentalWorkflowService::class)->open([
            'customer_id' => $customer->getKey(),
            'vehicle_id' => $vehicle->getKey(),
            'start_at' => ($startAt ?? now())->format('Y-m-d H:i:s'),
            'expected_return_at' => ($expectedReturnAt ?? now()->addDays(2))->format('Y-m-d H:i:s'),
            'opening_mileage' => 1000,
            'fuel_out' => 100,
            'daily_rate' => 2500,
            'deposit_amount' => 5000,
            'fees' => 0,
        ]);
    }
}
