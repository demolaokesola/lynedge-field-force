<?php

namespace Database\Factories;

use App\Enums\TeamKind;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Team '.Str::upper(fake()->unique()->randomLetter());

        return [
            'name' => $name,
            'code' => Str::slug($name),
            'kind' => fake()->randomElement(TeamKind::cases()),
            'active' => true,
        ];
    }

    public function strict(): static
    {
        return $this->state(['kind' => TeamKind::Strict]);
    }

    public function liberal(): static
    {
        return $this->state(['kind' => TeamKind::Liberal]);
    }
}
