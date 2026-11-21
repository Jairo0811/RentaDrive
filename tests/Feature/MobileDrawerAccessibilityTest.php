<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MobileDrawerAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_drawer_is_inert_when_closed_and_manages_focus(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(':inert="! desktop && ! sidebarOpen"', false)
            ->assertSee(':inert="! desktop && sidebarOpen"', false)
            ->assertSee('x-ref="menuToggle"', false)
            ->assertSee('x-ref="drawerClose"', false);
    }
}
