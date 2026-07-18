<?php

use App\Enums\StockAdjustmentReason;
use App\Filament\Office\Resources\StockAdjustments\Pages\CreateStockAdjustment;
use App\Filament\Office\Resources\StockAdjustments\Schemas\StockAdjustmentForm;
use App\Models\Position;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The product guard: each line's product must belong to the position's team's
 * catalogue — same invariant as the dispatch guard.
 */
beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));

    $this->team = Team::factory()->strict()->create();
    $this->territory = Territory::factory()->strict()->create();
    $this->position = Position::factory()->create([
        'territory_id' => $this->territory->id,
        'team_id' => $this->team->id,
    ]);

    $this->teamProduct = Product::factory()->create();
    $this->teamProduct->teams()->attach($this->team->id);

    $this->ops = User::factory()->withRole('platform_admin')->create();
    $this->actingAs($this->ops);
});

test('operations is blocked when an adjustment line contains a product outside the position team catalogue', function (): void {
    $foreignTeam = Team::factory()->strict()->create();
    $foreignProduct = Product::factory()->create();
    $foreignProduct->teams()->attach($foreignTeam->id);

    livewire(CreateStockAdjustment::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'adjustment_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $foreignProduct->id, 'quantity_delta' => -5, 'reason' => StockAdjustmentReason::Damage->value],
            ],
        ])
        ->call('create');

    expect(StockAdjustment::count())->toBe(0);
});

test('operations succeeds when an adjustment line contains a product from the position team catalogue', function (): void {
    livewire(CreateStockAdjustment::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'adjustment_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->teamProduct->id, 'quantity_delta' => -5, 'reason' => StockAdjustmentReason::Damage->value],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(StockAdjustment::count())->toBe(1);
});

test('the product select options in the adjustment form are scoped to the position team', function (): void {
    $foreignTeam = Team::factory()->strict()->create();
    $foreignProduct = Product::factory()->create();
    $foreignProduct->teams()->attach($foreignTeam->id);

    $options = StockAdjustmentForm::productOptions($this->position->id);

    expect($options)->toHaveKey($this->teamProduct->id)
        ->and($options)->not->toHaveKey($foreignProduct->id);
});
