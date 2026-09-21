<?php

use App\Enums\StockCountKind;
use App\Enums\StockCountStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InvalidStockTransition;
use App\Filament\Field\Resources\StockCounts\Pages\CreateStockCount as FieldCreateStockCount;
use App\Filament\Field\Resources\StockCounts\Pages\ListStockCounts as FieldListStockCounts;
use App\Filament\Office\Resources\StockCounts\Pages\CreateStockCount as OfficeCreateStockCount;
use App\Filament\Office\Resources\StockCounts\Pages\ListStockCounts as OfficeListStockCounts;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionProductStock;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockDispatch;
use App\Models\StockDispatchLine;
use App\Models\StockMovement;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use App\Services\StockCountService;
use App\Services\StockLedger;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * Draft -> Submitted -> Posted, with Void possible from Draft or Submitted. Posting is
 * the only point stock actually moves: each line's system quantity is snapshotted and
 * the variance goes to the ledger. Opening counts may be posted straight from Draft.
 */
beforeEach(function (): void {
    $this->team = Team::factory()->strict()->create();
    $this->territory = Territory::factory()->strict()->create();
    $this->position = Position::factory()->create([
        'territory_id' => $this->territory->id,
        'team_id' => $this->team->id,
    ]);

    $this->productA = Product::factory()->create(['name' => 'Product A']);
    $this->productB = Product::factory()->create(['name' => 'Product B']);
    $this->productA->teams()->attach($this->team->id);
    $this->productB->teams()->attach($this->team->id);

    $this->rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $this->position->id,
        'user_id' => $this->rep->id,
        'effective_to' => null,
    ]);

    $this->ops = User::factory()->withRole('platform_admin')->create();

    $this->seedStock = function (Product $product, string $quantity): void {
        $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->accepted()->create();
        $line = StockDispatchLine::factory()->forDispatch($dispatch)->create(['product_id' => $product->id, 'quantity' => $quantity]);

        app(StockLedger::class)->record($this->position, $product, $quantity, StockMovementType::DispatchAcceptance, $line, $this->ops, today());
    };

    $this->balance = fn (Product $product): ?float => PositionProductStock::query()
        ->where('position_id', $this->position->id)
        ->where('product_id', $product->id)
        ->value('quantity');
});

test('operations can create a draft opening count with lines from the Office panel', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    livewire(OfficeCreateStockCount::class)
        ->fillForm([
            'kind' => StockCountKind::Opening->value,
            'position_id' => $this->position->id,
            'count_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->productA->id, 'counted_quantity' => 40],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $count = StockCount::sole();

    expect($count->kind)->toBe(StockCountKind::Opening)
        ->and($count->status)->toBe(StockCountStatus::Draft)
        ->and($count->counted_by_user_id)->toBe($this->ops->id)
        ->and($count->territory_id)->toBe($this->position->territory_id)
        ->and($count->team_id)->toBe($this->position->team_id)
        ->and($count->lines)->toHaveCount(1)
        ->and((float) $count->lines->first()->counted_quantity)->toBe(40.0)
        ->and($count->lines->first()->system_quantity)->toBeNull();
});

test('posting an opening count from zero sets the counted quantities and zeroes uncounted catalogue products', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    $count = StockCount::factory()->by($this->ops)->forPosition($this->position)->opening()->create(['count_date' => '2026-09-01']);
    StockCountLine::factory()->forCount($count)->create(['product_id' => $this->productA->id, 'counted_quantity' => 40]);

    livewire(OfficeListStockCounts::class)
        ->callAction(TestAction::make('post')->table($count))
        ->assertNotified('Stock count posted');

    $count->refresh();

    expect($count->status)->toBe(StockCountStatus::Posted)
        ->and($count->posted_by_user_id)->toBe($this->ops->id)
        ->and($count->posted_at)->not->toBeNull()
        ->and($count->lines)->toHaveCount(2);

    $lineA = $count->lines->firstWhere('product_id', $this->productA->id);
    $lineB = $count->lines->firstWhere('product_id', $this->productB->id);

    expect((float) $lineA->system_quantity)->toBe(0.0)
        ->and((float) $lineA->variance_quantity)->toBe(40.0)
        ->and((float) $lineB->counted_quantity)->toBe(0.0)
        ->and((float) $lineB->variance_quantity)->toBe(0.0);

    // Only the non-zero variance reaches the ledger.
    $movement = StockMovement::query()->where('type', StockMovementType::StockCount)->sole();

    expect((float) $movement->quantity_delta)->toBe(40.0)
        ->and($movement->product_id)->toBe($this->productA->id)
        ->and($movement->effective_date->toDateString())->toBe('2026-09-01')
        ->and($movement->source->is($lineA))->toBeTrue()
        ->and(($this->balance)($this->productA))->toBe(40.0);
});

