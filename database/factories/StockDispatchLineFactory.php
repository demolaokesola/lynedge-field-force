<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockDispatch;
use App\Models\StockDispatchLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockDispatchLine>
 */
class StockDispatchLineFactory extends Factory
{
    /**
     * Assumes a dispatch exists. Creates a fresh product and attaches it to the
     * dispatch's team so the product-guard invariant holds.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dispatch = StockDispatch::factory()->create();
        $product = Product::factory()->create();
        $product->teams()->attach($dispatch->team_id);

        return [
            'stock_dispatch_id' => $dispatch->id,
            'product_id' => $product->id,
            'quantity' => fake()->randomFloat(2, 1, 100),
        ];
    }

    /**
     * Bind to an existing dispatch, picking a product from its team's catalogue.
     * Creates and attaches a fresh product if the team has none yet.
     */
    public function forDispatch(StockDispatch $dispatch): static
    {
        $product = $dispatch->team->products->first()
            ?? tap(Product::factory()->create(), fn (Product $p) => $p->teams()->attach($dispatch->team_id));

        return $this->state([
            'stock_dispatch_id' => $dispatch->id,
            'product_id' => $product->id,
        ]);
    }
}
