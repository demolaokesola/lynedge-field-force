<?php

namespace Database\Factories;

use App\Enums\CallType;
use App\Models\Call;
use App\Models\DemandCreator;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Call>
 */
class CallFactory extends Factory
{
    /**
     * A physical visit by a fresh rep, with territory and demand creator kept consistent
     * with the call's position (territory_id is denormalised from the position).
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
            'demand_creator_id' => DemandCreator::factory()->state(['territory_id' => $position->territory_id]),
            'call_type' => CallType::PhysicalVisit,
            'called_at' => now(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Pin the call to a given position, keeping territory_id in sync.
     */
    public function forPosition(Position $position): static
    {
        return $this->state([
            'position_id' => $position->id,
            'territory_id' => $position->territory_id,
        ]);
    }

    /**
     * Attribute the call to a specific rep.
     */
    public function by(User $rep): static
    {
        return $this->state(['user_id' => $rep->id]);
    }

    public function phoneCall(): static
    {
        return $this->state(['call_type' => CallType::PhoneCall]);
    }
}
