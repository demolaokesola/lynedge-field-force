<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'territory_id' => Territory::factory(),
            'name' => fake()->company().' Pharmacy',
            'type' => fake()->randomElement(CustomerType::cases()),
            'address' => fake()->streetAddress(),
            'phone' => '0'.fake()->numerify('80########'),
        ];
    }

    /**
     * Attribute the customer to the rep who created it.
     */
    public function by(User $user): static
    {
        return $this->state(['created_by' => $user->id]);
    }
}
