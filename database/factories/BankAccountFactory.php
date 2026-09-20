<?php

namespace Database\Factories;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * Defaults to an active account at a Nigerian bank with a 10-digit NUBAN.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_name' => fake()->randomElement(['GTBank', 'Access Bank', 'First Bank', 'Zenith Bank', 'UBA', 'Stanbic IBTC']),
            'account_name' => fake()->company(),
            'account_number' => fake()->unique()->numerify('##########'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
