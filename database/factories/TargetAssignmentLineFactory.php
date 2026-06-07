<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\TargetAssignment;
use App\Models\TargetAssignmentLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TargetAssignmentLine>
 */
class TargetAssignmentLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'target_assignment_id' => TargetAssignment::factory(),
            'product_id' => Product::factory(),
            'annual_volume' => fake()->randomFloat(2, 0, 5000),
        ];
    }
}
