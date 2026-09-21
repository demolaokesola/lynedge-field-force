<?php

namespace Database\Factories;

use App\Enums\StockCountKind;
use App\Enums\StockCountStatus;
use App\Models\Position;
use App\Models\StockCount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockCount>
 */
class StockCountFactory extends Factory
{
    /**
     * Defaults to a draft periodic count, with territory_id and team_id kept consistent
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
            'kind' => StockCountKind::Periodic,
            'count_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'status' => StockCountStatus::Draft,
            'counted_by_user_id' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Pin the count to a given position, keeping territory_id and team_id in sync.
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
     * Attribute the count to the user who did the counting.
     */
    public function by(User $user): static
    {
        return $this->state(['counted_by_user_id' => $user->id]);
    }

    public function opening(): static
    {
        return $this->state(['kind' => StockCountKind::Opening]);
    }

    public function submitted(): static
    {
        return $this->state([
            'status' => StockCountStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function posted(?User $postedBy = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StockCountStatus::Posted,
            'submitted_at' => now(),
            'posted_by_user_id' => $postedBy?->id ?? User::factory(),
            'posted_at' => now(),
        ]);
    }

    public function void(): static
    {
        return $this->state(['status' => StockCountStatus::Void]);
    }
}
