<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportDateRangeTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        return $administrator;
    }

    public function test_default_and_reasonable_ranges_are_accepted(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)->get('/reports')->assertOk();
        $this->actingAs($administrator)->get('/reports?from=2026-01-01&to=2026-12-31')->assertOk();
    }

    public function test_range_longer_than_a_year_is_rejected(): void
    {
        $this->actingAs($this->administrator())
            ->get('/reports?from=2000-01-01&to=2026-11-13')
            ->assertSessionHasErrors('to');
    }

    public function test_inverted_range_is_rejected(): void
    {
        $this->actingAs($this->administrator())
            ->get('/reports?from=2026-11-13&to=2026-11-01')
            ->assertSessionHasErrors('to');
    }

    public function test_invalid_dates_are_rejected_instead_of_failing(): void
    {
        $this->actingAs($this->administrator())
            ->get('/reports?from=no-es-fecha&to=2026-11-13')
            ->assertSessionHasErrors('from');
    }

    public function test_exports_apply_the_same_range_limit(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->get('/reports/export?from=2000-01-01&to=2026-11-13')
            ->assertSessionHasErrors('to');
        $this->actingAs($administrator)
            ->get('/reports/export/pdf?from=2000-01-01&to=2026-11-13')
            ->assertSessionHasErrors('to');
    }
}
