<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
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

        // Exercise the real validation + redirect flow; never create records.
        $this->actingAs($administrator)
            ->from('/reservations/create')
            ->post('/reservations', [
                'customer_id' => '',
                'vehicle_category_id' => '',
                'start_at' => '2026-12-12T12:00',
                'end_at' => '2026-12-11T12:00',
                'daily_rate' => 2500,
                'pickup_location' => 'Oficina principal',
                'return_location' => 'Oficina principal',
                'status' => 'pending',
            ])
            ->assertRedirect('/reservations/create')
            ->assertSessionHasErrors(['customer_id', 'end_at']);

        // The PHPUnit array session is intentionally non-persistent between HTTP
        // requests. Provide an error bag to the view to assert the Blade output.
        $errors = (new ViewErrorBag)->put('default', new MessageBag([
            'customer_id' => ['Selecciona un cliente válido.'],
            'end_at' => ['La devolución debe ser posterior al retiro.'],
        ]));

        View::composer(['reservations.form', 'components.lookup-select'], static function ($view) use ($errors): void {
            $view->with('errors', $errors);
        });

        $this->get('/reservations/create')
            ->assertOk()
            ->assertSee('Revisa los campos indicados antes de guardar la reserva.')
            ->assertSee('Selecciona un cliente válido.')
            ->assertSee('La devolución debe ser posterior al retiro.')
            ->assertSee('aria-describedby="customer_id-status customer_id-error"', false)
            ->assertSee('id="end_at-error"', false)
            ->assertSee('aria-invalid="true"', false);
    }
}
