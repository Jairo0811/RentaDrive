<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_authenticated_user_requires_an_active_company(): void
    {
        $user = User::factory()->create([
            'company_id' => null,
            'branch_id' => null,
        ]);
        $user->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_authenticated_user_with_tenant_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_administrator_cannot_edit_user_from_another_company(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $otherCompany = Company::query()->create([
            'name' => 'Otra Rent a Car',
            'slug' => 'otra-rent-a-car',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
        ]);

        $otherBranch = Branch::query()->create([
            'company_id' => $otherCompany->getKey(),
            'name' => 'Principal',
            'code' => 'PRINCIPAL',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->getKey(),
            'branch_id' => $otherBranch->getKey(),
        ]);

        $this->actingAs($administrator)
            ->get(route('users.edit', $otherUser))
            ->assertNotFound();
    }

    public function test_created_user_can_be_assigned_to_an_active_branch_from_same_company(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $secondaryBranch = Branch::query()->create([
            'company_id' => $administrator->company_id,
            'name' => 'Aeropuerto',
            'code' => 'AEROPUERTO',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $this->actingAs($administrator)
            ->post(route('users.store'), [
                'name' => 'Agente Comercial',
                'email' => 'agente@rentadrive.test',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => RoleName::RENTAL_AGENT->value,
                'branch_id' => $secondaryBranch->getKey(),
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $created = User::query()->where('email', 'agente@rentadrive.test')->firstOrFail();

        $this->assertSame($administrator->company_id, $created->company_id);
        $this->assertSame($secondaryBranch->getKey(), $created->branch_id);
    }

    public function test_user_cannot_be_assigned_to_branch_from_another_company(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $otherCompany = Company::query()->create([
            'name' => 'Tenant Ajeno',
            'slug' => 'tenant-ajeno',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
        ]);

        $otherBranch = Branch::query()->create([
            'company_id' => $otherCompany->getKey(),
            'name' => 'Ajena',
            'code' => 'AJENA',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->actingAs($administrator)
            ->post(route('users.store'), [
                'name' => 'Usuario Cruzado',
                'email' => 'cruzado@rentadrive.test',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => RoleName::RENTAL_AGENT->value,
                'branch_id' => $otherBranch->getKey(),
                'is_active' => true,
            ])
            ->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('users', ['email' => 'cruzado@rentadrive.test']);
    }

    public function test_user_creation_respects_commercial_plan_limit(): void
    {
        config(['rentadrive.plans.starter.max_users' => 1]);

        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);
        $administrator->company()->update(['plan_code' => 'starter']);

        $this->actingAs($administrator)
            ->post(route('users.store'), [
                'name' => 'Usuario Excedente',
                'email' => 'limite@rentadrive.test',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => RoleName::RENTAL_AGENT->value,
                'branch_id' => $administrator->branch_id,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('users');

        $this->assertDatabaseMissing('users', ['email' => 'limite@rentadrive.test']);
    }
}
