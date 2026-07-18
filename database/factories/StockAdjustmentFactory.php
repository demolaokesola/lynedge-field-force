<?php

namespace Database\Factories;

use App\Enums\StockAdjustmentStatus;
use App\Models\Position;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
{
    /**
     * Defaults to a draft adjustment, with territory_id and team_id kept consistent
     * with the position (both are denormalised at write time).
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
            'adjusted_by_user_id' => User::factory(),
            'adjustment_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'status' => StockAdjustmentStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Pin the adjustment to a given position, keeping territory_id and team_id in sync.
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
     * Attribute the adjustment to a specific Operations user.
     */
    public function by(User $user): static
    {
        return $this->state(['adjusted_by_user_id' => $user->id]);
    }

    public function posted(): static
    {
        return $this->state(['status' => StockAdjustmentStatus::Posted]);
    }

    public function void(): static
    {
        return $this->state(['status' => StockAdjustmentStatus::Void]);
    }
}
