<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdministrativeContinuityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_last_tenant_administrator_cannot_delete_own_profile(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'account')
            ->assertRedirect('/profile');

        $this->assertAuthenticatedAs($administrator);
        $this->assertNotNull($administrator->fresh());
    }

    public function test_last_tenant_administrator_cannot_be_demoted_or_deactivated(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $payload = [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => RoleName::MANAGER->value,
            'branch_id' => $administrator->branch_id,
            'is_active' => true,
        ];

        $this->actingAs($administrator)
            ->put(route('users.update', $administrator), $payload)
            ->assertSessionHasErrors('role');

        $administrator->refresh();
        $this->assertTrue($administrator->is_active);
        $this->assertTrue($administrator->hasRole(RoleName::ADMINISTRATOR->value));

        $payload['role'] = RoleName::ADMINISTRATOR->value;
        $payload['is_active'] = false;

        $this->put(route('users.update', $administrator), $payload)
            ->assertSessionHasErrors('role');

        $administrator->refresh();
        $this->assertTrue($administrator->is_active);
        $this->assertTrue($administrator->hasRole(RoleName::ADMINISTRATOR->value));
    }

    public function test_administrator_can_be_demoted_when_another_active_administrator_exists(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $replacement = User::factory()->create([
            'company_id' => $administrator->company_id,
            'branch_id' => $administrator->branch_id,
        ]);
        $replacement->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->put(route('users.update', $administrator), [
                'name' => $administrator->name,
                'email' => $administrator->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => RoleName::MANAGER->value,
                'branch_id' => $administrator->branch_id,
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue($administrator->fresh()->hasRole(RoleName::MANAGER->value));
        $this->assertTrue($replacement->fresh()->hasRole(RoleName::ADMINISTRATOR->value));
    }

    public function test_last_platform_administrator_cannot_delete_own_profile(): void
    {
        $platformAdministrator = User::factory()->create([
            'company_id' => null,
            'branch_id' => null,
            'is_platform_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($platformAdministrator)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'account')
            ->assertRedirect('/profile');

        $this->assertAuthenticatedAs($platformAdministrator);
        $this->assertNotNull($platformAdministrator->fresh());
    }
}
