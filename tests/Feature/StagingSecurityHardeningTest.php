<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Operations\ProductionReadiness;
use App\Support\Security\DemoCredentials;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PlatformAdminSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

final class StagingSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private string $originalEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnvironment = app()->environment();
    }

    protected function tearDown(): void
    {
        $original = $this->originalEnvironment;
        app()->detectEnvironment(fn (): string => $original);

        parent::tearDown();
    }

    private function useEnvironment(string $environment, ?string $url = null): void
    {
        app()->detectEnvironment(fn (): string => $environment);

        if ($url !== null) {
            config()->set('app.url', $url);
        }
    }

    public function test_demo_accounts_are_never_seeded_in_production_or_staging(): void
    {
        foreach (['production', 'staging'] as $environment) {
            $this->useEnvironment($environment, 'https://staging.example.com');

            $this->seed(DatabaseSeeder::class);

            $this->assertDatabaseCount('users', 0);
        }
    }

    public function test_local_environment_with_a_public_url_refuses_predictable_accounts(): void
    {
        $this->useEnvironment('local', 'https://staging.example.com');

        $this->seed(PlatformAdminSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertNotNull(DemoCredentials::refusalReason());
    }

    public function test_local_environment_with_a_development_url_still_seeds_demo_accounts(): void
    {
        foreach (['http://localhost:8000', 'http://127.0.0.1:8080', 'http://rentadrive.test', 'http://app.localhost'] as $url) {
            $this->useEnvironment('local', $url);
            $this->assertNull(DemoCredentials::refusalReason(), $url);
        }

        $this->useEnvironment('local', 'http://localhost:8000');
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseHas('users', ['email' => config('rentadrive.seed.admin_email')]);
    }

    public function test_seeders_do_not_create_accounts_without_an_explicit_password(): void
    {
        $this->useEnvironment('testing');
        config()->set(['rentadrive.seed.admin_password' => '', 'rentadrive.seed.platform_admin_password' => null]);

        $this->seed(PlatformAdminSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_development_url_detection_rejects_public_and_lookalike_hosts(): void
    {
        foreach (['https://staging.example.com', 'https://localhost.evil.com', 'https://rentadrive.test.example.com', '', 'not-a-url'] as $url) {
            $this->assertFalse(DemoCredentials::isDevelopmentUrl($url), $url);
        }
    }

    public function test_preflight_reports_each_new_hardening_problem(): void
    {
        config()->set([
            'session.same_site' => 'none',
            'session.driver' => 'array',
            'app.key' => 'base64:'.base64_encode('short'),
            'database.connections.sqlsrv.username' => 'sa',
            'database.connections.sqlsrv.password' => '',
            'logging.channels.single.level' => 'debug',
            'rentadrive.security.trusted_proxies' => '*',
            'services.rentadrive_gateway.webhook_secret' => 'sandbox-secret',
            'cors.allowed_origins' => ['*'],
        ]);

        $problems = implode("\n", app(ProductionReadiness::class)->hardeningProblems());

        foreach (['SESSION_SAME_SITE', 'SESSION_DRIVER', 'APP_KEY', 'DB_USERNAME', 'DB_PASSWORD', 'LOG_LEVEL', 'TRUSTED_PROXIES', 'RENTA_PAYMENT_WEBHOOK_SECRET', 'CORS'] as $needle) {
            $this->assertStringContainsString($needle, $problems);
        }
    }

    public function test_preflight_detects_demo_accounts_that_keep_a_known_password(): void
    {
        User::factory()->create([
            'email' => 'admin@rentadrive.com.do',
            'password' => Hash::make('RentaDrive123..'),
        ]);
        User::factory()->create([
            'email' => 'platform@rentadrive.test',
            'password' => Hash::make('una-contrasena-larga-y-unica-2026'),
        ]);

        $problems = app(ProductionReadiness::class)->predictableCredentialProblems();

        $this->assertCount(1, $problems);
        $this->assertStringContainsString('admin@rentadrive.com.do', $problems[0]);
    }

    public function test_production_check_command_fails_on_insecure_configuration(): void
    {
        $this->artisan('rentadrive:production-check')->assertExitCode(1);
    }

    public function test_api_does_not_allow_cross_origin_browser_access(): void
    {
        $response = $this->postJson('/api/payments/sandbox/webhook', [], ['Origin' => 'https://evil.example']);

        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertFalse($response->headers->has('Access-Control-Allow-Credentials'));
        $this->assertSame([], config('cors.allowed_origins'));
        $this->assertFalse(config('cors.supports_credentials'));
    }

    public function test_forwarded_proto_is_only_trusted_from_configured_proxies(): void
    {
        config()->set('trustedproxy.proxies', '10.0.0.1');
        $middleware = app(TrustProxies::class);
        $probe = static fn (Request $request) => response()->json(['secure' => $request->isSecure()]);

        $make = static fn (string $remote): Request => Request::create('http://staging.example.com/up', 'GET', [], [], [], [
            'REMOTE_ADDR' => $remote,
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $this->assertTrue($middleware->handle($make('10.0.0.1'), $probe)->getData(true)['secure']);
        $this->assertFalse($middleware->handle($make('203.0.113.9'), $probe)->getData(true)['secure']);

        config()->set('trustedproxy.proxies', null);
        $this->assertFalse($middleware->handle($make('10.0.0.1'), $probe)->getData(true)['secure']);
    }

    public function test_production_password_policy_is_stricter_than_local(): void
    {
        $rules = static fn (): array => ['password' => ['required', 'confirmed', Password::defaults()]];
        $weak = ['password' => 'abcd1234', 'password_confirmation' => 'abcd1234'];
        $strong = ['password' => 'Contraseña-Larga#2026x', 'password_confirmation' => 'Contraseña-Larga#2026x'];

        $this->useEnvironment('local');
        $this->assertTrue(Validator::make($weak, $rules())->passes());

        $this->useEnvironment('production');
        $this->assertTrue(Validator::make($weak, $rules())->fails());
        $this->assertTrue(Validator::make($strong, $rules())->passes());
    }

    public function test_tracked_files_contain_no_known_leaked_secrets(): void
    {
        $files = [];
        exec('git -C '.escapeshellarg(base_path()).' ls-files 2>/dev/null', $files, $status);

        if ($status !== 0 || $files === []) {
            $this->markTestSkipped('git no está disponible para listar los archivos versionados.');
        }

        $patterns = [
            '/RentaDrive_Local_2026/',
            '/AKIA[0-9A-Z]{16}/',
            '/-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----/',
            '/^\s*(?:DB_PASSWORD|MAIL_PASSWORD|AWS_SECRET_ACCESS_KEY|APP_KEY)=(?!\s*$)(?!null)(?!base64:\.\.\.)\S+/m',
        ];
        $skip = ['tests/Feature/StagingSecurityHardeningTest.php'];
        $hits = [];

        foreach ($files as $file) {
            if (in_array($file, $skip, true) || str_ends_with($file, '.lock') || str_ends_with($file, '.png')
                || str_ends_with($file, '.jpg') || str_ends_with($file, '.woff2') || str_ends_with($file, '.mp4')
                || str_ends_with($file, '.json') && str_contains($file, 'package-lock')) {
                continue;
            }

            $path = base_path($file);
            if (! is_file($path) || filesize($path) > 2_000_000) {
                continue;
            }

            $content = (string) file_get_contents($path);
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content) === 1) {
                    $hits[] = $file.' ~ '.$pattern;
                }
            }
        }

        $this->assertSame([], $hits, 'Se encontraron posibles secretos versionados.');
    }
}
