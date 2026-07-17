<?php

use App\Models\Distribution;
use App\Models\DistributionLine;
use App\Models\Product;

/**
 * unit_price on a DistributionLine must always come from the product's current price —
 * a field officer (or any other caller) can never set/override it directly.
 */
test('unit_price is always derived from the product, ignoring whatever is assigned', function (): void {
    $distribution = Distribution::factory()->create();
    $product = Product::factory()->create(['unit_price' => '1000.00']);
    $product->teams()->attach($distribution->team_id);

    $line = DistributionLine::create([
        'distribution_id' => $distribution->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '1.00', // deliberately wrong — must be ignored
    ]);

    expect((float) $line->unit_price->amount)->toBe(1000.0)
        ->and((float) $line->line_amount->amount)->toBe(2000.0);
});

test('updating a line re-derives unit_price from the product\'s current price', function (): void {
    $distribution = Distribution::factory()->create();
    $product = Product::factory()->create(['unit_price' => '1000.00']);
    $product->teams()->attach($distribution->team_id);

    $line = DistributionLine::create([
        'distribution_id' => $distribution->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '1000.00',
    ]);

    $product->update(['unit_price' => '1500.00']);

    $line->update(['unit_price' => '1.00', 'quantity' => 3]);

    expect((float) $line->fresh()->unit_price->amount)->toBe(1500.0)
        ->and((float) $line->fresh()->line_amount->amount)->toBe(4500.0);
});
