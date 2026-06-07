<?php

namespace Database\Factories;

use App\Models\Cycle;
use App\Models\Product;
use App\Models\RepMonthlyTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<RepMonthlyTarget>
 */
class RepMonthlyTargetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cycle_id' => Cycle::factory(),
            'user_id' => User::factory(),
            'year_month' => Carbon::now()->startOfMonth(),
            'product_id' => Product::factory(),
            'target_qty' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
