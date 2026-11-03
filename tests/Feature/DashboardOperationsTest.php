<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_operational_indicators_and_alerts_without_data(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Devoluciones hoy')
            ->assertSee('En mantenimiento')
            ->assertSee('Ocupación')
            ->assertSee('Alertas operativas')
            ->assertSee('No hay devoluciones atrasadas.')
            ->assertSee('Sin reservas este mes.');
    }
}
