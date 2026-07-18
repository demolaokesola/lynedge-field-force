<?php

use App\Enums\StockDispatchStatus;
use App\Enums\StockMovementType;
use App\Filament\Field\Resources\StockDispatches\Pages\ListStockDispatches as FieldListStockDispatches;
use App\Filament\Office\Resources\StockDispatches\Pages\CreateStockDispatch;
use App\Filament\Office\Resources\StockDispatches\Pages\ListStockDispatches as OfficeListStockDispatches;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionProductStock;
use App\Models\Product;
use App\Models\StockDispatch;
use App\Models\StockDispatchLine;
use App\Models\StockMovement;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * Draft -> Dispatched -> Accepted, with Void possible from Draft or Dispatched.
 * Accepting is the only point stock actually moves.
 */
beforeEach(function (): void {
    $this->team = Team::factory()->strict()->create();
    $this->territory = Territory::factory()->strict()->create();
    $this->position = Position::factory()->create([
        'territory_id' => $this->territory->id,
        'team_id' => $this->team->id,
    ]);

    $this->product = Product::factory()->create();
    $this->product->teams()->attach($this->team->id);

    $this->rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $this->position->id,
        'user_id' => $this->rep->id,
        'effective_to' => null,
    ]);

    $this->ops = User::factory()->withRole('platform_admin')->create();
});

test('operations can create a draft stock dispatch with lines', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    livewire(CreateStockDispatch::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'dispatch_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(StockDispatch::count())->toBe(1);

    $dispatch = StockDispatch::first();
    expect($dispatch->status)->toBe(StockDispatchStatus::Draft)
        ->and($dispatch->dispatched_by_user_id)->toBe($this->ops->id)
        ->and($dispatch->territory_id)->toBe($this->territory->id)
        ->and($dispatch->team_id)->toBe($this->team->id);
});

test('operations can send a draft dispatch, locking it for the rep to accept', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->create();
    StockDispatchLine::factory()->forDispatch($dispatch)->create();

    livewire(OfficeListStockDispatches::class)
        ->callAction(TestAction::make('send')->table($dispatch))
        ->assertNotified();

    expect($dispatch->fresh()->status)->toBe(StockDispatchStatus::Dispatched);
});

test('the rep occupying the position can accept a dispatched shipment, recording a movement and updating the balance', function (): void {
    $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->dispatched()->create();
    $line = StockDispatchLine::factory()->forDispatch($dispatch)->create(['product_id' => $this->product->id, 'quantity' => 10]);

    Filament::setCurrentPanel(Filament::getPanel('field'));
    $this->actingAs($this->rep);

    livewire(FieldListStockDispatches::class)
        ->callAction(TestAction::make('accept')->table($dispatch))
        ->assertNotified();

    $dispatch->refresh();
    expect($dispatch->status)->toBe(StockDispatchStatus::Accepted)
        ->and($dispatch->accepted_by_user_id)->toBe($this->rep->id)
        ->and($dispatch->accepted_at)->not->toBeNull();

    expect(StockMovement::count())->toBe(1);
    $movement = StockMovement::first();
    expect((float) $movement->quantity_delta)->toBe(10.0)
        ->and($movement->type)->toBe(StockMovementType::DispatchAcceptance)
        ->and($movement->position_id)->toBe($this->position->id)
        ->and($movement->product_id)->toBe($line->product_id);

    $balance = PositionProductStock::query()
        ->where('position_id', $this->position->id)
        ->where('product_id', $line->product_id)
        ->first();

    expect((float) $balance->quantity)->toBe(10.0);
});

test('a rep not holding the position cannot accept a dispatch', function (): void {
    $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->dispatched()->create();
    $otherRep = User::factory()->withRole('sales_rep')->create();

    expect($otherRep->can('accept', $dispatch))->toBeFalse();
});

test('a draft or dispatched shipment cannot be accepted', function (): void {
    $draft = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->create();

    expect($this->rep->can('accept', $draft))->toBeFalse();
});

test('operations can void a dispatch before acceptance', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->dispatched()->create();

    livewire(OfficeListStockDispatches::class)
        ->callAction(TestAction::make('void')->table($dispatch))
        ->assertNotified();

    expect($dispatch->fresh()->status)->toBe(StockDispatchStatus::Void);
});

test('an accepted dispatch can no longer be voided', function (): void {
    $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->accepted()->create();

    expect($this->ops->can('void', $dispatch))->toBeFalse();
});

test('a sales_rep cannot create, send, or void a stock dispatch', function (): void {
    $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->create();

    expect($this->rep->can('create', StockDispatch::class))->toBeFalse()
        ->and($this->rep->can('send', $dispatch))->toBeFalse()
        ->and($this->rep->can('void', $dispatch))->toBeFalse();
});
