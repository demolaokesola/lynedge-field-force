<?php

namespace Database\Factories;

use App\Models\DemandCreatorType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DemandCreatorType>
 */
class DemandCreatorTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::headline(fake()->unique()->words(2, true)),
        ];
    }
}
