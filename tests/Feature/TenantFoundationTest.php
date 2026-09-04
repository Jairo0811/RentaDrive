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

    public function test_created_user_inherits_administrator_tenant(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->post(route('users.store'), [
                'name' => 'Agente Comercial',
                'email' => 'agente@rentadrive.test',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => RoleName::RENTAL_AGENT->value,
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $created = User::query()->where('email', 'agente@rentadrive.test')->firstOrFail();

        $this->assertSame($administrator->company_id, $created->company_id);
        $this->assertSame($administrator->branch_id, $created->branch_id);
    }
}
