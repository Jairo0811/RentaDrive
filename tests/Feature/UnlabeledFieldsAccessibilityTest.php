<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UnlabeledFieldsAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_and_filter_fields_expose_an_accessible_name(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $expected = [
            '/users' => 'aria-label="Buscar usuario por nombre o correo"',
            '/audit' => 'aria-label="Filtrar por evento"',
            '/inspections' => 'aria-label="Filtrar inspecciones por tipo"',
            '/fleet/catalogs' => 'aria-label="Tarifa diaria"',
        ];

        foreach ($expected as $uri => $attribute) {
            $this->actingAs($administrator)->get($uri)->assertOk()->assertSee($attribute, false);
        }
    }
}
