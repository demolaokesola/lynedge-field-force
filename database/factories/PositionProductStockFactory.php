<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\PositionProductStock;
use App\Models\Product;
use App\Services\StockLedger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PositionProductStock>
 */
class PositionProductStockFactory extends Factory
{
    /**
     * Direct factory use is mainly for scope/query tests — production code only ever
     * writes balances through {@see StockLedger}.
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
            'product_id' => $product->id,
            'quantity' => fake()->randomFloat(2, -10, 100),
        ];
    }

    /**
     * Pin the balance to a given position.
     */
    public function forPosition(Position $position): static
    {
        return $this->state(['position_id' => $position->id]);
    }
}
