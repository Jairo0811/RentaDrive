<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Security\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Security\Mfa\Totp;
use App\Support\Security\Mfa\TwoFactorAuthentication;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /** @return array{User, string, list<string>} */
    private function userWithMfa(RoleName $role = RoleName::ADMINISTRATOR): array
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        $mfa = app(TwoFactorAuthentication::class);
        $secret = $mfa->beginSetup($user);
        $codes = $mfa->confirmSetup($user, (new Totp)->codeAt($secret, time()));

        $this->assertNotNull($codes, 'La activación con un código válido debe funcionar.');

        return [$user->refresh(), $secret, $codes];
    }

    private function nextCode(User $user, string $secret): string
    {
        // Siguiente intervalo: distinto al consumido durante la activación (se tolera ±1 intervalo).
        $step = (int) $user->two_factor_last_used_step + 1;

        return (new Totp)->codeForStep($secret, $step);
    }

    public function test_user_without_mfa_logs_in_directly(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_mfa_requires_second_factor_before_creating_session(): void
    {
        [$user, $secret] = $this->userWithMfa();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();

        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->post(route('two-factor.challenge'), ['code' => $this->nextCode($user, $secret)])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_code_does_not_authenticate(): void
    {
        [$user] = $this->userWithMfa();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('two-factor.challenge'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_totp_code_cannot_be_replayed(): void
    {
        [$user, $secret] = $this->userWithMfa();
        $code = $this->nextCode($user, $secret);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('two-factor.challenge'), ['code' => $code]);
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('two-factor.challenge'), ['code' => $code])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_challenge_without_pending_login_redirects_to_login(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
        $this->post(route('two-factor.challenge'), ['code' => '123456'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_pending_login_expires(): void
    {
        [$user, $secret] = $this->userWithMfa();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->travel(10)->minutes();

        $this->post(route('two-factor.challenge'), ['code' => $this->nextCode($user, $secret)])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_recovery_code_works_once_and_is_consumed(): void
    {
        [$user, , $codes] = $this->userWithMfa();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('two-factor.challenge'), ['recovery_code' => strtoupper($codes[0])])->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->assertSame(7, app(TwoFactorAuthentication::class)->recoveryCodesRemaining($user->refresh()));

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('two-factor.challenge'), ['recovery_code' => $codes[0]])->assertSessionHasErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_brute_force_on_second_factor_is_throttled(): void
    {
        [$user, $secret] = $this->userWithMfa();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.challenge'), ['code' => '000000'])->assertSessionHasErrors('code');
        }

        // Incluso el código correcto queda bloqueado mientras dure el límite.
        $this->post(route('two-factor.challenge'), ['code' => $this->nextCode($user, $secret)])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertTrue(AuditLog::query()->where('event', 'mfa_locked')->exists());
    }

    public function test_inactive_user_with_mfa_cannot_complete_login(): void
    {
        [$user, $secret] = $this->userWithMfa();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $user->forceFill(['is_active' => false])->saveQuietly();

        $this->post(route('two-factor.challenge'), ['code' => $this->nextCode($user, $secret)])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_secret_and_recovery_codes_are_encrypted_at_rest_and_never_audited(): void
    {
        [$user, $secret, $codes] = $this->userWithMfa();

        $row = DB::table('users')->where('id', $user->id)->first();

        $this->assertNotSame($secret, $row->two_factor_secret);
        $this->assertStringNotContainsString($secret, (string) $row->two_factor_secret);
        $this->assertStringNotContainsString($codes[0], (string) $row->two_factor_recovery_codes);
        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $user->toArray());

        $dump = json_encode(AuditLog::query()->get()->toArray());
        $this->assertStringNotContainsString($secret, $dump);
        $this->assertStringNotContainsString($codes[0], $dump);
        $this->assertTrue(AuditLog::query()->where('event', 'mfa_enabled')->where('user_id', $user->id)->exists());
    }

    public function test_enrollment_requires_recent_password_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('two-factor.enable'))
            ->assertRedirect(route('password.confirm'));

        $this->assertNull($user->refresh()->two_factor_secret);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.enable'))
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->refresh()->two_factor_secret);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_user_confirms_setup_and_sees_recovery_codes_once(): void
    {
        $user = User::factory()->create();
        $mfa = app(TwoFactorAuthentication::class);
        $secret = $mfa->beginSetup($user);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertFalse($user->refresh()->hasTwoFactorEnabled());

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.confirm'), ['code' => (new Totp)->codeAt($secret, time())]);

        $response->assertOk()->assertSee('No se volverán a mostrar');
        $this->assertTrue($user->refresh()->hasTwoFactorEnabled());
        $this->assertSame(8, $mfa->recoveryCodesRemaining($user));

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertDontSee($secret);
    }

    public function test_regenerating_recovery_codes_requires_valid_totp_and_invalidates_old_codes(): void
    {
        [$user, $secret, $old] = $this->userWithMfa();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.recovery-codes'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.recovery-codes'), ['code' => $this->nextCode($user, $secret)])
            ->assertOk();

        $mfa = app(TwoFactorAuthentication::class);
        $this->assertFalse($mfa->verifyRecoveryCode($user->refresh(), $old[0]));
    }

    public function test_disabling_mfa_clears_secrets_and_requires_confirmed_password(): void
    {
        [$user] = $this->userWithMfa();

        $this->actingAs($user)->delete(route('two-factor.disable'))->assertRedirect(route('password.confirm'));
        $this->assertTrue($user->refresh()->hasTwoFactorEnabled());

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('two-factor.disable'))
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_secret);
        $this->assertTrue(AuditLog::query()->where('event', 'mfa_disabled')->exists());
    }

    public function test_enforcement_redirects_privileged_users_without_mfa_to_profile(): void
    {
        config(['rentadrive.security.mfa.enforce' => true]);

        $admin = User::factory()->create();
        $admin->assignRole(RoleName::ADMINISTRATOR->value);

        $agent = User::factory()->create();
        $agent->assignRole(RoleName::RENTAL_AGENT->value);

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('profile.edit'));
        $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
        $this->assertFalse($this->actingAs($agent)->get(route('dashboard'))->isRedirect(route('profile.edit')));
    }

    public function test_enforcement_covers_platform_admin_and_is_satisfied_by_mfa(): void
    {
        config(['rentadrive.security.mfa.enforce' => true]);

        $platform = User::factory()->create(['company_id' => null, 'branch_id' => null, 'is_platform_admin' => true]);
        $this->actingAs($platform)->get(route('platform.dashboard'))->assertRedirect(route('profile.edit'));

        [$admin] = $this->userWithMfa();
        $this->assertFalse($this->actingAs($admin)->get(route('dashboard'))->isRedirect(route('profile.edit')));
    }

    public function test_enforcement_is_off_by_default(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::ADMINISTRATOR->value);

        $this->assertFalse($this->actingAs($admin)->get(route('dashboard'))->isRedirect(route('profile.edit')));
    }

    public function test_password_reset_does_not_bypass_second_factor(): void
    {
        [$user] = $this->userWithMfa();

        $user->forceFill(['password' => 'Nueva-clave-segura-123!'])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'Nueva-clave-segura-123!'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
    }

    public function test_mfa_reset_command_disables_second_factor(): void
    {
        [$user] = $this->userWithMfa();

        $this->artisan('rentadrive:mfa-reset', ['email' => $user->email])
            ->expectsConfirmation('¿Desactivar MFA de '.$user->email.'? El usuario deberá volver a activarlo.', 'yes')
            ->assertSuccessful();

        $this->assertFalse($user->refresh()->hasTwoFactorEnabled());
    }
}
