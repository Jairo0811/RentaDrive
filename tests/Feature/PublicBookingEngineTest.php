<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BookingOption;
use App\Models\Company;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicBookingEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_company_has_public_booking_portal_but_suspended_company_does_not(): void
    {
        $user = User::factory()->create();
        $company = $user->company;

        $this->get(route('public-booking.show', $company->slug))
            ->assertOk()
            ->assertSee($company->name);

        $company->update(['status' => 'suspended']);

        $this->get(route('public-booking.show', $company->slug))
            ->assertNotFound();
    }

    public function test_public_booking_creates_customer_quote_options_and_private_reservation(): void
    {
        [$user, $category] = $this->tenantWithVehicle();
        $company = $user->company;
        $branch = $user->branch;

        $gps = BookingOption::query()->create([
            'code' => 'GPS',
            'name' => 'GPS portátil',
            'type' => 'extra',
            'pricing_mode' => 'per_day',
            'price' => 300,
            'is_active' => true,
        ]);
        $insurance = BookingOption::query()->create([
            'code' => 'FULL',
            'name' => 'Seguro ampliado',
            'type' => 'insurance',
            'pricing_mode' => 'flat',
            'price' => 1200,
            'is_active' => true,
        ]);

        $startAt = now()->addDays(2)->setTime(9, 0, 0);
        $endAt = $startAt->copy()->addDays(3);

        $response = $this->post(route('public-booking.store', $company->slug), [
            'branch_id' => $branch->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $endAt->format('Y-m-d H:i:s'),
            'booking_option_ids' => [$gps->getKey(), $insurance->getKey()],
            'document_type' => 'cedula',
            'document_number' => '001-1391820-5',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.test',
            'phone' => '809-555-0101',
            'notes' => 'Llegaré unos minutos antes.',
        ]);

        $reservation = Reservation::query()
            ->where('source', 'public')
            ->whereHas('customer', fn ($query) => $query->where('email', 'ana@example.test'))
            ->firstOrFail();

        $response->assertRedirect(route('public-booking.confirmation', [
            'company' => $company->slug,
            'token' => $reservation->public_token,
        ]));

        $this->assertSame($company->getKey(), $reservation->company_id);
        $this->assertSame($branch->getKey(), $reservation->branch_id);
        $this->assertSame($category->getKey(), $reservation->vehicle_category_id);
        $this->assertNull($reservation->vehicle_id);
        $this->assertSame('pending', $reservation->status);
        $this->assertNotEmpty($reservation->public_token);
        $this->assertSame('2500.00', $reservation->daily_rate);
        $this->assertSame('7500.00', $reservation->subtotal);
        $this->assertSame('2100.00', $reservation->extras_total);
        $this->assertSame('9600.00', $reservation->estimated_total);
        $this->assertSame('5000.00', $reservation->deposit_amount);
        $this->assertCount(2, $reservation->bookingOptions()->get());
        $this->assertSame('00113918205', $reservation->customer->document_number);
    }

    public function test_public_booking_rejects_invalid_dominican_cedula(): void
    {
        [$user, $category] = $this->tenantWithVehicle();
        $company = $user->company;
        $branch = $user->branch;
        $startAt = now()->addDays(2)->setTime(9, 0, 0);
        $endAt = $startAt->copy()->addDay();

        $this->post(route('public-booking.store', $company->slug), [
            'branch_id' => $branch->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $endAt->format('Y-m-d H:i:s'),
            'document_type' => 'cedula',
            'document_number' => '001-1391820-6',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana-invalid@example.test',
            'phone' => '809-555-0101',
        ])->assertSessionHasErrors('document_number');

        $this->assertDatabaseMissing('customers', [
            'email' => 'ana-invalid@example.test',
        ]);
    }

    public function test_unassigned_public_reservation_consumes_category_capacity(): void
    {
        [$user, $category] = $this->tenantWithVehicle();
        $company = $user->company;
        $branch = $user->branch;
        $startAt = now()->addDays(2)->setTime(9, 0, 0);
        $endAt = $startAt->copy()->addDays(2);

        $this->post(route('public-booking.store', $company->slug), [
            'branch_id' => $branch->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $endAt->format('Y-m-d H:i:s'),
            'document_type' => 'passport',
            'document_number' => 'PA1001',
            'first_name' => 'Luis',
            'last_name' => 'Santos',
            'email' => 'luis@example.test',
            'phone' => '809-555-0102',
        ])->assertRedirect();

        $this->get(route('public-booking.availability', [
            'company' => $company->slug,
            'branch_id' => $branch->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $endAt->format('Y-m-d H:i:s'),
        ]))
            ->assertOk()
            ->assertSee('Sin disponibilidad');

        $this->post(route('public-booking.store', $company->slug), [
            'branch_id' => $branch->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $endAt->format('Y-m-d H:i:s'),
            'document_type' => 'passport',
            'document_number' => 'PA1002',
            'first_name' => 'Marta',
            'last_name' => 'Díaz',
            'email' => 'marta@example.test',
            'phone' => '809-555-0103',
        ])->assertSessionHasErrors('vehicle_category_id');
    }

    public function test_private_confirmation_token_is_scoped_to_company_and_can_cancel_future_booking(): void
    {
        [$user, $category] = $this->tenantWithVehicle();
        $company = $user->company;
        $branch = $user->branch;
        $startAt = now()->addDays(4)->setTime(9, 0, 0);
        $endAt = $startAt->copy()->addDay();

        $this->post(route('public-booking.store', $company->slug), [
            'branch_id' => $branch->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $endAt->format('Y-m-d H:i:s'),
            'document_type' => 'passport',
            'document_number' => 'PA2001',
            'first_name' => 'Carlos',
            'last_name' => 'Ramos',
            'email' => 'carlos@example.test',
            'phone' => '809-555-0104',
        ])->assertRedirect();

        $reservation = Reservation::query()->where('source', 'public')->firstOrFail();

        $this->get(route('public-booking.confirmation', [
            'company' => $company->slug,
            'token' => $reservation->public_token,
        ]))
            ->assertOk()
            ->assertSee($reservation->code);

        $otherCompany = Company::query()->create([
            'name' => 'Otra Empresa',
            'slug' => 'otra-empresa',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
            'plan_code' => 'starter',
        ]);

        $this->get(route('public-booking.confirmation', [
            'company' => $otherCompany->slug,
            'token' => $reservation->public_token,
        ]))->assertNotFound();

        $this->post(route('public-booking.cancel', [
            'company' => $company->slug,
            'token' => $reservation->public_token,
        ]))->assertRedirect();

        $reservation->refresh();
        $this->assertSame('cancelled', $reservation->status);
        $this->assertNotNull($reservation->cancelled_at);
    }

    /**
     * @return array{User, VehicleCategory}
     */
    private function tenantWithVehicle(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = VehicleCategory::query()->create([
            'code' => 'SED',
            'name' => 'Sedán',
            'daily_rate' => 2500,
            'deposit_amount' => 5000,
            'description' => 'Cómodo para ciudad y carretera.',
            'is_active' => true,
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'Toyota', 'is_active' => true]);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->getKey(),
            'name' => 'Corolla',
            'year' => 2026,
            'is_active' => true,
        ]);
        Vehicle::query()->create([
            'branch_id' => $user->branch_id,
            'vehicle_model_id' => $model->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'code' => 'RD-WEB-01',
            'plate' => 'A123456',
            'color' => 'Azul',
            'transmission' => 'automatic',
            'fuel_type' => 'gasoline',
            'seats' => 5,
            'mileage' => 100,
            'status' => 'available',
        ]);

        return [$user, $category];
    }
}
