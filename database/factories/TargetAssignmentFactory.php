<?php

namespace Database\Factories;

use App\Enums\AssignmentReason;
use App\Enums\TargetBasis;
use App\Models\Cycle;
use App\Models\TargetAssignment;
use App\Models\TargetTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TargetAssignment>
 */
class TargetAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cycle_id' => Cycle::factory(),
            'user_id' => User::factory(),
            'position_id' => null,
            'target_tier_id' => TargetTier::factory(),
            'basis' => TargetBasis::Tier,
            'effective_from' => fake()->date(),
            'effective_to' => null,
            'reason' => AssignmentReason::Initial,
            'notes' => null,
        ];
    }
}
