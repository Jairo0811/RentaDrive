<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HardeningConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_worker_only_caches_explicit_public_static_assets(): void
    {
        $serviceWorker = file_get_contents(public_path('sw.js'));

        $this->assertIsString($serviceWorker);
        $this->assertStringContainsString("'/build/assets/'", $serviceWorker);
        $this->assertStringContainsString("'/images/'", $serviceWorker);
        $this->assertStringContainsString("event.request.mode === 'navigate'", $serviceWorker);
        $this->assertStringNotContainsString("'/login'", $serviceWorker);
        $this->assertStringNotContainsString("caches.match('/login')", $serviceWorker);
    }

    public function test_default_database_seeder_omits_demo_operational_data_in_production(): void
    {
        $application = app();
        $originalEnvironment = $application->environment();
        $application->detectEnvironment(fn (): string => 'production');

        try {
            $this->artisan('db:seed', [
                '--class' => DatabaseSeeder::class,
                '--force' => true,
            ])->assertExitCode(0);

            $this->assertDatabaseCount('vehicle_categories', 0);
            $this->assertDatabaseCount('vehicles', 0);
            $this->assertDatabaseCount('customers', 0);
        } finally {
            $application->detectEnvironment(fn (): string => $originalEnvironment);
        }
    }
}
