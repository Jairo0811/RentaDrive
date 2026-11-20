<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PaymentsEmptyOpenInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_form_is_replaced_by_a_message_when_no_invoice_has_balance(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->get('/payments')
            ->assertOk()
            ->assertSee('No hay facturas con balance pendiente')
            ->assertDontSee('Nuevo pago')
            ->assertDontSee('Aplicar pago');
    }
}
