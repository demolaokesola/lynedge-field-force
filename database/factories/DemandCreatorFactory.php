<?php

namespace Database\Factories;

use App\Models\DemandCreator;
use App\Models\DemandCreatorType;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandCreator>
 */
class DemandCreatorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'demand_creator_type_id' => DemandCreatorType::factory(),
            'territory_id' => Territory::factory(),
            'name' => fake()->name(),
            'affiliation' => fake()->company(),
            'phone' => '0'.fake()->numerify('80########'),
            'address' => fake()->streetAddress(),
        ];
    }

    /**
     * Attribute the demand creator to the rep who created it.
     */
    public function by(User $user): static
    {
        return $this->state(['created_by' => $user->id]);
    }
}
