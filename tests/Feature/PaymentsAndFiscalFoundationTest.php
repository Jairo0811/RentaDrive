<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Fiscal\Services\FiscalDraftService;
use App\Domain\Payments\Services\PaymentIntentService;
use App\Domain\Security\Enums\RoleName;
use App\Models\Customer;
use App\Models\FiscalDraft;
use App\Models\FiscalProfile;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PaymentIntent;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class PaymentsAndFiscalFoundationTest extends TestCase
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

    private function reservation(User $user): Reservation
    {
        $customer = Customer::query()->create([
            'document_type' => 'passport',
            'document_number' => 'P5550001',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'phone' => '8095551212',
            'status' => 'active',
        ]);
        $category = VehicleCategory::query()->create([
            'code' => 'F3TEST', 'name' => 'Fase 3', 'daily_rate' => 1000, 'deposit_amount' => 2000,
        ]);

        return Reservation::query()->create([
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'code' => 'RES-F3-001',
            'customer_id' => $customer->id,
            'vehicle_category_id' => $category->id,
            'start_at' => now()->addDays(4),
            'end_at' => now()->addDays(6),
            'daily_rate' => 1000,
            'estimated_total' => 2000,
            'deposit_amount' => 2000,
            'status' => 'pending',
            'source' => 'public',
            'public_token' => 'testtoken-f3-unique',
        ]);
    }

    private function sendWebhook(array $event, bool $signed = true): TestResponse
    {
        $payload = json_encode($event, JSON_THROW_ON_ERROR);
        $signature = $signed ? hash_hmac('sha256', $payload, 'test-webhook-secret') : 'bad-signature';

        return $this->call('POST', '/api/payments/sandbox/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RENTADRIVE_SIGNATURE' => $signature,
        ], $payload);
    }

    public function test_sandbox_deposit_is_idempotent_and_never_confirms_itself(): void
    {
        config()->set('services.rentadrive_gateway.webhook_secret', 'test-webhook-secret');
        $user = $this->administrator();
        $reservation = $this->reservation($user);
        $service = app(PaymentIntentService::class);

        $first = $service->beginReservationDeposit($reservation);
        $second = $service->beginReservationDeposit($reservation);

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame('pending', $first->status);
        $this->assertSame('2000.00', $first->amount);
        $this->assertSame(1, PaymentIntent::query()->count());
    }

    public function test_invalid_signature_cannot_capture_a_deposit(): void
    {
        config()->set('services.rentadrive_gateway.webhook_secret', 'test-webhook-secret');
        $user = $this->administrator();
        $intent = app(PaymentIntentService::class)->beginReservationDeposit($this->reservation($user));
        $event = [
            'event_id' => 'evt-invalid',
            'intent_id' => $intent->public_id,
            'event_type' => 'captured',
            'amount' => '2000.00',
            'currency' => 'DOP',
        ];

        $this->sendWebhook($event, false)->assertUnauthorized();
        $this->assertSame('pending', $intent->fresh()->status);
        $this->assertSame(0, PaymentEvent::query()->count());
    }

    public function test_signed_payment_events_are_idempotent_and_refundable_once(): void
    {
        config()->set('services.rentadrive_gateway.webhook_secret', 'test-webhook-secret');
        $user = $this->administrator();
        $intent = app(PaymentIntentService::class)->beginReservationDeposit($this->reservation($user));

        $captured = [
            'event_id' => 'evt-capture-one',
            'intent_id' => $intent->public_id,
            'event_type' => 'captured',
            'amount' => '2000.00',
            'currency' => 'DOP',
            'reference' => 'TEST-CAPTURE',
        ];

        $this->sendWebhook($captured)->assertOk();
        $this->sendWebhook($captured)->assertOk();
        $this->assertSame('captured', $intent->fresh()->status);
        $this->assertSame(1, PaymentEvent::query()->count());

        $this->sendWebhook([
            ...$captured,
            'event_id' => 'evt-refund-one',
            'event_type' => 'refunded',
        ])->assertOk();

        $this->assertSame('refunded', $intent->fresh()->status);
        $this->assertSame(2, PaymentEvent::query()->count());
    }

    public function test_wrong_amount_is_rejected_even_with_a_valid_signature(): void
    {
        config()->set('services.rentadrive_gateway.webhook_secret', 'test-webhook-secret');
        $user = $this->administrator();
        $intent = app(PaymentIntentService::class)->beginReservationDeposit($this->reservation($user));

        $this->sendWebhook([
            'event_id' => 'evt-wrong-amount',
            'intent_id' => $intent->public_id,
            'event_type' => 'captured',
            'amount' => '100.00',
            'currency' => 'DOP',
        ])->assertUnprocessable();

        $this->assertSame('pending', $intent->fresh()->status);
    }

    public function test_fiscal_profile_is_tenant_scoped_and_drafts_are_not_issued(): void
    {
        $user = $this->administrator();

        $this->put(route('fiscal.update'), [
            'legal_name' => 'Prueba Rentadora SRL',
            'rnc' => '101000001',
            'fiscal_mode' => 'standard',
            'address' => 'Santo Domingo',
        ])->assertRedirect();

        $profile = FiscalProfile::query()->firstOrFail();
        $this->assertSame($user->company_id, $profile->company_id);
        $this->assertSame('not_certified', $profile->ecf_status);

        $this->put(route('fiscal.update'), [
            'legal_name' => 'No válido',
            'rnc' => 'XYZ',
            'fiscal_mode' => 'standard',
        ])->assertSessionHasErrors('rnc');

        $customer = Customer::query()->create([
            'document_type' => 'passport', 'document_number' => 'P88111',
            'first_name' => 'Luis', 'last_name' => 'Díaz',
            'status' => 'active',
        ]);
        $category = VehicleCategory::query()->create([
            'code' => 'F4TEST', 'name' => 'Fase 4', 'daily_rate' => 1000, 'deposit_amount' => 0,
        ]);
        $brand = VehicleBrand::query()->create(['name' => 'Kia']);
        $model = VehicleModel::query()->create(['vehicle_brand_id' => $brand->id, 'name' => 'Rio', 'year' => 2025]);
        $vehicle = Vehicle::query()->create([
            'vehicle_model_id' => $model->id, 'vehicle_category_id' => $category->id,
            'code' => 'F4-VEH', 'plate' => 'F4-0001', 'color' => 'Gris',
            'mileage' => 100, 'status' => 'rented',
        ]);
        $rental = Rental::query()->create([
            'code' => 'F4-RENT', 'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id, 'start_at' => now(),
            'expected_return_at' => now()->addDay(), 'opening_mileage' => 100,
            'daily_rate' => 1000, 'deposit_amount' => 0,
            'subtotal' => 1000, 'fees' => 0, 'taxes' => 180,
            'total' => 1180, 'status' => 'open',
        ]);
        $invoice = Invoice::query()->create([
            'number' => 'F4-FAC', 'rental_id' => $rental->id, 'customer_id' => $customer->id,
            'issued_at' => now(), 'subtotal' => 1000,
            'tax' => 180, 'discount' => 0, 'total' => 1180,
            'paid_amount' => 0, 'balance' => 1180, 'status' => 'pending',
        ]);

        $draft = app(FiscalDraftService::class)->prepare($invoice, 'consumer');
        $again = app(FiscalDraftService::class)->prepare($invoice, 'consumer');
        $this->assertSame($draft->getKey(), $again->getKey());
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('101000001', $draft->issuer_rnc);
        $this->assertSame(1, FiscalDraft::query()->count());
    }

    public function test_manual_payment_void_preserves_receipt_and_recalculates_invoice(): void
    {
        $user = $this->administrator();
        $reservation = $this->reservation($user);

        $brand = VehicleBrand::query()->create(['name' => 'Toyota']);
        $model = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id, 'name' => 'Yaris', 'year' => 2025,
        ]);
        $vehicle = Vehicle::query()->create([
            'vehicle_model_id' => $model->id,
            'vehicle_category_id' => $reservation->vehicle_category_id,
            'code' => 'F3-VEH', 'plate' => 'F3-0002',
            'color' => 'Azul', 'mileage' => 20, 'status' => 'rented',
        ]);
        $rental = Rental::query()->create([
            'code' => 'F3-RENT', 'customer_id' => $reservation->customer_id,
            'vehicle_id' => $vehicle->id, 'start_at' => now(),
            'expected_return_at' => now()->addDay(), 'opening_mileage' => 20,
            'daily_rate' => 1000, 'deposit_amount' => 0,
            'subtotal' => 1000, 'fees' => 0, 'taxes' => 180,
            'total' => 1180, 'status' => 'open',
        ]);
        $invoice = Invoice::query()->create([
            'number' => 'F3-FAC', 'rental_id' => $rental->id,
            'customer_id' => $reservation->customer_id,
            'issued_at' => now(), 'subtotal' => 1000,
            'tax' => 180, 'discount' => 0, 'total' => 1180,
            'paid_amount' => 0, 'balance' => 1180, 'status' => 'pending',
        ]);

        $this->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'paid_at' => now()->toDateTimeString(),
            'method' => 'cash',
            'amount' => 500,
        ])->assertRedirect();

        $this->assertSame('500.00', $invoice->fresh()->paid_amount);
        $this->put(route('invoices.update', $invoice), [
            'discount' => 900,
        ])->assertSessionHasErrors('discount');

        $receipt = Payment::query()->firstOrFail();
        $this->delete(route('payments.destroy', $receipt))->assertRedirect();
        $this->assertNotNull($receipt->fresh()->voided_at);
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame('0.00', $invoice->fresh()->paid_amount);
        $this->assertSame('1180.00', $invoice->fresh()->balance);

        $this->delete(route('payments.destroy', $receipt))
            ->assertSessionHasErrors('payment');
    }
}
