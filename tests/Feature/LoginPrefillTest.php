<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginPrefillTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_email_is_prefilled_only_in_local_and_testing(): void
    {
        $this->get('/login')->assertOk()->assertSee('admin@rentadrive.com.do');
    }

    public function test_production_login_does_not_prefill_or_reveal_the_admin_account(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('admin@rentadrive.com.do')
            ->assertDontSee('Acceso de demostración')
            ->assertDontSee('RentaDrive123');
    }
}
