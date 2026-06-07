<?php

namespace Database\Factories;

use App\Models\TargetTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TargetTier>
 */
class TargetTierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Tier '.fake()->unique()->numberBetween(1, 99),
            'description' => null,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
