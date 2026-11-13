<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerDocumentMaskingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function customer(string $document): Customer
    {
        return new Customer(['document_number' => $document]);
    }

    /**
     * @return array{0: User, 1: Customer}
     */
    private function tenantWithCustomer(string $document): array
    {
        $company = Company::query()->create([
            'name' => 'Rent a Car MASK',
            'slug' => 'mask',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
        ]);
        $branch = Branch::query()->create([
            'company_id' => $company->getKey(),
            'name' => 'Sucursal Principal',
            'code' => 'PRINCIPAL',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $context = app(TenantContext::class);
        $context->set($company, $branch);
        $customer = Customer::query()->create([
            'document_type' => 'other',
            'document_number' => $document,
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'phone' => '809-555-0000',
            'status' => 'active',
        ]);
        $context->clear();

        $administrator = User::factory()->create([
            'company_id' => $company->getKey(),
            'branch_id' => $branch->getKey(),
        ]);
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        return [$administrator, $customer];
    }

    public function test_masked_document_keeps_only_the_last_four_characters(): void
    {
        $customer = $this->customer('00113918205');

        $this->assertSame('•••••••8205', $customer->maskedDocumentNumber());
        $this->assertSame('8205', $customer->documentLastDigits());
    }

    public function test_short_documents_are_fully_masked(): void
    {
        $customer = $this->customer('AB12');

        $this->assertSame('••••', $customer->maskedDocumentNumber());
        $this->assertSame('', $customer->documentLastDigits());
    }

    public function test_customer_list_does_not_print_the_full_document_number(): void
    {
        [$administrator] = $this->tenantWithCustomer('00113918205');

        $this->actingAs($administrator)
            ->get('/customers')
            ->assertOk()
            ->assertSee('•••••••8205')
            ->assertSee('Documento terminado en 8205')
            ->assertDontSee('00113918205');
    }

    public function test_customer_detail_still_shows_the_full_document_number(): void
    {
        [$administrator, $customer] = $this->tenantWithCustomer('00113918205');

        $this->actingAs($administrator)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('00113918205');
    }
}
