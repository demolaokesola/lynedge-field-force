<?php

use App\Enums\StockAdjustmentReason;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Filament\Office\Resources\StockAdjustments\Pages\CreateStockAdjustment;
use App\Filament\Office\Resources\StockAdjustments\Pages\ListStockAdjustments;
use App\Models\Position;
use App\Models\PositionProductStock;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use App\Models\StockMovement;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * Draft -> Posted, with Void possible from Draft only — mirrors DistributionStatus's
 * lifecycle. Posting is the only point stock actually moves, and quantity_delta may be
 * negative (damage, loss, recall, return) — balances are allowed to go negative.
 */
beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));

    $this->team = Team::factory()->strict()->create();
    $this->territory = Territory::factory()->strict()->create();
    $this->position = Position::factory()->create([
        'territory_id' => $this->territory->id,
        'team_id' => $this->team->id,
    ]);

    $this->product = Product::factory()->create();
    $this->product->teams()->attach($this->team->id);

    $this->ops = User::factory()->withRole('platform_admin')->create();
    $this->rep = User::factory()->withRole('sales_rep')->create();
    $this->actingAs($this->ops);
});

test('operations can create a draft adjustment with a signed quantity and reason', function (): void {
    livewire(CreateStockAdjustment::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'adjustment_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->product->id, 'quantity_delta' => -5, 'reason' => StockAdjustmentReason::Damage->value],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(StockAdjustment::count())->toBe(1);

    $adjustment = StockAdjustment::first();
    expect($adjustment->status)->toBe(StockAdjustmentStatus::Draft)
        ->and($adjustment->adjusted_by_user_id)->toBe($this->ops->id)
        ->and((float) $adjustment->lines->first()->quantity_delta)->toBe(-5.0);
});

test('posting an adjustment records movements — including negative deltas — and lets the balance go negative', function (): void {
    $adjustment = StockAdjustment::factory()->by($this->ops)->forPosition($this->position)->create();
    $line = StockAdjustmentLine::factory()->forAdjustment($adjustment)->create([
        'product_id' => $this->product->id,
        'quantity_delta' => -15,
        'reason' => StockAdjustmentReason::Loss,
    ]);

    livewire(ListStockAdjustments::class)
        ->callAction(TestAction::make('post')->table($adjustment))
        ->assertNotified();

    expect($adjustment->fresh()->status)->toBe(StockAdjustmentStatus::Posted);

    expect(StockMovement::count())->toBe(1);
    $movement = StockMovement::first();
    expect((float) $movement->quantity_delta)->toBe(-15.0)
        ->and($movement->type)->toBe(StockMovementType::Adjustment)
        ->and($movement->position_id)->toBe($this->position->id);

    $balance = PositionProductStock::query()
        ->where('position_id', $this->position->id)
        ->where('product_id', $line->product_id)
        ->first();

    expect((float) $balance->quantity)->toBe(-15.0);
});

test('operations can void a draft adjustment', function (): void {
    $adjustment = StockAdjustment::factory()->by($this->ops)->forPosition($this->position)->create();

    livewire(ListStockAdjustments::class)
        ->callAction(TestAction::make('void')->table($adjustment))
        ->assertNotified();

    expect($adjustment->fresh()->status)->toBe(StockAdjustmentStatus::Void);
});

test('a posted adjustment can no longer be edited, deleted, voided, or posted again', function (): void {
    $adjustment = StockAdjustment::factory()->by($this->ops)->forPosition($this->position)->posted()->create();

    expect($this->ops->can('update', $adjustment))->toBeFalse()
        ->and($this->ops->can('delete', $adjustment))->toBeFalse()
        ->and($this->ops->can('void', $adjustment))->toBeFalse()
        ->and($this->ops->can('post', $adjustment))->toBeFalse();
});

test('a sales_rep cannot create, edit, post, or void a stock adjustment', function (): void {
    $adjustment = StockAdjustment::factory()->by($this->ops)->forPosition($this->position)->create();

    expect($this->rep->can('create', StockAdjustment::class))->toBeFalse()
        ->and($this->rep->can('update', $adjustment))->toBeFalse()
        ->and($this->rep->can('post', $adjustment))->toBeFalse()
        ->and($this->rep->can('void', $adjustment))->toBeFalse();
});
