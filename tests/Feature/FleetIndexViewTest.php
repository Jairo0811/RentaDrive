<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FleetIndexViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function administrator(): User
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        return $administrator;
    }

    public function test_fleet_index_shows_status_filters_and_view_switcher(): void
    {
        $this->actingAs($this->administrator())
            ->get('/vehicles')
            ->assertOk()
            ->assertSee('Filtrar por estado', false)
            ->assertSee('Mantenimiento')
            ->assertSee('Tarjetas');
    }

    public function test_fleet_index_accepts_whitelisted_sort_and_card_view(): void
    {
        $this->actingAs($this->administrator())
            ->get('/vehicles?view=cards&sort=mileage&dir=asc&status=available')
            ->assertOk();
    }

    public function test_fleet_index_ignores_unknown_sort_columns(): void
    {
        $this->actingAs($this->administrator())
            ->get('/vehicles?sort=password&dir=sideways&view=other')
            ->assertOk();
    }
}
