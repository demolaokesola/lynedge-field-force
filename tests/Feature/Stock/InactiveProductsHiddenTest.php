<?php

use App\Filament\Office\Resources\StockAdjustments\Schemas\StockAdjustmentForm;
use App\Filament\Office\Resources\StockDispatches\Schemas\StockDispatchForm;
use App\Filament\Shared\Resources\StockCounts\Schemas\StockCountForm;
use App\Models\Position;
use App\Models\Product;
use App\Models\Team;
use App\Models\Territory;

/**
 * Discontinued products stay in the ledger history but cannot be picked on a new
 * dispatch, adjustment or count.
 */
beforeEach(function (): void {
    $team = Team::factory()->strict()->create();
    $this->position = Position::factory()->create([
        'territory_id' => Territory::factory()->strict()->create()->id,
        'team_id' => $team->id,
    ]);

    $this->active = Product::factory()->create(['active' => true]);
    $this->inactive = Product::factory()->create(['active' => false]);
    $this->active->teams()->attach($team->id);
    $this->inactive->teams()->attach($team->id);
});

test('inactive products are excluded from every stock line product select', function (string $form): void {
    $options = $form::productOptions($this->position->id);

    expect($options)->toHaveKey($this->active->id)
        ->and($options)->not->toHaveKey($this->inactive->id);
})->with([
    'dispatch' => [StockDispatchForm::class],
    'adjustment' => [StockAdjustmentForm::class],
    'count' => [StockCountForm::class],
]);
