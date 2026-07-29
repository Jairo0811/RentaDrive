<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class FoundationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_every_configured_role_can_open_the_dashboard(): void
    {
        foreach (RoleName::cases() as $role) {
            $user = User::factory()->create();
            $user->assignRole($role->value);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertSee('Gestiona tu flota. Impulsa tu negocio.');
        }
    }

    public function test_user_without_dashboard_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_only_administrator_can_delete_another_user(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $agent = User::factory()->create();
        $agent->assignRole(RoleName::RENTAL_AGENT->value);

        $target = User::factory()->create();

        $this->assertTrue(Gate::forUser($administrator)->allows('delete', $target));
        $this->assertFalse(Gate::forUser($agent)->allows('delete', $target));
        $this->assertFalse(Gate::forUser($administrator)->allows('delete', $administrator));
    }

    public function test_administration_menu_is_hidden_from_inspector(): void
    {
        $inspector = User::factory()->create();
        $inspector->assignRole(RoleName::INSPECTOR->value);

        $this->actingAs($inspector)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Administración');
    }
}
