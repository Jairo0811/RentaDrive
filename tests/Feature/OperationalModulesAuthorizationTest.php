<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OperationalModulesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_administrator_can_open_every_primary_module(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        foreach ([
            '/customers',
            '/vehicles',
            '/fleet/catalogs',
            '/reservations',
            '/rentals',
            '/inspections',
            '/invoices',
            '/payments',
            '/reports',
            '/users',
            '/settings',
            '/audit',
        ] as $uri) {
            $this->actingAs($administrator)->get($uri)->assertOk();
        }
    }

    public function test_inspector_only_has_access_to_assigned_operational_modules(): void
    {
        $inspector = User::factory()->create();
        $inspector->assignRole(RoleName::INSPECTOR->value);

        $this->actingAs($inspector)->get('/vehicles')->assertOk();
        $this->actingAs($inspector)->get('/rentals')->assertOk();
        $this->actingAs($inspector)->get('/inspections')->assertOk();
        $this->actingAs($inspector)->get('/customers')->assertForbidden();
        $this->actingAs($inspector)->get('/invoices')->assertForbidden();
        $this->actingAs($inspector)->get('/users')->assertForbidden();
    }
}
