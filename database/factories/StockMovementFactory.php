<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Position;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockLedger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Direct factory use is mainly for scope/query tests — production code only ever
     * creates movements through {@see StockLedger}.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $position = Position::factory()->create();
        $product = Product::factory()->create();
        $product->teams()->attach($position->team_id);

        return [
            'position_id' => $position->id,
            'territory_id' => $position->territory_id,
            'team_id' => $position->team_id,
            'product_id' => $product->id,
            'quantity_delta' => fake()->randomFloat(2, -20, 20),
            'type' => fake()->randomElement(StockMovementType::cases()),
            'caused_by_user_id' => User::factory(),
        ];
    }

    /**
     * Pin the movement to a given position, keeping territory_id and team_id in sync.
     */
    public function forPosition(Position $position): static
    {
        return $this->state([
            'position_id' => $position->id,
            'territory_id' => $position->territory_id,
            'team_id' => $position->team_id,
        ]);
    }
}
