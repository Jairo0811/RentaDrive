<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::query()->firstOrCreate(
            ['slug' => 'rentadrive-testing'],
            [
                'name' => 'RentaDrive Testing',
                'currency' => 'DOP',
                'timezone' => 'America/Santo_Domingo',
                'status' => 'active',
            ],
        );

        $branch = Branch::query()->firstOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'TEST',
            ],
            [
                'name' => 'Sucursal Testing',
                'is_primary' => true,
                'is_active' => true,
            ],
        );

        return [
            'company_id' => $company->getKey(),
            'branch_id' => $branch->getKey(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user cannot access the application.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
