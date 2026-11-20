<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_recovery_and_reset_screens_are_in_spanish_with_accessible_fields(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('¿Olvidaste tu contraseña?')
            ->assertSee('Correo electrónico')
            ->assertSee('Volver al inicio de sesión')
            ->assertSee('autocomplete="email"', false);

        $this->get(route('password.reset', ['token' => 'placeholder', 'email' => 'user@example.test']))
            ->assertOk()
            ->assertSee('Crear nueva contraseña')
            ->assertSee('Confirmar nueva contraseña')
            ->assertSee('autocomplete="new-password"', false);
    }

    public function test_unknown_account_gets_generic_recovery_confirmation(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.');

        Notification::assertNothingSent();
    }

    public function test_invalid_reset_token_is_rejected_with_spanish_message(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['email' => 'El enlace de recuperación es inválido o ha vencido. Solicita uno nuevo.']);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}
