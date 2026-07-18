<?php

use App\Filament\Office\Resources\StockDispatches\Pages\CreateStockDispatch;
use App\Filament\Office\Resources\StockDispatches\Schemas\StockDispatchForm;
use App\Models\Position;
use App\Models\Product;
use App\Models\StockDispatch;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The product guard: each line's product must belong to the position's team's
 * catalogue — the same invariant Distribution enforces.
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

test('operations is blocked when a line contains a product outside the position team catalogue', function (): void {
    $foreignTeam = Team::factory()->strict()->create();
    $foreignProduct = Product::factory()->create();
    $foreignProduct->teams()->attach($foreignTeam->id);

    livewire(CreateStockDispatch::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'dispatch_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $foreignProduct->id, 'quantity' => 10],
            ],
        ])
        ->call('create');

    expect(StockDispatch::count())->toBe(0);
});

test('operations succeeds when a line contains a product from the position team catalogue', function (): void {
    livewire(CreateStockDispatch::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'dispatch_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->teamProduct->id, 'quantity' => 10],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(StockDispatch::count())->toBe(1);
});

test('a liberal position accepts any product in its liberal team catalogue', function (): void {
    $liberalTeam = Team::factory()->liberal()->create();
    $liberalTerritory = Territory::factory()->liberal()->create();
    $liberalPosition = Position::factory()->create([
        'territory_id' => $liberalTerritory->id,
        'team_id' => $liberalTeam->id,
    ]);

    $productA = Product::factory()->create();
    $productB = Product::factory()->create();
    $productA->teams()->attach($liberalTeam->id);
    $productB->teams()->attach($liberalTeam->id);

    livewire(CreateStockDispatch::class)
        ->fillForm([
            'position_id' => $liberalPosition->id,
            'dispatch_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $productA->id, 'quantity' => 5],
                ['product_id' => $productB->id, 'quantity' => 3],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(StockDispatch::count())->toBe(1);
});

test('the product select options in the dispatch form are scoped to the position team', function (): void {
    $foreignTeam = Team::factory()->strict()->create();
    $foreignProduct = Product::factory()->create();
    $foreignProduct->teams()->attach($foreignTeam->id);

    $options = StockDispatchForm::productOptions($this->position->id);

    expect($options)->toHaveKey($this->teamProduct->id)
        ->and($options)->not->toHaveKey($foreignProduct->id);
});
