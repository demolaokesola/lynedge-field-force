<?php

use App\Filament\Field\Resources\StockCounts\Pages\CreateStockCount as FieldCreateStockCount;
use App\Filament\Office\Resources\StockCounts\Pages\CreateStockCount as OfficeCreateStockCount;
use App\Filament\Shared\Resources\StockCounts\Schemas\StockCountForm;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The product guard: each line's product must belong to the position's team's
 * catalogue — the same invariant Distribution and the other stock documents enforce.
 */
beforeEach(function (): void {
    $this->team = Team::factory()->strict()->create();
    $this->territory = Territory::factory()->strict()->create();
    $this->position = Position::factory()->create([
        'territory_id' => $this->territory->id,
        'team_id' => $this->team->id,
    ]);

    $this->teamProduct = Product::factory()->create();
    $this->teamProduct->teams()->attach($this->team->id);

    $foreignTeam = Team::factory()->strict()->create();
    $this->foreignProduct = Product::factory()->create();
    $this->foreignProduct->teams()->attach($foreignTeam->id);

    $this->ops = User::factory()->withRole('platform_admin')->create();
    $this->rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $this->position->id,
        'user_id' => $this->rep->id,
        'effective_to' => null,
    ]);
});

test('operations is blocked when a line contains a product outside the position team catalogue', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    livewire(OfficeCreateStockCount::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'count_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->foreignProduct->id, 'counted_quantity' => 10],
            ],
        ])
        ->call('create');

    expect(StockCount::count())->toBe(0);
});

test('a rep is blocked when a line contains a product outside the position team catalogue', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $this->actingAs($this->rep);

    livewire(FieldCreateStockCount::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'count_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->foreignProduct->id, 'counted_quantity' => 10],
            ],
        ])
        ->call('create');

    expect(StockCount::count())->toBe(0);
});

test('a line with a product from the position team catalogue is accepted', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    livewire(OfficeCreateStockCount::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'count_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->teamProduct->id, 'counted_quantity' => 10],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(StockCount::count())->toBe(1);
});

test('the same product cannot appear on two lines', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    livewire(OfficeCreateStockCount::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'count_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->teamProduct->id, 'counted_quantity' => 10],
                ['product_id' => $this->teamProduct->id, 'counted_quantity' => 5],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors();

    expect(StockCount::count())->toBe(0);
});

test('the product select options in the count form are scoped to the position team', function (): void {
    $options = StockCountForm::productOptions($this->position->id);

    expect($options)->toHaveKey($this->teamProduct->id)
        ->and($options)->not->toHaveKey($this->foreignProduct->id)
        ->and(StockCountForm::productOptions(null))->toBe([]);
});