test('a rep can create a periodic count for a position they hold, and kind is forced regardless of input', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $this->actingAs($this->rep);

    livewire(FieldCreateStockCount::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'count_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->productA->id, 'counted_quantity' => 12],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $count = StockCount::sole();

    expect($count->kind)->toBe(StockCountKind::Periodic)
        ->and($count->counted_by_user_id)->toBe($this->rep->id)
        ->and($count->status)->toBe(StockCountStatus::Draft);
});

test('a rep cannot create a count for a position they do not hold', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $this->actingAs($this->rep);

    // Strict territories allow one position per team, so the other position lives elsewhere.
    $otherTerritory = Territory::factory()->strict()->create();
    $other = Position::factory()->create(['territory_id' => $otherTerritory->id, 'team_id' => $this->team->id]);

    livewire(FieldCreateStockCount::class)
        ->fillForm([
            'position_id' => $other->id,
            'count_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->productA->id, 'counted_quantity' => 12],
            ],
        ])
        ->call('create');

    expect(StockCount::count())->toBe(0);
});

test('a rep submits their draft, then operations posts it and only the variance hits the ledger', function (): void {
    ($this->seedStock)($this->productA, '50.00');
    ($this->seedStock)($this->productB, '10.00');

    $count = StockCount::factory()->by($this->rep)->forPosition($this->position)->create(['count_date' => '2026-09-15']);
    $lineA = StockCountLine::factory()->forCount($count)->create(['product_id' => $this->productA->id, 'counted_quantity' => 47]);

    Filament::setCurrentPanel(Filament::getPanel('field'));
    $this->actingAs($this->rep);

    livewire(FieldListStockCounts::class)
        ->callAction(TestAction::make('submit')->table($count))
        ->assertNotified('Stock count submitted');

    $count->refresh();
    expect($count->status)->toBe(StockCountStatus::Submitted)
        ->and($count->submitted_at)->not->toBeNull();

    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    livewire(OfficeListStockCounts::class)
        ->callAction(TestAction::make('post')->table($count))
        ->assertNotified('Stock count posted');

    $count->refresh();
    $lineA->refresh();

    expect($count->status)->toBe(StockCountStatus::Posted)
        ->and($count->lines)->toHaveCount(1) // periodic: product B was not counted, so it is left alone
        ->and((float) $lineA->system_quantity)->toBe(50.0)
        ->and((float) $lineA->variance_quantity)->toBe(-3.0);

    $movement = StockMovement::query()->where('type', StockMovementType::StockCount)->sole();

    expect((float) $movement->quantity_delta)->toBe(-3.0)
        ->and($movement->effective_date->toDateString())->toBe('2026-09-15')
        ->and(($this->balance)($this->productA))->toBe(47.0)
        ->and(($this->balance)($this->productB))->toBe(10.0);
});

test('the system quantity is read at post time, not when the count was created', function (): void {
    ($this->seedStock)($this->productA, '50.00');

    $count = StockCount::factory()->by($this->rep)->forPosition($this->position)->submitted()->create();
    StockCountLine::factory()->forCount($count)->create(['product_id' => $this->productA->id, 'counted_quantity' => 50]);

    // Stock arrives between the count and the post.
    ($this->seedStock)($this->productA, '20.00');

    app(StockCountService::class)->post($count, $this->ops);

    $line = $count->lines->first();

    expect((float) $line->system_quantity)->toBe(70.0)
        ->and((float) $line->variance_quantity)->toBe(-20.0)
        ->and(($this->balance)($this->productA))->toBe(50.0);
});

test('a line whose counted quantity matches the system records no movement', function (): void {
    ($this->seedStock)($this->productA, '50.00');

    $count = StockCount::factory()->by($this->rep)->forPosition($this->position)->submitted()->create();
    StockCountLine::factory()->forCount($count)->create(['product_id' => $this->productA->id, 'counted_quantity' => 50]);

    app(StockCountService::class)->post($count, $this->ops);

    expect(StockMovement::query()->where('type', StockMovementType::StockCount)->count())->toBe(0)
        ->and((float) $count->lines->first()->variance_quantity)->toBe(0.0)
        ->and($count->status)->toBe(StockCountStatus::Posted);
});

