<?php

namespace Database\Factories;

use App\Enums\DistributionStatus;
use App\Models\Customer;
use App\Models\Distribution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Distribution>
 */
class DistributionFactory extends Factory
{
    /**
     * Defaults to a draft distribution for a fresh rep, with territory_id and team_id
     * kept consistent with the position (both are denormalised at write time).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $position = Position::factory()->create();

        return [
            'user_id' => User::factory(),
            'position_id' => $position->id,
            'territory_id' => $position->territory_id,
            'team_id' => $position->team_id,
            'customer_id' => Customer::factory()->state(['territory_id' => $position->territory_id]),
            'invoice_number' => 'INV-'.Str::upper(fake()->unique()->bothify('??##-####')),
            'invoice_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'total_amount' => 0,
            'status' => DistributionStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Pin the distribution to a given position, keeping territory_id and team_id in sync.
     */
    public function forPosition(Position $position): static
    {
        return $this->state([
            'position_id' => $position->id,
            'territory_id' => $position->territory_id,
            'team_id' => $position->team_id,
            'customer_id' => Customer::factory()->state(['territory_id' => $position->territory_id]),
        ]);
    }

    /**
     * Attribute the distribution to a specific rep.
     */
    public function by(User $rep): static
    {
        return $this->state(['user_id' => $rep->id]);
    }

    public function posted(): static
    {
        return $this->state(['status' => DistributionStatus::Posted]);
    }

    public function void(): static
    {
        return $this->state(['status' => DistributionStatus::Void]);
    }
}
