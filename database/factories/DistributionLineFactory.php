<?php

namespace Database\Factories;

use App\Models\Distribution;
use App\Models\DistributionLine;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DistributionLine>
 */
class DistributionLineFactory extends Factory
{
    /**
     * Assumes a distribution exists. Creates a fresh product and attaches it to the
     * distribution's team so the product-guard invariant holds.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $distribution = Distribution::factory()->create();
        $product = Product::factory()->create();
        $product->teams()->attach($distribution->team_id);

        $quantity = fake()->randomFloat(2, 1, 50);
        $unitPrice = fake()->randomFloat(2, 100, 5000);

        return [
            'distribution_id' => $distribution->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_amount' => bcmul((string) $quantity, (string) $unitPrice, 2),
        ];
    }

    /**
     * Bind to an existing distribution, picking a product from its team's catalogue.
     * Creates and attaches a fresh product if the team has none yet.
     */
    public function forDistribution(Distribution $distribution): static
    {
        $product = $distribution->team->products->first()
            ?? tap(Product::factory()->create(), fn (Product $p) => $p->teams()->attach($distribution->team_id));

        $quantity = fake()->randomFloat(2, 1, 50);
        $unitPrice = fake()->randomFloat(2, 100, 5000);

        return $this->state([
            'distribution_id' => $distribution->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_amount' => bcmul((string) $quantity, (string) $unitPrice, 2),
        ]);
    }
}
