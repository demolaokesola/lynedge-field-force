<?php

namespace Database\Factories;

use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

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
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
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
     * Create the user with the given role assigned (creating the role if needed).
     */
    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            $user->assignRole(Role::findOrCreate($role, 'web'));
        });
    }

    /**
     * Anchor the user to a region (for region-scoped non-reps like regional_head).
     */
    public function inRegion(Region|int $region): static
    {
        return $this->state(['region_id' => $region instanceof Region ? $region->getKey() : $region]);
    }
}
