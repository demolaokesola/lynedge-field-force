<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\TargetTier;
use App\Models\TargetTierLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TargetTierLine>
 */
class TargetTierLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'target_tier_id' => TargetTier::factory(),
            'product_id' => Product::factory(),
            'annual_volume' => fake()->randomFloat(2, 100, 5000),
        ];
    }
}
