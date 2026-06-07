<?php

namespace Database\Factories;

use App\Models\Deposit;
use App\Models\DepositAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepositAllocation>
 */
class DepositAllocationFactory extends Factory
{
    /**
     * Creates a fresh deposit with a large amount so the default allocation amount
     * fits within the guard. Override deposit_id and amount as needed in tests.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deposit_id' => Deposit::factory()->state(['amount' => 100000]),
            'distribution_id' => null,
            'amount' => fake()->randomFloat(2, 100, 5000),
            'allocated_by_user_id' => User::factory(),
            'allocated_at' => now(),
        ];
    }

    public function forDeposit(Deposit $deposit): static
    {
        return $this->state(['deposit_id' => $deposit->id]);
    }
}
