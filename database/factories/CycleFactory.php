<?php

namespace Database\Factories;

use App\Models\Cycle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Cycle>
 */
class CycleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = Carbon::parse('2025-02-01');

        return [
            'name' => '2025/2026',
            'starts_on' => $startsOn,
            'ends_on' => $startsOn->copy()->addYear()->subDay(),
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(['is_current' => true]);
    }
}
