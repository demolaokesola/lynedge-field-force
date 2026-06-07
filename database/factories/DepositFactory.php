<?php

namespace Database\Factories;

use App\Enums\DepositChannel;
use App\Enums\DepositStatus;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    /**
     * Defaults to an unreconciled deposit for a fresh rep and customer.
     * territory_id is derived from the customer in the Deposit::creating() event.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 500, 100000),
            'deposit_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'reference' => fake()->optional()->bothify('REF-########'),
            'bank' => fake()->optional()->randomElement(['GTBank', 'Access Bank', 'First Bank', 'Zenith Bank']),
            'channel' => fake()->optional()->randomElement(DepositChannel::cases())?->value,
            'status' => DepositStatus::Unreconciled,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(['customer_id' => $customer->id]);
    }

    public function by(User $rep): static
    {
        return $this->state(['user_id' => $rep->id]);
    }

    public function reconciled(): static
    {
        return $this->state(['status' => DepositStatus::Reconciled]);
    }

    public function disputed(): static
    {
        return $this->state(['status' => DepositStatus::Disputed]);
    }
}
