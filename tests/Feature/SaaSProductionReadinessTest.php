<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\CompanyLifecycleEvent;
use App\Models\User;
use App\Support\Commercial\CompanyLifecycle;
use App\Support\Operations\ProductionReadiness;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SaaSProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_trial_is_suspended_exactly_once_and_denies_tenant_access(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(RoleName::ADMINISTRATOR->value);
        $company = $user->company;
        $company->update([
            'status' => 'trial',
            'trial_ends_at' => now()->subMinute(),
            'plan_code' => 'starter',
        ]);

        $lifecycle = app(CompanyLifecycle::class);
        $this->assertSame(1, $lifecycle->expireTrials());
        $this->assertSame(0, $lifecycle->expireTrials());
        $this->assertSame('suspended', $company->fresh()->status);

        $event = CompanyLifecycleEvent::query()->firstOrFail();
        $this->assertSame('trial_expired', $event->reason);
        $this->assertSame('trial', $event->previous_status);
        $this->assertSame('suspended', $event->new_status);
        $this->assertSame(1, CompanyLifecycleEvent::query()->count());

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_active_trial_is_not_expired(): void
    {
        $company = User::factory()->create()->company;
        $company->update(['status' => 'trial', 'trial_ends_at' => now()->addDays(3)]);

        $this->assertSame(0, app(CompanyLifecycle::class)->expireTrials());
        $this->assertSame('trial', $company->fresh()->status);
        $this->assertSame(0, CompanyLifecycleEvent::query()->count());
    }

    public function test_platform_admin_plan_change_creates_history_without_fake_payment(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $company = User::factory()->create()->company;
        $company->update(['status' => 'active', 'plan_code' => 'starter']);
        $platform = User::factory()->create([
            'company_id' => null,
            'branch_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->actingAs($platform)->put(route('platform.companies.update', $company), [
            'name' => $company->name,
            'slug' => $company->slug,
            'currency' => $company->currency,
            'timezone' => $company->timezone,
            'plan_code' => 'professional',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertSame('professional', $company->fresh()->plan_code);
        $event = CompanyLifecycleEvent::query()->firstOrFail();
        $this->assertSame('platform_update', $event->reason);
        $this->assertSame('starter', $event->previous_plan);
        $this->assertSame('professional', $event->new_plan);
        $this->assertSame($platform->getKey(), $event->actor_id);

        $this->actingAs(User::factory()->create())
            ->get(route('platform.companies.edit', $company))->assertForbidden();
    }

    public function test_production_preflight_detects_insecure_configuration(): void
    {
        config()->set([
            'app.env' => 'local',
            'app.debug' => true,
            'app.url' => 'http://example.test',
            'app.key' => '',
            'session.secure' => false,
            'session.encrypt' => false,
            'database.default' => 'sqlsrv',
            'database.connections.sqlsrv.encrypt' => 'no',
            'database.connections.sqlsrv.trust_server_certificate' => 'true',
            'queue.default' => 'sync',
            'cache.default' => 'array',
            'rentadrive.automation.enabled' => true,
            'mail.default' => 'log',
        ]);

        $problems = app(ProductionReadiness::class)->configurationProblems();
        $this->assertGreaterThanOrEqual(8, count($problems));
    }

    public function test_production_preflight_accepts_hardened_configuration(): void
    {
        config()->set([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://rentadrive.example.test',
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'session.secure' => true,
            'session.encrypt' => true,
            'session.http_only' => true,
            'database.default' => 'sqlsrv',
            'database.connections.sqlsrv.encrypt' => 'yes',
            'database.connections.sqlsrv.trust_server_certificate' => 'false',
            'queue.default' => 'database',
            'cache.default' => 'database',
            'rentadrive.automation.enabled' => false,
        ]);

        $this->assertSame([], app(ProductionReadiness::class)->configurationProblems());
    }

    public function test_security_headers_are_added_to_public_web_responses(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }
}
