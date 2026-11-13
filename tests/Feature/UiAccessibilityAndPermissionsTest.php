<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UiAccessibilityAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    public function test_empty_fleet_hides_create_call_to_action_without_manage_permission(): void
    {
        $this->actingAs($this->userWithRole(RoleName::MANAGER))
            ->get('/vehicles')
            ->assertOk()
            ->assertSee('No hay vehículos')
            ->assertDontSee('Nuevo vehículo');
    }

    public function test_empty_fleet_shows_create_call_to_action_with_manage_permission(): void
    {
        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->get('/vehicles')
            ->assertOk()
            ->assertSee('Nuevo vehículo');
    }

    public function test_empty_customers_hides_create_call_to_action_without_manage_permission(): void
    {
        $this->actingAs($this->userWithRole(RoleName::MANAGER))
            ->get('/customers')
            ->assertOk()
            ->assertSee('Aún no hay clientes')
            ->assertDontSee('Nuevo cliente');
    }

    public function test_empty_reservations_and_rentals_hide_call_to_action_without_manage_permission(): void
    {
        $manager = $this->userWithRole(RoleName::MANAGER);

        $this->actingAs($manager)->get('/reservations')->assertOk()->assertDontSee('Nueva reserva');
        $this->actingAs($manager)->get('/rentals')->assertOk()->assertDontSee('Abrir alquiler');
    }

    public function test_list_filters_expose_programmatic_labels(): void
    {
        $administrator = $this->userWithRole(RoleName::ADMINISTRATOR);

        foreach ([
            '/customers' => ['customer-search', 'customer-status'],
            '/reservations' => ['reservation-search', 'reservation-status'],
            '/rentals' => ['rental-search', 'rental-status'],
            '/invoices' => ['invoice-search', 'invoice-status'],
            '/payments' => ['payment-method-filter'],
            '/vehicles' => ['fleet-search', 'fleet-category'],
        ] as $uri => $controls) {
            $response = $this->actingAs($administrator)->get($uri)->assertOk();

            foreach ($controls as $id) {
                $response->assertSee('for="'.$id.'"', false)->assertSee('id="'.$id.'"', false);
            }
        }
    }
}
