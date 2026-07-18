<?php

use App\Enums\StockMovementType;
use App\Models\Position;
use App\Models\PositionProductStock;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use App\Models\StockDispatch;
use App\Models\StockDispatchLine;
use App\Models\StockMovement;
use App\Models\Team;
use App\Models\User;
use App\Services\StockLedger;

/**
 * StockLedger is the single choke point for "stock actually changed" — it inserts an
 * immutable movement and recomputes the (position, product) balance from the full
 * movement history, never trusting an incremental update.
 */
beforeEach(function (): void {
    $this->team = Team::factory()->strict()->create();
    $this->position = Position::factory()->create(['team_id' => $this->team->id]);
    $this->product = Product::factory()->create();
    $this->product->teams()->attach($this->team->id);
    $this->causer = User::factory()->withRole('platform_admin')->create();
});

test('recording a movement inserts an immutable ledger row denormalised from the position', function (): void {
    $dispatch = StockDispatch::factory()->forPosition($this->position)->create();
    $line = StockDispatchLine::factory()->forDispatch($dispatch)->create(['product_id' => $this->product->id]);

    $movement = app(StockLedger::class)->record(
        $this->position,
        $this->product,
        '25.00',
        StockMovementType::DispatchAcceptance,
        $line,
        $this->causer,
    );

    expect($movement->position_id)->toBe($this->position->id)
        ->and($movement->territory_id)->toBe($this->position->territory_id)
        ->and($movement->team_id)->toBe($this->position->team_id)
        ->and((float) $movement->quantity_delta)->toBe(25.0)
        ->and($movement->type)->toBe(StockMovementType::DispatchAcceptance)
        ->and($movement->caused_by_user_id)->toBe($this->causer->id)
        ->and($movement->source->is($line))->toBeTrue();
});

test('recording a movement creates the balance row for a first-time (position, product) pair', function (): void {
    $dispatch = StockDispatch::factory()->forPosition($this->position)->create();
    $line = StockDispatchLine::factory()->forDispatch($dispatch)->create(['product_id' => $this->product->id]);

    app(StockLedger::class)->record($this->position, $this->product, '40.00', StockMovementType::DispatchAcceptance, $line, $this->causer);

    $balance = PositionProductStock::query()
        ->where('position_id', $this->position->id)
        ->where('product_id', $this->product->id)
        ->first();

    expect((float) $balance->quantity)->toBe(40.0);
});

test('the balance is recomputed from the full movement history, and may go negative', function (): void {
    $dispatch = StockDispatch::factory()->forPosition($this->position)->create();
    $dispatchLine = StockDispatchLine::factory()->forDispatch($dispatch)->create(['product_id' => $this->product->id]);

    $adjustment = StockAdjustment::factory()->forPosition($this->position)->create();
    $adjustmentLine = StockAdjustmentLine::factory()->forAdjustment($adjustment)->create(['product_id' => $this->product->id]);

    $ledger = app(StockLedger::class);
    $ledger->record($this->position, $this->product, '10.00', StockMovementType::DispatchAcceptance, $dispatchLine, $this->causer);
    $ledger->record($this->position, $this->product, '-30.00', StockMovementType::Adjustment, $adjustmentLine, $this->causer);

    expect(StockMovement::count())->toBe(2);

    $balance = PositionProductStock::query()
        ->where('position_id', $this->position->id)
        ->where('product_id', $this->product->id)
        ->first();

    expect((float) $balance->quantity)->toBe(-20.0);
});
