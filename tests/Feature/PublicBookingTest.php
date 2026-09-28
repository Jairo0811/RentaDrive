<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_active_company_has_a_public_booking_portal(): void
    {
        [$company] = $this->companyWithFleet('portal-a', 'Toyota', 'Corolla', 'PA');

        app(TenantContext::class)->clear();

        $this->get(route('public.booking.show', ['company' => $company->slug]))
            ->assertOk()
            ->assertSee($company->name)
            ->assertSee('Reserva directa');
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

    public function test_public_checkout_creates_a_pending_reservation_for_the_correct_tenant(): void
    {
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
            'document_number' => 'P1234567',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.com',
            'phone' => '809-555-0101',
            'terms' => '1',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString(
            '/r/'.$company->slug.'/reservation/',
            (string) $response->headers->get('Location'),
        );

        $this->assertDatabaseHas('customers', [
            'company_id' => $company->id,
            'document_number' => 'P1234567',
            'email' => 'ana@example.com',
        ]);

        $this->assertDatabaseHas('reservations', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'pending',
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
