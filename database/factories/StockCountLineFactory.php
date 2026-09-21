<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockCountLine>
 */
class StockCountLineFactory extends Factory
{
    /**
     * Assumes a count exists. Creates a fresh product and attaches it to the count's
     * team so the product-guard invariant holds.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $count = StockCount::factory()->create();
        $product = Product::factory()->create();
        $product->teams()->attach($count->team_id);

        return [
            'stock_count_id' => $count->id,
            'product_id' => $product->id,
            'counted_quantity' => fake()->randomFloat(2, 0, 100),
        ];
    }

    /**
     * Bind to an existing count, picking a product from its team's catalogue.
     * Creates and attaches a fresh product if the team has none yet.
     */
    public function forCount(StockCount $count): static
    {
        $product = $count->team->products->first()
            ?? tap(Product::factory()->create(), fn (Product $p) => $p->teams()->attach($count->team_id));

        return $this->state([
            'stock_count_id' => $count->id,
            'product_id' => $product->id,
        ]);
    }
}
