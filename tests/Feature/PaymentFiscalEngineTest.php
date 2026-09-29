<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Operations\Services\RentalWorkflowService;
use App\Domain\Security\Enums\RoleName;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FiscalSequence;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Support\Fiscal\FiscalDocumentService;
use App\Support\Payments\PaymentLedgerService;
use App\Support\Payments\PaymentReconciliationService;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PaymentFiscalEngineTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Branch $branch;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->company = Company::query()->firstOrFail();
        $this->branch = $this->company->branches()->firstOrFail();

        $this->administrator = User::factory()->create([
            'company_id' => $this->company->getKey(),
            'branch_id' => $this->branch->getKey(),
        ]);
        $this->administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($this->administrator);
        app(TenantContext::class)->set($this->company, $this->branch);
    }

    public function test_partial_payment_refund_and_reconciliation_preserve_ledger(): void
    {
        $invoice = $this->closedRentalInvoice();

        $payment = app(PaymentLedgerService::class)->record([
            'invoice_id' => $invoice->getKey(),
            'paid_at' => now(),
            'method' => 'transfer',
            'reference' => 'BANK-001',
            'amount' => 1000,
            'gateway' => 'manual',
            'currency' => 'DOP',
            'status' => 'completed',
            'received_by' => $this->administrator->getKey(),
        ]);

        $invoice->refresh();
        $this->assertSame(1000.0, (float) $invoice->paid_amount);

        $refund = app(PaymentLedgerService::class)->refund($payment, 400, 'Ajuste de prueba');

        $this->assertSame('completed', $refund->status);
        $this->assertSame('partial_refund', $payment->fresh()->status);
        $this->assertSame(600.0, $payment->fresh()->netAmount());
        $this->assertSame(600.0, (float) $invoice->fresh()->paid_amount);

        $invoice->update(['paid_amount' => 9999, 'balance' => 0]);

        $result = app(PaymentReconciliationService::class)->reconcile(true);

        $this->assertSame(1, $result['invoice_mismatches']);
        $this->assertSame(600.0, (float) $invoice->fresh()->paid_amount);
        $this->assertSame((float) $invoice->total - 600.0, (float) $invoice->fresh()->balance);
        $this->assertDatabaseCount('payment_refunds', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_webhook_is_idempotent_and_updates_reservation_deposit(): void
    {
        $customer = $this->customer('passport', 'PAY-WEBHOOK');
        $vehicle = $this->vehicle('WEB');

        $reservation = Reservation::query()->create([
            'code' => 'RES-WEBHOOK',
            'customer_id' => $customer->getKey(),
            'vehicle_category_id' => $vehicle->vehicle_category_id,
            'vehicle_id' => $vehicle->getKey(),
            'start_at' => now()->addDays(4),
            'end_at' => now()->addDays(7),
            'pickup_location' => $this->branch->name,
            'return_location' => $this->branch->name,
            'daily_rate' => 2500,
            'estimated_total' => 7500,
            'deposit_required' => 1500,
            'deposit_paid' => 0,
            'status' => 'pending',
        ]);

        $intent = PaymentIntent::query()->create([
            'branch_id' => $this->branch->getKey(),
            'reservation_id' => $reservation->getKey(),
            'public_id' => 'pi_test_001',
            'purpose' => 'reservation_deposit',
            'gateway' => 'hosted',
            'idempotency_key' => 'deposit-webhook-test',
            'amount' => 1500,
            'currency' => 'DOP',
            'status' => 'pending',
        ]);

        app(TenantContext::class)->clear();

        config(['services.payment_gateway.webhook_secret' => 'webhook-secret']);

        $payload = [
            'event_id' => 'evt-001',
            'type' => 'payment.captured',
            'data' => [
                'payment_intent_id' => $intent->public_id,
                'transaction_id' => 'txn-001',
                'amount' => 1500,
            ],
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'webhook-secret');

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->call(
                'POST',
                '/api/webhooks/payments/hosted',
                [],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_X_RENTADRIVE_SIGNATURE' => $signature,
                ],
                $raw,
            )->assertOk();
        }

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_webhook_events', 1);

        app(TenantContext::class)->set($this->company, $this->branch);

        $this->assertSame(1500.0, (float) $reservation->fresh()->deposit_paid);
        $this->assertSame('paid', $intent->fresh()->status);

        $payment = Payment::query()->firstOrFail();
        $this->assertSame('txn-001', $payment->gateway_transaction_id);
        $this->assertSame('hosted', $payment->gateway);
    }

    public function test_paper_consumption_ncf_is_allocated_from_authorized_sequence(): void
    {
        $this->company->update([
            'settings' => [
                'fiscal' => [
                    'enabled' => true,
                    'mode' => 'paper',
                    'rnc' => '101000001',
                    'legal_name' => 'RentaDrive Pruebas SRL',
                    'address' => 'Santo Domingo',
                ],
            ],
        ]);

        FiscalSequence::query()->create([
            'document_type' => 'B02',
            'prefix' => 'B02',
            'next_number' => 1,
            'end_number' => 100,
            'sequence_length' => 8,
            'is_active' => true,
        ]);

        $invoice = $this->closedRentalInvoice('passport', 'PAPER-001');
        $issued = app(FiscalDocumentService::class)->issue($invoice, 'B02');

        $this->assertSame('B0200000001', $issued->ncf);
        $this->assertSame('issued', $issued->fiscal_status);
        $this->assertSame(18.0, (float) $issued->tax_rate);
        $this->assertNotNull($issued->fiscal_issued_at);
        $this->assertSame(2, FiscalSequence::query()->where('document_type', 'B02')->firstOrFail()->next_number);
    }

    public function test_electronic_credit_fiscal_document_uses_hosted_provider(): void
    {
        Http::fake([
            '*/documents' => Http::response([
                'status' => 'accepted',
                'reference' => 'DGII-TRACK-001',
            ]),
        ]);

        config([
            'services.fiscal_gateway.base_url' => 'https://fiscal-adapter.test',
            'services.fiscal_gateway.token' => 'test-token',
        ]);

        $this->company->update([
            'settings' => [
                'fiscal' => [
                    'enabled' => true,
                    'mode' => 'electronic',
                    'authorized_electronic_issuer' => true,
                    'rnc' => '101000001',
                    'legal_name' => 'RentaDrive Electrónico SRL',
                    'address' => 'Santo Domingo',
                ],
            ],
        ]);

        FiscalSequence::query()->create([
            'document_type' => 'E31',
            'prefix' => 'E31',
            'next_number' => 1,
            'end_number' => 100,
            'sequence_length' => 10,
            'is_active' => true,
        ]);

        $invoice = $this->closedRentalInvoice('rnc', '131000001');
        $issued = app(FiscalDocumentService::class)->issue($invoice, 'E31');

        $this->assertSame('E310000000001', $issued->ncf);
        $this->assertSame('accepted', $issued->fiscal_status);
        $this->assertSame('DGII-TRACK-001', $issued->fiscal_reference);
        $this->assertSame('hosted', $issued->fiscal_provider);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://fiscal-adapter.test/documents'
            && $request['document_type'] === 'E31'
            && $request['ncf'] === 'E310000000001');
    }

    private function closedRentalInvoice(
        string $documentType = 'passport',
        string $documentNumber = 'RENTAL-001',
    ): Invoice {
        $customer = $this->customer($documentType, $documentNumber);
        $vehicle = $this->vehicle('INV'.substr(md5($documentNumber), 0, 4));

        $rental = app(RentalWorkflowService::class)->open([
            'customer_id' => $customer->getKey(),
            'vehicle_id' => $vehicle->getKey(),
            'start_at' => now()->subDays(3)->format('Y-m-d H:i:s'),
            'expected_return_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'opening_mileage' => 100,
            'fuel_out' => 100,
            'daily_rate' => 2500,
            'deposit_amount' => 5000,
            'fees' => 0,
        ]);

        app(RentalWorkflowService::class)->close($rental, [
            'returned_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'closing_mileage' => 300,
            'fuel_in' => 100,
            'fees' => 0,
            'vehicle_status' => 'available',
            'notes' => null,
        ]);

        return $rental->invoice()->firstOrFail();
    }

    private function customer(string $documentType, string $documentNumber): Customer
    {
        return Customer::query()->create([
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'first_name' => 'Cliente',
            'last_name' => 'Prueba',
            'email' => strtolower(str_replace(['-', ' '], '', $documentNumber)).'@example.com',
            'phone' => '8095550101',
            'status' => 'active',
        ]);
    }

    private function vehicle(string $suffix): Vehicle
    {
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

        return Vehicle::query()->create([
            'vehicle_model_id' => $model->getKey(),
            'vehicle_category_id' => $category->getKey(),
            'code' => 'VEH-'.$suffix,
            'plate' => 'P'.$suffix,
            'color' => 'Azul',
            'transmission' => 'automatic',
            'fuel_type' => 'gasoline',
            'seats' => 5,
            'mileage' => 100,
            'status' => 'available',
        ]);
    }
}
