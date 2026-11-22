<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalAcceptance;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Support\Commercial\ContractIntegrity;
use App\Support\Commercial\ContractVersions;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContractIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Rental $rental;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create(['name' => 'Contrato SRL', 'slug' => 'contrato', 'currency' => 'DOP', 'timezone' => 'America/Santo_Domingo', 'status' => 'active']);
        $branch = Branch::query()->create(['company_id' => $company->getKey(), 'name' => 'Principal', 'code' => 'CTR', 'is_primary' => true, 'is_active' => true]);
        $this->operator = User::factory()->create(['company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'name' => 'Operador Contrato']);
        $this->operator->assignRole(RoleName::ADMINISTRATOR->value);

        app(TenantContext::class)->set($company, $branch);
        $customer = Customer::query()->create(['document_type' => 'passport', 'document_number' => 'CT-1', 'first_name' => 'Ana', 'last_name' => 'Prueba', 'phone' => '8095550002', 'status' => 'active']);
        $category = VehicleCategory::query()->create(['code' => 'CT', 'name' => 'Cat', 'daily_rate' => 1000, 'deposit_amount' => 0]);
        $brand = VehicleBrand::query()->create(['name' => 'MarcaCt']);
        $model = VehicleModel::query()->create(['vehicle_brand_id' => $brand->getKey(), 'name' => 'ModeloCt', 'year' => 2025]);
        $vehicle = Vehicle::query()->create(['vehicle_model_id' => $model->getKey(), 'vehicle_category_id' => $category->getKey(), 'code' => 'CT-1', 'plate' => 'CT0001', 'color' => 'Gris', 'mileage' => 1, 'status' => 'rented']);
        $this->rental = Rental::query()->create([
            'code' => 'ALQ-CT-1', 'customer_id' => $customer->getKey(), 'vehicle_id' => $vehicle->getKey(),
            'start_at' => now()->subDay(), 'expected_return_at' => now()->addDay(), 'opening_mileage' => 1,
            'daily_rate' => 1000, 'deposit_amount' => 0, 'subtotal' => 1000, 'fees' => 0, 'taxes' => 180,
            'total' => 1180, 'status' => 'open',
        ]);
    }

    private function accept(): void
    {
        $this->actingAs($this->operator)
            ->post(route('rentals.acceptance', $this->rental), ['signer_name' => 'Operador Contrato', 'acknowledge' => '1'])
            ->assertSessionHasNoErrors();
    }

    public function test_acceptance_stores_versioned_snapshot_and_verifies_as_valid(): void
    {
        $this->accept();

        $acceptance = RentalAcceptance::query()->firstOrFail();
        $this->assertSame(ContractIntegrity::VERSION, (int) $acceptance->hash_version);
        $this->assertSame(64, strlen($acceptance->document_hash));
        $this->assertSame('1000.00', json_decode($acceptance->document_snapshot, true)['daily_rate']);
        $this->assertSame(ContractIntegrity::VALID, ContractIntegrity::status($acceptance));
    }

    public function test_any_economic_change_after_acceptance_is_detected(): void
    {
        $this->accept();

        foreach (['daily_rate' => 900, 'fees' => 50, 'taxes' => 170, 'deposit_amount' => 500, 'total' => 1100] as $field => $value) {
            $this->rental->forceFill([$field => $value])->save();
            $this->assertSame(ContractIntegrity::MODIFIED, ContractIntegrity::status(RentalAcceptance::query()->firstOrFail()->refresh()), "No detectó cambio en {$field}");
            $this->rental->refresh()->forceFill([$field => $field === 'daily_rate' ? 1000 : ($field === 'taxes' ? 180 : ($field === 'total' ? 1180 : 0))])->save();
        }

        $this->assertSame(ContractIntegrity::VALID, ContractIntegrity::status(RentalAcceptance::query()->firstOrFail()->refresh()));
    }

    public function test_legacy_acceptances_are_labelled_not_verified(): void
    {
        $this->accept();
        RentalAcceptance::query()->update(['hash_version' => 1]);

        $this->assertSame(ContractIntegrity::LEGACY, ContractIntegrity::status(RentalAcceptance::query()->firstOrFail()));
    }

    public function test_rental_page_shows_integrity_and_never_claims_certified_signature(): void
    {
        $this->accept();

        $html = $this->actingAs($this->operator)->get(route('rentals.show', $this->rental))->assertOk()->getContent();

        $this->assertStringContainsString('Integridad verificada', $html);
        $this->assertStringNotContainsString('firma digital certificada del cliente válida', $html);

        $this->rental->forceFill(['total' => 9999])->save();
        $this->actingAs($this->operator)->get(route('rentals.show', $this->rental))->assertSee('cambiaron después del acuse');
    }

    public function test_closed_rental_keeps_agreed_terms_valid_but_flags_changed_agreed_terms(): void
    {
        $this->accept();

        // Cierre legítimo: cambian importes finales, no las condiciones pactadas.
        $this->rental->forceFill(['status' => 'closed', 'total' => 1500, 'taxes' => 270, 'fees' => 100])->save();
        $this->assertSame(ContractIntegrity::CLOSED, ContractIntegrity::status(RentalAcceptance::query()->firstOrFail()->refresh()));

        $this->rental->forceFill(['daily_rate' => 5])->save();
        $this->assertSame(ContractIntegrity::MODIFIED, ContractIntegrity::status(RentalAcceptance::query()->firstOrFail()->refresh()));
    }

    public function test_versions_are_recorded_only_on_change_with_a_diff_and_are_immutable(): void
    {
        $first = ContractVersions::record($this->rental, 'apertura');
        $this->assertSame(1, $first->version);
        $this->assertNull(ContractVersions::record($this->rental, 'cierre'), 'Sin cambios no se crea versión.');

        $this->rental->forceFill(['fees' => 200, 'total' => 1400])->save();
        $second = ContractVersions::record($this->rental->refresh(), 'cierre');

        $this->assertSame(2, $second->version);
        $this->assertSame(['fees', 'total'], array_keys(ContractVersions::diff($first, $second)));

        $this->expectException(\LogicException::class);
        $second->update(['reason' => 'manipulado']);
    }

    public function test_versions_cannot_be_deleted(): void
    {
        $version = ContractVersions::record($this->rental, 'apertura');

        $this->expectException(\LogicException::class);
        $version->delete();
    }

    public function test_contract_pdf_downloads_with_integrity_evidence_and_history_is_listed(): void
    {
        $this->accept();
        ContractVersions::record($this->rental, 'apertura');

        $response = $this->actingAs($this->operator)->get(route('rentals.contract.download', $this->rental));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->actingAs($this->operator)->get(route('rentals.show', $this->rental))
            ->assertOk()->assertSee('Historial del contrato')->assertSee('Descargar PDF');
    }

    public function test_printable_contract_page_works_under_csp_with_nonce_style_and_no_inline_handler(): void
    {
        $response = $this->actingAs($this->operator)->get(route('rentals.contract', $this->rental))->assertOk();
        $html = $response->getContent();
        $header = (string) $response->headers->get('Content-Security-Policy');

        preg_match("/style-src-elem [^;]*'nonce-([^']+)'/", $header, $nonce);
        $this->assertNotEmpty($nonce, 'style-src-elem debe llevar nonce.');
        $this->assertStringContainsString('<style nonce="'.$nonce[1].'">', $html);
        $this->assertStringNotContainsString('onclick=', $html);
    }
}
