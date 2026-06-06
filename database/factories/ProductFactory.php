<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->unique()->word()).' '.fake()->randomElement(['Syrup', 'Tablets', 'Capsules', 'Injection', 'Suspension']);

        return [
            'name' => $name,
            'sku' => Str::upper(fake()->unique()->bothify('???-####')),
            'pack_size' => fake()->randomElement(['10 x 10 tablets', '1 x 100ml', '5 x 5ml ampoules', '1 x 60ml', '2 x 14 capsules']),
            'unit_price' => fake()->randomFloat(2, 500, 50000),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
