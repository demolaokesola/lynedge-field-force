<?php

namespace Database\Factories;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\Team;
use App\Models\Territory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    /**
     * Defaults to a valid strict (territory, team) pair so the kind-match holds.
     * `code` is observer-derived (PositionObserver::deriveCode()) and not set here.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'territory_id' => Territory::factory()->strict(),
            'team_id' => Team::factory()->strict(),
            'label' => fake()->jobTitle(),
            'status' => PositionStatus::Active,
        ];
    }

    /**
     * A position in a strict territory manning a strict team.
     */
    public function strict(): static
    {
        return $this->state([
            'territory_id' => Territory::factory()->strict(),
            'team_id' => Team::factory()->strict(),
        ]);
    }

    /**
     * A position in a liberal territory manning a liberal team.
     */
    public function liberal(): static
    {
        return $this->state([
            'territory_id' => Territory::factory()->liberal(),
            'team_id' => Team::factory()->liberal(),
        ]);
    }

    public function frozen(): static
    {
        return $this->state(['status' => PositionStatus::Frozen]);
    }
}
