<?php

namespace Database\Factories;

use App\Enums\StockDispatchStatus;
use App\Models\Position;
use App\Models\StockDispatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockDispatch>
 */
class StockDispatchFactory extends Factory
{
    /**
     * Defaults to a draft dispatch, with territory_id and team_id kept consistent with
     * the position (both are denormalised at write time).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $position = Position::factory()->create();

        return [
            'position_id' => $position->id,
            'territory_id' => $position->territory_id,
            'team_id' => $position->team_id,
            'dispatched_by_user_id' => User::factory(),
            'dispatch_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'status' => StockDispatchStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Pin the dispatch to a given position, keeping territory_id and team_id in sync.
     */
    public function forPosition(Position $position): static
    {
        return $this->state([
            'position_id' => $position->id,
            'territory_id' => $position->territory_id,
            'team_id' => $position->team_id,
        ]);
    }

    /**
     * Attribute the dispatch to a specific Operations user.
     */
    public function by(User $user): static
    {
        return $this->state(['dispatched_by_user_id' => $user->id]);
    }

    public function dispatched(): static
    {
        return $this->state(['status' => StockDispatchStatus::Dispatched]);
    }

    public function accepted(?User $acceptedBy = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StockDispatchStatus::Accepted,
            'accepted_by_user_id' => $acceptedBy?->id ?? User::factory(),
            'accepted_at' => now(),
        ]);
    }

    public function void(): static
    {
        return $this->state(['status' => StockDispatchStatus::Void]);
    }
}
