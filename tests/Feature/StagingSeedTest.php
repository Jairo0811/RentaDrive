<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\Operations\StagingGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class StagingSeedTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG = 'Staging-Ficticia#2026x';

    public function test_guard_refuses_without_explicit_flag(): void
    {
        config()->set('rentadrive.staging.allow_seed', false);

        $this->assertStringContainsString('RENTADRIVE_ALLOW_STAGING_SEED', (string) StagingGuard::refusalReason());
    }

    public function test_guard_always_refuses_the_real_database_even_with_the_flag(): void
    {
        config()->set([
            'rentadrive.staging.allow_seed' => true,
            'database.default' => 'sqlsrv',
            'database.connections.sqlsrv.database' => 'RentaDriveDb',
        ]);

        $this->assertStringContainsString('RentaDriveDb', (string) StagingGuard::refusalReason());

        config()->set('database.connections.sqlsrv.database', 'rentadrivedb');
        $this->assertNotNull(StagingGuard::refusalReason());
    }

    public function test_guard_accepts_only_staging_like_database_names(): void
    {
        config()->set(['rentadrive.staging.allow_seed' => true, 'database.default' => 'sqlsrv']);

        foreach (['RentaDriveStaging', 'RentaDriveStagingCI', 'RentaDriveE2E', 'RentaDriveTest'] as $name) {
            config()->set('database.connections.sqlsrv.database', $name);
            $this->assertNull(StagingGuard::refusalReason(), $name);
        }

        foreach (['master', 'RentaDrive', 'Produccion', 'RentaDriveDb2'] as $name) {
            config()->set('database.connections.sqlsrv.database', $name);
            $this->assertNotNull(StagingGuard::refusalReason(), $name);
        }
    }

    public function test_staging_seed_creates_only_fictional_data_and_is_idempotent(): void
    {
        config()->set(['rentadrive.staging.allow_seed' => true, 'rentadrive.staging.admin_password' => self::STRONG]);

        $this->artisan('rentadrive:staging-seed')->assertExitCode(0);
        $this->artisan('rentadrive:staging-seed')->assertExitCode(0);

        $company = Company::query()->where('slug', 'staging-ficticia')->firstOrFail();
        $admin = User::query()->where('email', 'admin@staging.rentadrive.test')->firstOrFail();

        $this->assertSame($company->getKey(), $admin->company_id);
        $this->assertTrue(Hash::check(self::STRONG, $admin->password));
        $this->assertDatabaseMissing('users', ['email' => 'admin@rentadrive.com.do']);
        $this->assertSame(1, User::query()->where('email', 'admin@staging.rentadrive.test')->count());
    }

    public function test_staging_seed_rejects_weak_or_known_default_passwords(): void
    {
        config()->set('rentadrive.staging.allow_seed', true);

        foreach (['corta', 'RentaDrive123..', 'RentaDrivePlatform123!'] as $password) {
            config()->set('rentadrive.staging.admin_password', $password);
            $this->artisan('rentadrive:staging-seed')->assertExitCode(1);
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_staging_seed_rejects_the_predictable_demo_email(): void
    {
        config()->set(['rentadrive.staging.allow_seed' => true, 'rentadrive.staging.admin_password' => self::STRONG]);

        $this->artisan('rentadrive:staging-seed', ['--admin-email' => 'admin@rentadrive.com.do'])->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }
}
