<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PublicReservationCreated;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_commercial_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Tu rent-a-car, operando desde un solo lugar.');
    }

    public function test_custom_domain_redirects_to_the_company_booking_portal(): void
    {
        [$company] = $this->companyWithFleet('custom-domain', 'Toyota', 'Corolla', 'CD');
        $company->update(['public_domain' => 'reservas.example.com']);
        app(TenantContext::class)->clear();

        $this->withServerVariables(['HTTP_HOST' => 'reservas.example.com'])
            ->get('/')
            ->assertRedirect('/r/'.$company->slug);
    }

    public function test_active_company_has_a_white_label_public_booking_portal(): void
    {
        [$company] = $this->companyWithFleet('portal-a', 'Toyota', 'Corolla', 'PA');
        $company->update([
            'settings' => [
                'branding' => [
                    'primary_color' => '#123456',
                    'accent_color' => '#654321',
                ],
            ],
        ]);

        app(TenantContext::class)->clear();

        $this->get(route('public.booking.show', ['company' => $company->slug]))
            ->assertOk()
            ->assertSee($company->name)
            ->assertSee('Reserva directa')
            ->assertSee('#123456');
    }

    public function test_suspended_company_portal_is_not_publicly_available(): void
    {
        $company = Company::query()->create([
            'name' => 'Rent a Car Suspendido',
            'slug' => 'suspendido',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'suspended',
        ]);

        $this->get(route('public.booking.show', ['company' => $company->slug]))
            ->assertNotFound();
    }

    public function test_vehicle_search_is_isolated_by_company(): void
    {
        [$companyA, $branchA] = $this->companyWithFleet('tenant-a', 'Toyota', 'Corolla', 'TA');
        $this->companyWithFleet('tenant-b', 'Honda', 'Civic', 'TB');

        app(TenantContext::class)->clear();

        $startAt = now()->addDay()->startOfHour();
        $endAt = now()->addDays(3)->startOfHour();

        $this->get(route('public.booking.search', [
            'company' => $companyA->slug,
            'branch_id' => $branchA->id,
            'start_at' => $startAt->format('Y-m-d\TH:i'),
            'end_at' => $endAt->format('Y-m-d\TH:i'),
        ]))
            ->assertOk()
            ->assertSee('Toyota Corolla 2026')
            ->assertDontSee('Honda Civic 2026');
    }

    public function test_public_checkout_applies_duration_extras_and_promo_pricing(): void
    {
        Mail::fake();

        [$company, $branch, $vehicle] = $this->companyWithFleet('pricing', 'Kia', 'K5', 'PR');
        $company->update([
            'settings' => [
                'booking' => [
                    'weekly_discount_percent' => 10,
                    'monthly_discount_percent' => 20,
                    'extras' => 'GPS|GPS|100|per_day|extra',
                    'promo_codes' => 'SAVE5|percent|5||',
                    'email_confirmation_enabled' => true,
                ],
            ],
        ]);

        app(TenantContext::class)->clear();

        $startAt = now()->addDays(2)->startOfHour();
        $endAt = now()->addDays(10)->startOfHour();

        $response = $this->post(route('public.booking.store', ['company' => $company->slug]), [
            'branch_id' => $branch->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => $startAt->format('Y-m-d\TH:i'),
            'end_at' => $endAt->format('Y-m-d\TH:i'),
            'extras' => ['GPS'],
            'promo_code' => 'SAVE5',
            'document_type' => 'passport',
            'document_number' => 'P1234567',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.com',
            'phone' => '809-555-0101',
            'terms' => '1',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'vehicle_id' => $vehicle->id,
            'promo_code' => 'SAVE5',
            'base_total' => '20000.00',
            'extras_total' => '800.00',
            'discount_total' => '2900.00',
            'estimated_total' => '17900.00',
            'status' => 'pending',
        ]);

        Mail::assertSent(PublicReservationCreated::class, 1);
    }

    public function test_public_checkout_creates_a_pending_reservation_for_the_correct_tenant(): void
    {
        Mail::fake();

        [$company, $branch, $vehicle] = $this->companyWithFleet('checkout', 'Kia', 'K5', 'CK');
        app(TenantContext::class)->clear();

        $startAt = now()->addDays(2)->startOfHour();
        $endAt = now()->addDays(5)->startOfHour();

        $response = $this->post(route('public.booking.store', ['company' => $company->slug]), [
            'branch_id' => $branch->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => $startAt->format('Y-m-d\TH:i'),
            'end_at' => $endAt->format('Y-m-d\TH:i'),
            'document_type' => 'passport',
            'document_number' => 'P7654321',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana2@example.com',
            'phone' => '809-555-0101',
            'terms' => '1',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('/r/'.$company->slug.'/reservation/', (string) $response->headers->get('Location'));

        $this->assertDatabaseHas('customers', [
            'company_id' => $company->id,
            'document_number' => 'P7654321',
            'email' => 'ana2@example.com',
        ]);

        $this->assertDatabaseHas('reservations', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'pending',
        ]);
    }

    public function test_customer_can_cancel_with_a_valid_signed_link_before_deadline(): void
    {
        [$company, $branch, $vehicle] = $this->companyWithFleet('cancel', 'Honda', 'CR-V', 'CA');
        app(TenantContext::class)->set($company, $branch);

        $reservation = Reservation::query()->create([
            'code' => 'RES-CANCEL-001',
            'customer_id' => $this->createCustomerForTenant()->id,
            'vehicle_category_id' => $vehicle->vehicle_category_id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(5),
            'pickup_location' => $branch->name,
            'return_location' => $branch->name,
            'daily_rate' => 2500,
            'estimated_total' => 5000,
            'status' => 'pending',
        ]);

        app(TenantContext::class)->clear();

        $url = URL::temporarySignedRoute(
            'public.booking.cancel.show',
            now()->addDay(),
            ['company' => $company->slug, 'code' => $reservation->code],
        );

        $this->get($url)->assertOk()->assertSee('Cancelar '.$reservation->code);
        $this->post($url, ['reason' => 'Cambio de planes'])->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Cambio de planes',
        ]);
    }

    private function createCustomerForTenant(): \App\Models\Customer
    {
        return \App\Models\Customer::query()->create([
            'document_type' => 'passport',
            'document_number' => 'PASS-CANCEL',
            'first_name' => 'Cliente',
            'last_name' => 'Cancelación',
            'email' => 'cancel@example.com',
            'phone' => '8095550199',
            'status' => 'active',
        ]);
    }

    /**
     * @return array{Company, Branch, Vehicle}
     */
    private function companyWithFleet(
        string $slug,
        string $brandName,
        string $modelName,
        string $prefix,
    ): array {
        $company = Company::query()->create([
            'name' => 'Rent a Car '.strtoupper($prefix),
            'slug' => $slug,
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
        ]);

        $branch = Branch::query()->create([
            'company_id' => $company->id,
            'name' => 'Sucursal Principal '.$prefix,
            'code' => 'MAIN-'.$prefix,
            'city' => 'Santo Domingo',
            'is_primary' => true,
            'is_active' => true,
        ]);

        app(TenantContext::class)->set($company, $branch);

        $category = VehicleCategory::query()->create([
            'code' => 'SED-'.$prefix,
            'name' => 'Sedán '.$prefix,
            'daily_rate' => 2500,
            'deposit_amount' => 5000,
            'is_active' => true,
        ]);

        $brand = VehicleBrand::query()->create([
            'name' => $brandName,
            'is_active' => true,
        ]);

        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => $modelName,
            'year' => 2026,
            'is_active' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'vehicle_model_id' => $model->id,
            'vehicle_category_id' => $category->id,
            'code' => 'VEH-'.$prefix,
            'plate' => 'P-'.$prefix.'-001',
            'color' => 'Azul',
            'transmission' => 'automatic',
            'fuel_type' => 'gasoline',
            'seats' => 5,
            'mileage' => 1000,
            'status' => 'available',
        ]);

        return [$company, $branch, $vehicle];
    }
}
