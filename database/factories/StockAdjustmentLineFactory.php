<?php

namespace Database\Factories;

use App\Enums\StockAdjustmentReason;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustmentLine>
 */
class StockAdjustmentLineFactory extends Factory
{
    /**
     * Assumes an adjustment exists. Creates a fresh product and attaches it to the
     * adjustment's team so the product-guard invariant holds.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adjustment = StockAdjustment::factory()->create();
        $product = Product::factory()->create();
        $product->teams()->attach($adjustment->team_id);

        return [
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product->id,
            'quantity_delta' => fake()->randomFloat(2, -20, 20),
            'reason' => fake()->randomElement(StockAdjustmentReason::cases()),
            'note' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Bind to an existing adjustment, picking a product from its team's catalogue.
     * Creates and attaches a fresh product if the team has none yet.
     */
    public function forAdjustment(StockAdjustment $adjustment): static
    {
        $product = $adjustment->team->products->first()
            ?? tap(Product::factory()->create(), fn (Product $p) => $p->teams()->attach($adjustment->team_id));

        return $this->state([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product->id,
        ]);
    }
}
