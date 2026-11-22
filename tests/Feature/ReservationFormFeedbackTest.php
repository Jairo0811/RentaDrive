<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

final class ReservationFormFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_errors_are_visible_and_associated_with_form_fields(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $errors = (new ViewErrorBag)->put('default', new MessageBag([
            'customer_id' => ['Selecciona un cliente válido.'],
            'end_at' => ['La devolución debe ser posterior al retiro.'],
        ]));

        $this->actingAs($administrator)
            ->withSession(['errors' => $errors])
            ->get('/reservations/create')
            ->assertOk()
            ->assertSee('Revisa los campos indicados antes de guardar la reserva.')
            ->assertSee('Selecciona un cliente válido.')
            ->assertSee('La devolución debe ser posterior al retiro.')
            ->assertSee('aria-describedby="customer_id-status customer_id-error"', false)
            ->assertSee('id="end_at-error"', false)
            ->assertSee('aria-invalid="true"', false);
    }
}
