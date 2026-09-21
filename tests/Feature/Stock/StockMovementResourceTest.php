<?php

use App\Enums\StockMovementType;
use App\Filament\Field\Resources\StockMovements\Pages\ListStockMovements as FieldListStockMovements;
use App\Filament\Office\Resources\StockMovements\Pages\ListStockMovements as OfficeListStockMovements;
use App\Filament\Shared\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\Distribution;
use App\Models\DistributionLine;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockDispatch;
use App\Models\StockDispatchLine;
use App\Models\StockMovement;
use App\Models\Territory;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The ledger is exposed read-only in the Field (own/supervised positions) and Office
 * (everything) panels, with the originating document line described per row.
 */
beforeEach(function (): void {
    $this->territory = Territory::factory()->create();
    $this->position = Position::factory()->create(['territory_id' => $this->territory->id]);
    $this->otherPosition = Position::factory()->create(['territory_id' => $this->territory->id]);

    $this->own = StockMovement::factory()->forPosition($this->position)->create();
    $this->foreign = StockMovement::factory()->forPosition($this->otherPosition)->create();
});

test('a rep sees only movements for positions they hold in the Field panel', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create(['position_id' => $this->position->id, 'user_id' => $rep->id, 'effective_to' => null]);
    $this->actingAs($rep);

    livewire(FieldListStockMovements::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->own])
        ->assertCanNotSeeTableRecords([$this->foreign]);
});

test('operations sees every movement in the Office panel and can filter by type', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('platform_admin')->create());

    $this->own->update(['type' => StockMovementType::Distribution]);
    $this->foreign->update(['type' => StockMovementType::Adjustment]);

    livewire(OfficeListStockMovements::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->own, $this->foreign])
        ->filterTable('type', StockMovementType::Distribution->value)
        ->assertCanSeeTableRecords([$this->own])
        ->assertCanNotSeeTableRecords([$this->foreign]);
});

test('the ledger can be narrowed to an effective-date window', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('platform_admin')->create());

    $this->own->update(['effective_date' => '2026-03-01']);
    $this->foreign->update(['effective_date' => '2026-06-01']);

    livewire(OfficeListStockMovements::class)
        ->filterTable('effective_date', ['from' => '2026-05-01', 'until' => '2026-06-30'])
        ->assertCanSeeTableRecords([$this->foreign])
        ->assertCanNotSeeTableRecords([$this->own]);
});

test('the ledger is append-only for everyone, including platform_admin', function (): void {
    $ops = User::factory()->withRole('platform_admin')->create();

    expect($ops->can('viewAny', StockMovement::class))->toBeTrue()
        ->and($ops->can('create', StockMovement::class))->toBeFalse()
        ->and($ops->can('update', $this->own))->toBeFalse()
        ->and($ops->can('delete', $this->own))->toBeFalse();
});

test('each source line is described by its parent document', function (): void {
    $dispatch = StockDispatch::factory()->forPosition($this->position)->create();
    $dispatchLine = StockDispatchLine::factory()->forDispatch($dispatch)->create();

    $adjustment = StockAdjustment::factory()->forPosition($this->position)->create();
    $adjustmentLine = StockAdjustmentLine::factory()->forAdjustment($adjustment)->create();

    $count = StockCount::factory()->forPosition($this->position)->create();
    $countLine = StockCountLine::factory()->forCount($count)->create();

    $distribution = Distribution::factory()->forPosition($this->position)->create(['invoice_number' => 'INV-0042']);
    $distributionLine = DistributionLine::factory()->forDistribution($distribution)->create();

    expect(StockMovementsTable::describeSource($dispatchLine))->toBe("Dispatch #{$dispatch->id}")
        ->and(StockMovementsTable::describeSource($adjustmentLine))->toBe("Adjustment #{$adjustment->id}")
        ->and(StockMovementsTable::describeSource($countLine))->toBe("Count #{$count->id}")
        ->and(StockMovementsTable::describeSource($distributionLine))->toBe('Invoice INV-0042')
        ->and(StockMovementsTable::describeSource(null))->toBe('—');
});
