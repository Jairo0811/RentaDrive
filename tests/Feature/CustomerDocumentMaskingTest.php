<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\Customer;
use App\Models\User;
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
        return Customer::query()->create([
            'document_type' => 'cedula',
            'document_number' => $document,
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'phone' => '809-555-0000',
            'status' => 'active',
        ]);
    }

    public function test_masked_document_keeps_only_the_last_four_characters(): void
    {
        $this->assertSame('•••••••8205', $this->customer('00113918205')->maskedDocumentNumber());
        $this->assertSame('8205', $this->customer('00113918205')->documentLastDigits());
    }

    public function test_short_documents_are_fully_masked(): void
    {
        $customer = $this->customer('AB12');

        $this->assertSame('••••', $customer->maskedDocumentNumber());
        $this->assertSame('', $customer->documentLastDigits());
    }

    public function test_customer_list_does_not_print_the_full_document_number(): void
    {
        $this->customer('00113918205');
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->get('/customers')
            ->assertOk()
            ->assertSee('•••••••8205')
            ->assertSee('Documento terminado en 8205')
            ->assertDontSee('00113918205');
    }

    public function test_customer_detail_still_shows_the_full_document_number(): void
    {
        $customer = $this->customer('00113918205');
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('00113918205');
    }
}
