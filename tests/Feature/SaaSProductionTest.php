<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Jobs\EnforceSubscriptionLifecycleJob;
use App\Models\BackupSnapshot;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Production\BackupManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SaaSProductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_public_responses_include_security_and_correlation_headers(): void
    {
        $response = $this->withHeader('X-Request-Id', 'test-request-123456')
            ->get(route('home'));

        $response
            ->assertOk()
            ->assertHeader('X-Request-Id', 'test-request-123456')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_readiness_checks_database_cache_storage_queue_and_backup_registry(): void
    {
        $response = $this->get(route('health.ready'));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.healthy', true)
            ->assertJsonPath('checks.cache.healthy', true)
            ->assertJsonPath('checks.private_storage.healthy', true)
            ->assertJsonPath('checks.queue.healthy', true)
            ->assertJsonPath('checks.backup.healthy', true);
    }

    public function test_expired_subscription_grace_blocks_tenant_even_when_company_is_active(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);
        $company = $administrator->company;

        $company->subscriptions()->create([
            'plan_code' => 'starter',
            'status' => 'past_due',
            'billing_cycle' => 'monthly',
            'provider' => 'manual',
            'started_at' => now()->subMonth(),
            'grace_ends_at' => now()->subMinute(),
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_subscription_lifecycle_suspends_expired_trial(): void
    {
        $company = Company::query()->create([
            'name' => 'Trial vencido',
            'slug' => 'trial-vencido',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'trial',
            'plan_code' => 'starter',
            'trial_ends_at' => now()->subMinute(),
        ]);

        $subscription = $company->subscriptions()->create([
            'plan_code' => 'starter',
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'provider' => 'manual',
            'started_at' => now()->subDays(14),
            'trial_ends_at' => now()->subMinute(),
        ]);

        (new EnforceSubscriptionLifecycleJob)->handle();

        $this->assertSame('expired', $subscription->fresh()->status);
        $this->assertSame('suspended', $company->fresh()->status);
    }

    public function test_backup_is_encrypted_verified_and_can_restore_business_data(): void
    {
        Storage::fake('local');

        config([
            'rentadrive.storage.backup_disk' => 'local',
            'rentadrive.backup.require_external_in_production' => false,
        ]);

        $administrator = User::factory()->create();
        $originalCompanyName = $administrator->company->name;

        /** @var BackupManager $backups */
        $backups = app(BackupManager::class);
        $snapshot = $backups->create();

        $this->assertSame('completed', $snapshot->status);
        $this->assertNotNull($snapshot->verified_at);
        $this->assertNotNull($snapshot->checksum_sha256);
        Storage::disk('local')->assertExists($snapshot->path);

        $stored = Storage::disk('local')->get($snapshot->path);
        $this->assertStringNotContainsString($originalCompanyName, $stored);

        $administrator->company->update(['name' => 'Nombre alterado']);
        $this->assertDatabaseHas('companies', [
            'id' => $administrator->company_id,
            'name' => 'Nombre alterado',
        ]);

        $backups->restore($snapshot);

        $this->assertDatabaseHas('companies', [
            'id' => $administrator->company_id,
            'name' => $originalCompanyName,
        ]);

        $this->assertTrue($backups->verify(BackupSnapshot::query()->findOrFail($snapshot->getKey())));
    }

    public function test_trial_onboarding_creates_subscription_record(): void
    {
        $platformAdmin = User::factory()->create([
            'company_id' => null,
            'branch_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->actingAs($platformAdmin)
            ->post(route('platform.companies.store'), [
                'name' => 'SaaS Rent RD',
                'legal_name' => 'SaaS Rent RD SRL',
                'rnc' => '131000099',
                'slug' => 'saas-rent-rd',
                'email' => 'hola@saas-rent.test',
                'phone' => '809-555-0200',
                'currency' => 'DOP',
                'timezone' => 'America/Santo_Domingo',
                'plan_code' => 'professional',
                'status' => 'trial',
                'branch_name' => 'Principal',
                'branch_code' => 'MAIN',
                'branch_city' => 'Santo Domingo',
                'admin_name' => 'Admin SaaS',
                'admin_email' => 'admin@saas-rent.test',
                'admin_password' => 'Password123!',
                'admin_password_confirmation' => 'Password123!',
            ])
            ->assertRedirect();

        $company = Company::query()->where('slug', 'saas-rent-rd')->firstOrFail();
        $subscription = Subscription::query()
            ->where('company_id', $company->getKey())
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('trialing', $subscription->status);
        $this->assertSame('professional', $subscription->plan_code);
        $this->assertTrue($subscription->trial_ends_at?->isFuture() ?? false);
        $this->assertNull($company->onboarding_completed_at);
    }
}
