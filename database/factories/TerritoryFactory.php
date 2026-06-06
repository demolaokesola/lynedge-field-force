<?php

namespace Database\Factories;

use App\Enums\TeamPolicy;
use App\Models\Region;
use App\Models\Territory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Territory>
 */
class TerritoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'region_id' => Region::factory(),
            'name' => $name,
            'code' => Str::upper(Str::substr($name, 0, 3)).'-'.fake()->unique()->numberBetween(100, 999),
            'team_policy' => fake()->randomElement(TeamPolicy::cases()),
        ];
    }

    public function strict(): static
    {
        return $this->state(['team_policy' => TeamPolicy::Strict]);
    }

    public function liberal(): static
    {
        return $this->state(['team_policy' => TeamPolicy::Liberal]);
    }
}