test('a periodic count cannot be posted straight from draft, but an opening count can', function (): void {
    $periodic = StockCount::factory()->by($this->rep)->forPosition($this->position)->create();
    $opening = StockCount::factory()->by($this->ops)->forPosition($this->position)->opening()->create();

    expect($this->ops->can('post', $periodic))->toBeFalse()
        ->and($this->ops->can('post', $opening))->toBeTrue()
        ->and(fn () => app(StockCountService::class)->post($periodic, $this->ops))->toThrow(InvalidStockTransition::class);

    app(StockCountService::class)->post($opening, $this->ops);

    expect($opening->status)->toBe(StockCountStatus::Posted);
});

test('posting the same count twice is rejected on the second attempt and moves stock only once', function (): void {
    $count = StockCount::factory()->by($this->rep)->forPosition($this->position)->submitted()->create();
    StockCountLine::factory()->forCount($count)->create(['product_id' => $this->productA->id, 'counted_quantity' => 9]);

    $service = app(StockCountService::class);
    $service->post($count, $this->ops);

    expect(fn () => $service->post($count, $this->ops))->toThrow(InvalidStockTransition::class);

    expect(StockMovement::count())->toBe(1)
        ->and(($this->balance)($this->productA))->toBe(9.0);
});

test('operations can void a draft or submitted count, but not a posted one', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    $draft = StockCount::factory()->by($this->rep)->forPosition($this->position)->create();
    $submitted = StockCount::factory()->by($this->rep)->forPosition($this->position)->submitted()->create();
    $posted = StockCount::factory()->by($this->rep)->forPosition($this->position)->posted($this->ops)->create();

    livewire(OfficeListStockCounts::class)
        ->callAction(TestAction::make('void')->table($draft))
        ->assertNotified('Stock count voided')
        ->callAction(TestAction::make('void')->table($submitted))
        ->assertNotified('Stock count voided');

    expect($draft->fresh()->status)->toBe(StockCountStatus::Void)
        ->and($submitted->fresh()->status)->toBe(StockCountStatus::Void)
        ->and($this->ops->can('void', $posted))->toBeFalse()
        ->and(fn () => app(StockCountService::class)->void($posted))->toThrow(InvalidStockTransition::class);
});

test('a posted count is frozen: no update, delete, submit, post, or void', function (): void {
    $posted = StockCount::factory()->by($this->rep)->forPosition($this->position)->posted($this->ops)->create();

    foreach (['update', 'delete', 'submit', 'post', 'void'] as $ability) {
        expect($this->ops->can($ability, $posted))->toBeFalse("ops should not be able to {$ability} a posted count")
            ->and($this->rep->can($ability, $posted))->toBeFalse("rep should not be able to {$ability} a posted count");
    }
});

test('a rep may edit, delete and submit only their own draft, and never post or void', function (): void {
    $own = StockCount::factory()->by($this->rep)->forPosition($this->position)->create();
    $ownSubmitted = StockCount::factory()->by($this->rep)->forPosition($this->position)->submitted()->create();

    $otherRep = User::factory()->withRole('sales_rep')->create();
    $theirs = StockCount::factory()->by($otherRep)->forPosition($this->position)->create();

    expect($this->rep->can('create', StockCount::class))->toBeTrue()
        ->and($this->rep->can('update', $own))->toBeTrue()
        ->and($this->rep->can('delete', $own))->toBeTrue()
        ->and($this->rep->can('submit', $own))->toBeTrue()
        ->and($this->rep->can('post', $own))->toBeFalse()
        ->and($this->rep->can('void', $own))->toBeFalse()
        ->and($this->rep->can('update', $ownSubmitted))->toBeFalse()
        ->and($this->rep->can('submit', $ownSubmitted))->toBeFalse()
        ->and($this->rep->can('update', $theirs))->toBeFalse()
        ->and($this->rep->can('submit', $theirs))->toBeFalse();
});

test('a rep loses edit rights on their draft once they no longer hold the position', function (): void {
    $own = StockCount::factory()->by($this->rep)->forPosition($this->position)->create();

    PositionAssignment::query()->where('user_id', $this->rep->id)->update(['effective_to' => today()->subDay()]);

    expect($this->rep->can('update', $own))->toBeFalse()
        ->and($this->rep->can('submit', $own))->toBeFalse();
});

test('operations may edit any draft count, including a rep\'s', function (): void {
    $repDraft = StockCount::factory()->by($this->rep)->forPosition($this->position)->create();

    expect($this->ops->can('update', $repDraft))->toBeTrue()
        ->and($this->ops->can('delete', $repDraft))->toBeTrue();
});
