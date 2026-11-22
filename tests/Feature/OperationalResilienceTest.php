<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Operations\HealthReport;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Monolog\Formatter\JsonFormatter;
use Tests\TestCase;

final class OperationalResilienceTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-de-salud-de-prueba-0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'queue.default' => 'database',
            'rentadrive.operations.require_scheduler' => false,
            'rentadrive.operations.max_queue_age_seconds' => 900,
            'rentadrive.operations.max_failed_jobs' => 5,
            'rentadrive.operations.max_scheduler_age_seconds' => 180,
        ]);
        Cache::forget(HealthReport::HEARTBEAT_KEY);
        Cache::forget('rentadrive:alert:sent');
    }

    private function job(int $availableAt, ?int $reservedAt = null): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default', 'payload' => '{}', 'attempts' => 0,
            'reserved_at' => $reservedAt, 'available_at' => $availableAt, 'created_at' => $availableAt,
        ]);
    }

    private function failedJobs(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            DB::table('failed_jobs')->insert([
                'uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default',
                'payload' => '{}', 'exception' => 'x', 'failed_at' => now(),
            ]);
        }
    }

    public function test_public_health_is_minimal_and_reports_database_status(): void
    {
        $response = $this->getJson('/api/health')->assertOk()->assertExactJson(['status' => 'ok']);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_detailed_health_does_not_exist_without_a_configured_token(): void
    {
        config(['rentadrive.operations.health_token' => null]);
        $this->getJson('/api/health/details')->assertNotFound();

        config(['rentadrive.operations.health_token' => 'corto']);
        $this->getJson('/api/health/details', ['Authorization' => 'Bearer corto'])->assertNotFound();
    }

    public function test_detailed_health_requires_the_exact_bearer_token(): void
    {
        config(['rentadrive.operations.health_token' => self::TOKEN]);

        $this->getJson('/api/health/details')->assertUnauthorized();
        $this->getJson('/api/health/details', ['Authorization' => 'Bearer otro-token'])->assertUnauthorized();
        $this->getJson('/api/health/details', ['Authorization' => 'Bearer '.self::TOKEN])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.up', true)
            ->assertJsonPath('checks.queue.pending', 0);
    }

    public function test_detailed_health_never_leaks_connection_details(): void
    {
        config(['rentadrive.operations.health_token' => self::TOKEN]);

        $body = (string) $this->getJson('/api/health/details', ['Authorization' => 'Bearer '.self::TOKEN])->getContent();

        foreach (['password', 'username', 'host', 'APP_KEY', 'sqlsrv', 'exception'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $body);
        }
    }

    public function test_stalled_queue_and_failed_jobs_degrade_the_report(): void
    {
        $this->job(time() - 3600);
        $this->failedJobs(6);

        $report = app(HealthReport::class)->details();

        $this->assertSame('degraded', $report['status']);
        $this->assertContains('queue.stalled', $report['problems']);
        $this->assertContains('queue.failed_jobs', $report['problems']);
    }

    public function test_recent_or_reserved_jobs_are_not_a_problem(): void
    {
        $this->job(time() - 30);
        $this->job(time() - 7200, reservedAt: time() - 10);
        $this->failedJobs(5);

        $this->assertSame('ok', app(HealthReport::class)->details()['status']);
    }

    public function test_scheduler_heartbeat_is_required_only_when_configured(): void
    {
        $this->assertSame('ok', app(HealthReport::class)->details()['status']);

        config(['rentadrive.operations.require_scheduler' => true]);
        $this->assertContains('scheduler.stale', app(HealthReport::class)->details()['problems']);

        Cache::put(HealthReport::HEARTBEAT_KEY, time() - 10, 600);
        $this->assertSame('ok', app(HealthReport::class)->details()['status']);

        Cache::put(HealthReport::HEARTBEAT_KEY, time() - 900, 600);
        $this->assertContains('scheduler.stale', app(HealthReport::class)->details()['problems']);
    }

    public function test_monitor_succeeds_when_healthy_and_fails_and_logs_when_degraded(): void
    {
        $this->artisan('rentadrive:monitor')->assertSuccessful();

        Log::spy();
        $this->job(time() - 3600);

        $this->artisan('rentadrive:monitor')->assertFailed();

        Log::shouldHaveReceived('critical')->withArgs(
            fn (string $message, array $context): bool => $message === 'operations.degraded' && $context['problems'] === ['queue.stalled'],
        )->once();
    }

    public function test_monitor_sends_no_email_without_a_configured_recipient(): void
    {
        $this->job(time() - 3600);
        Mail::shouldReceive('raw')->never();

        $this->artisan('rentadrive:monitor')->assertFailed();
    }

    public function test_monitor_alerts_once_per_hour_when_a_recipient_is_configured(): void
    {
        $this->job(time() - 3600);
        config(['rentadrive.operations.alert_email' => 'operaciones@example.com']);
        Mail::shouldReceive('raw')->once();

        $this->artisan('rentadrive:monitor')->assertFailed();
        $this->artisan('rentadrive:monitor')->assertFailed();
    }

    public function test_monitor_survives_a_mail_failure(): void
    {
        $this->job(time() - 3600);
        config(['rentadrive.operations.alert_email' => 'operaciones@example.com']);
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('smtp caído'));

        $this->artisan('rentadrive:monitor')->assertFailed();
    }

    public function test_scheduler_registers_heartbeat_monitor_and_failed_job_pruning(): void
    {
        $registered = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) ($event->description ?: $event->command))
            ->implode(' | ');

        $this->assertStringContainsString('rentadrive:heartbeat', $registered);
        $this->assertStringContainsString('rentadrive:monitor', $registered);
        $this->assertStringContainsString('queue:prune-failed', $registered);
        $this->assertStringContainsString('rentadrive:expire-trials', $registered);
    }

    public function test_request_id_is_generated_and_a_safe_incoming_one_is_preserved(): void
    {
        $generated = $this->getJson('/api/health')->headers->get('X-Request-Id');
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $generated);

        $this->getJson('/api/health', ['X-Request-Id' => 'trace-abc-12345'])
            ->assertHeader('X-Request-Id', 'trace-abc-12345');
    }

    public function test_unsafe_incoming_request_id_is_replaced(): void
    {
        foreach (["bad\nid-injection", 'corto', str_repeat('a', 200), 'con espacios y <script>'] as $unsafe) {
            $id = $this->getJson('/api/health', ['X-Request-Id' => $unsafe])->headers->get('X-Request-Id');

            $this->assertNotSame($unsafe, $id);
            $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $id);
        }
    }

    public function test_structured_log_channel_is_configured_as_rotating_json(): void
    {
        $handler = config('logging.channels.json');

        $this->assertSame(JsonFormatter::class, $handler['formatter']);
        $this->assertSame('daily', $handler['driver']);
    }
}
