<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\Security\Mfa\Totp;
use App\Support\Security\Mfa\TwoFactorAuthentication;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlatformMfaResetTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $companyAdmin;

    private User $platform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->company = Company::query()->create(['name' => 'Reset SRL', 'slug' => 'reset', 'currency' => 'DOP', 'timezone' => 'America/Santo_Domingo', 'status' => 'active']);
        $branch = Branch::query()->create(['company_id' => $this->company->getKey(), 'name' => 'Principal', 'code' => 'RST', 'is_primary' => true, 'is_active' => true]);

        $this->companyAdmin = User::factory()->create(['company_id' => $this->company->getKey(), 'branch_id' => $branch->getKey(), 'name' => 'Admin Perdido']);
        $this->companyAdmin->assignRole(RoleName::ADMINISTRATOR->value);
        $mfa = app(TwoFactorAuthentication::class);
        $secret = $mfa->beginSetup($this->companyAdmin);
        $mfa->confirmSetup($this->companyAdmin->refresh(), (new Totp)->codeAt($secret, time()));

        $this->platform = User::factory()->create(['company_id' => null, 'branch_id' => null, 'is_platform_admin' => true]);
    }

    private function url(?Company $company = null, ?User $user = null): string
    {
        return route('platform.companies.users.mfa-reset', [$company ?? $this->company, $user ?? $this->companyAdmin]);
    }

    public function test_platform_admin_resets_company_administrator_mfa_with_audit_of_the_actor(): void
    {
        $this->assertTrue($this->companyAdmin->refresh()->hasTwoFactorEnabled());

        $this->actingAs($this->platform)->withSession(['auth.password_confirmed_at' => time()])
            ->post($this->url())->assertRedirect()->assertSessionHas('status');

        $admin = $this->companyAdmin->refresh();
        $this->assertFalse($admin->hasTwoFactorEnabled());
        $this->assertNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_recovery_codes);

        $log = AuditLog::query()->withoutGlobalScopes()->where('event', 'mfa_reset_by_platform')->firstOrFail();
        $this->assertSame($this->platform->getKey(), (int) $log->user_id);
        $this->assertSame($this->companyAdmin->getKey(), (int) $log->auditable_id);
        $this->assertSame($this->company->getKey(), (int) $log->company_id);
    }

    public function test_reset_requires_recent_password_confirmation(): void
    {
        $this->actingAs($this->platform)->post($this->url())->assertRedirect(route('password.confirm'));

        $this->assertTrue($this->companyAdmin->refresh()->hasTwoFactorEnabled());
    }

    public function test_company_administrators_cannot_use_the_platform_reset(): void
    {
        $other = User::factory()->create(['company_id' => $this->company->getKey()]);
        $other->assignRole(RoleName::ADMINISTRATOR->value);

        $response = $this->actingAs($other)->withSession(['auth.password_confirmed_at' => time()])->post($this->url());

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $this->assertTrue($this->companyAdmin->refresh()->hasTwoFactorEnabled());
    }

    public function test_user_of_another_company_or_a_platform_admin_cannot_be_reset_through_this_company(): void
    {
        $otherCompany = Company::query()->create(['name' => 'Otra', 'slug' => 'otra', 'currency' => 'DOP', 'timezone' => 'America/Santo_Domingo', 'status' => 'active']);

        $this->actingAs($this->platform)->withSession(['auth.password_confirmed_at' => time()])
            ->post($this->url($otherCompany))->assertNotFound();
        $this->assertTrue($this->companyAdmin->refresh()->hasTwoFactorEnabled());
    }

    public function test_company_page_lists_administrators_with_the_reset_action(): void
    {
        $this->actingAs($this->platform)->get(route('platform.companies.edit', $this->company))
            ->assertOk()->assertSee('Admin Perdido')->assertSee('Restablecer MFA');
    }
}
