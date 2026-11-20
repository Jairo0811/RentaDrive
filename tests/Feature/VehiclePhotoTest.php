<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\RoleName;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class VehiclePhotoTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private Company $company;

    private VehicleModel $model;

    private VehicleCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['rentadrive.vehicle_photos.disk' => 'public']);
        $this->seed(RolePermissionSeeder::class);

        $this->company = Company::query()->create([
            'name' => 'Rent a Car FOTO',
            'slug' => 'foto',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
        ]);
        $branch = Branch::query()->create([
            'company_id' => $this->company->getKey(),
            'name' => 'Sucursal Principal',
            'code' => 'PRINCIPAL',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $context = app(TenantContext::class);
        $context->set($this->company, $branch);
        $brand = VehicleBrand::query()->create(['name' => 'Toyota', 'is_active' => true]);
        $this->category = VehicleCategory::query()->create(['code' => 'SED', 'name' => 'Sedán', 'daily_rate' => 2500, 'deposit_amount' => 5000, 'is_active' => true]);
        $this->model = VehicleModel::query()->create(['vehicle_brand_id' => $brand->getKey(), 'name' => 'Corolla', 'year' => 2026, 'is_active' => true]);
        $context->clear();
    }

    private function userWithRole(RoleName $role): User
    {
        $user = User::factory()->create([
            'company_id' => $this->company->getKey(),
            'branch_id' => $this->company->branches()->value('id'),
        ]);
        $user->assignRole($role->value);

        return $user;
    }

    private function png(string $name = 'auto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, (string) base64_decode(self::PNG, true));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'vehicle_model_id' => $this->model->getKey(),
            'vehicle_category_id' => $this->category->getKey(),
            'code' => 'FOTO-001',
            'plate' => 'A100001',
            'color' => 'Blanco',
            'transmission' => 'automatic',
            'fuel_type' => 'gasoline',
            'seats' => 5,
            'mileage' => 1000,
            'status' => 'available',
        ], $extra);
    }

    private function vehicleWithPhoto(): Vehicle
    {
        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->post('/vehicles', $this->payload(['photo' => $this->png()]))
            ->assertRedirect();

        return Vehicle::withoutGlobalScopes()->where('code', 'FOTO-001')->firstOrFail();
    }

    public function test_photo_is_stored_under_the_company_folder_with_a_generated_name(): void
    {
        $vehicle = $this->vehicleWithPhoto();

        $this->assertNotNull($vehicle->photo_path);
        $this->assertStringStartsWith('vehicles/'.$this->company->getKey().'/', $vehicle->photo_path);
        $this->assertStringNotContainsString('auto', $vehicle->photo_path);
        Storage::disk('public')->assertExists($vehicle->photo_path);
    }

    public function test_replacing_the_photo_deletes_the_previous_file(): void
    {
        $vehicle = $this->vehicleWithPhoto();
        $old = $vehicle->photo_path;

        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->put('/vehicles/'.$vehicle->getKey(), $this->payload(['photo' => $this->png('nueva.png')]))
            ->assertRedirect();

        $vehicle->refresh();
        $this->assertNotSame($old, $vehicle->photo_path);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($vehicle->photo_path);
    }

    public function test_remove_photo_clears_the_column_and_deletes_the_file(): void
    {
        $vehicle = $this->vehicleWithPhoto();
        $old = $vehicle->photo_path;

        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->put('/vehicles/'.$vehicle->getKey(), $this->payload(['remove_photo' => '1']))
            ->assertRedirect();

        $this->assertNull($vehicle->refresh()->photo_path);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_update_without_a_new_photo_keeps_the_existing_one(): void
    {
        $vehicle = $this->vehicleWithPhoto();
        $old = $vehicle->photo_path;

        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->put('/vehicles/'.$vehicle->getKey(), $this->payload(['color' => 'Negro']))
            ->assertRedirect();

        $this->assertSame($old, $vehicle->refresh()->photo_path);
        Storage::disk('public')->assertExists($old);
    }

    public function test_deleting_the_vehicle_removes_its_photo(): void
    {
        $vehicle = $this->vehicleWithPhoto();
        $old = $vehicle->photo_path;

        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->delete('/vehicles/'.$vehicle->getKey())
            ->assertRedirect();

        Storage::disk('public')->assertMissing($old);
    }

    public function test_non_image_and_oversized_files_are_rejected_without_creating_the_vehicle(): void
    {
        $administrator = $this->userWithRole(RoleName::ADMINISTRATOR);

        $this->actingAs($administrator)
            ->post('/vehicles', $this->payload(['photo' => UploadedFile::fake()->createWithContent('script.png', '<?php echo 1;')]))
            ->assertSessionHasErrors('photo');
        $this->actingAs($administrator)
            ->post('/vehicles', $this->payload(['photo' => UploadedFile::fake()->create('enorme.png', 9000, 'image/png')]))
            ->assertSessionHasErrors('photo');

        $this->assertSame(0, Vehicle::withoutGlobalScopes()->count());
    }

    public function test_user_without_manage_permission_cannot_upload_a_photo(): void
    {
        $this->actingAs($this->userWithRole(RoleName::MANAGER))
            ->post('/vehicles', $this->payload(['photo' => $this->png()]))
            ->assertForbidden();

        $this->assertSame(0, Vehicle::withoutGlobalScopes()->count());
    }

    public function test_photo_is_shown_on_the_detail_page(): void
    {
        $vehicle = $this->vehicleWithPhoto();

        $this->actingAs($this->userWithRole(RoleName::ADMINISTRATOR))
            ->get('/vehicles/'.$vehicle->getKey())
            ->assertOk()
            ->assertSee('Foto de', false)
            ->assertSee($vehicle->photo_path, false);
    }
}
