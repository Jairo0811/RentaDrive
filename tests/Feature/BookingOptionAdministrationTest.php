<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\BookingOption;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BookingOptionAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_tenant_administrator_can_create_booking_option_for_own_company(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $this->actingAs($administrator)
            ->post(route('booking-options.store'), [
                'code' => 'baby-seat',
                'name' => 'Asiento para bebé',
                'type' => 'extra',
                'pricing_mode' => 'per_day',
                'price' => 250,
                'description' => 'Asiento infantil sujeto a disponibilidad.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $option = BookingOption::query()->where('code', 'BABY-SEAT')->firstOrFail();

        $this->assertSame($administrator->company_id, $option->company_id);
        $this->assertSame('extra', $option->type);
    }

    public function test_tenant_administrator_cannot_manage_booking_option_from_another_company(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(RoleName::ADMINISTRATOR->value);

        $otherCompany = Company::query()->create([
            'name' => 'Otro Rent a Car',
            'slug' => 'otro-rent-a-car',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
            'plan_code' => 'starter',
        ]);
        $otherBranch = Branch::query()->create([
            'company_id' => $otherCompany->getKey(),
            'name' => 'Principal',
            'code' => 'PRINCIPAL',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->getKey(),
            'branch_id' => $otherBranch->getKey(),
        ]);

        $this->actingAs($otherUser);
        $foreignOption = BookingOption::query()->create([
            'code' => 'FOREIGN',
            'name' => 'Opción ajena',
            'type' => 'extra',
            'pricing_mode' => 'flat',
            'price' => 100,
            'is_active' => true,
        ]);

        $this->actingAs($administrator)
            ->put(route('booking-options.update', $foreignOption), [
                'code' => 'HACKED',
                'name' => 'No debe cambiar',
                'type' => 'extra',
                'pricing_mode' => 'flat',
                'price' => 1,
                'is_active' => true,
            ])
            ->assertNotFound();
    }
}
