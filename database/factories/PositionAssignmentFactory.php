<?php

namespace Database\Factories;

use App\Enums\AssignmentStatus;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PositionAssignment>
 */
class PositionAssignmentFactory extends Factory
{
    /**
     * Defaults to an open (active) assignment effective today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'position_id' => Position::factory(),
            'user_id' => User::factory(),
            'effective_from' => today(),
            'effective_to' => null,
            'status' => AssignmentStatus::Active,
            'notes' => null,
        ];
    }

    /**
     * An assignment that has already ended on the given date.
     */
    public function ended(?Carbon $on = null): static
    {
        $on ??= today();

        return $this->state([
            'effective_to' => $on,
            'status' => AssignmentStatus::Ended,
        ]);
    }
}
