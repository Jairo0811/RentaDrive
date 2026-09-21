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

final class PlatformAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_platform_admin_without_tenant_can_access_platform_dashboard(): void
    {
        $platformAdmin = $this->platformAdmin();

        $this->actingAs($platformAdmin)
            ->get(route('platform.dashboard'))
            ->assertOk();
    }

    public function test_tenant_administrator_cannot_access_platform_backoffice(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->get(route('platform.dashboard'))
            ->assertForbidden();
    }

    public function test_platform_admin_does_not_enter_tenant_dashboard(): void
    {
        $platformAdmin = $this->platformAdmin();

        $this->actingAs($platformAdmin)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_platform_admin_can_onboard_company_with_primary_branch_and_administrator(): void
    {
        $platformAdmin = $this->platformAdmin();

        $this->actingAs($platformAdmin)
            ->post(route('platform.companies.store'), [
                'name' => 'Quisqueya Rent a Car',
                'legal_name' => 'Quisqueya Mobility SRL',
                'rnc' => '131000001',
                'slug' => 'quisqueya-rent-a-car',
                'email' => 'hola@quisqueya.test',
                'phone' => '809-555-0101',
                'currency' => 'DOP',
                'timezone' => 'America/Santo_Domingo',
                'branch_name' => 'Sucursal Principal',
                'branch_code' => 'PRINCIPAL',
                'branch_city' => 'Santo Domingo',
                'admin_name' => 'Administrador Quisqueya',
                'admin_email' => 'admin@quisqueya.test',
                'admin_password' => 'Password123!',
                'admin_password_confirmation' => 'Password123!',
            ])
            ->assertRedirect();

        $company = Company::query()->where('slug', 'quisqueya-rent-a-car')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->getKey())->where('is_primary', true)->firstOrFail();
        $administrator = User::query()->where('email', 'admin@quisqueya.test')->firstOrFail();

        $this->assertSame('active', $company->status);
        $this->assertSame($company->getKey(), $branch->company_id);
        $this->assertSame($company->getKey(), $administrator->company_id);
        $this->assertSame($branch->getKey(), $administrator->branch_id);
        $this->assertFalse($administrator->is_platform_admin);
        $this->assertTrue($administrator->hasRole(RoleName::ADMINISTRATOR->value));
    }

    public function test_suspending_company_blocks_tenant_access(): void
    {
        $platformAdmin = $this->platformAdmin();
        $tenantAdmin = User::factory()->create();
        $tenantAdmin->assignRole(RoleName::ADMINISTRATOR->value);
        $company = $tenantAdmin->company;

        $this->actingAs($platformAdmin)
            ->patch(route('platform.companies.suspend', $company))
            ->assertRedirect();

        $this->actingAs($tenantAdmin->fresh())
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_platform_admin_login_redirects_to_platform_dashboard(): void
    {
        $platformAdmin = $this->platformAdmin();

        $this->post(route('login'), [
            'email' => $platformAdmin->email,
            'password' => 'password',
        ])->assertRedirect(route('platform.dashboard', absolute: false));
    }

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'company_id' => null,
            'branch_id' => null,
            'is_platform_admin' => true,
        ]);
    }
}
