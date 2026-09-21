<?php

use App\Enums\DistributionStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStock;
use App\Exceptions\InvalidStockTransition;
use App\Filament\Shared\Resources\Distributions\Pages\ListDistributions;
use App\Models\Customer;
use App\Models\Distribution;
use App\Models\DistributionLine;
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
use App\Services\DistributionPostingService;
use App\Services\StockLedger;
use App\Services\StockSettings;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * Posting a distribution draws each line down from the position's balance — but only
 * once the Office go-live switch is on, and subject to the warn-or-block switch.
 */
beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));

    $this->team = Team::factory()->strict()->create();
    $this->territory = Territory::factory()->strict()->create();
    $this->position = Position::factory()->create([
        'territory_id' => $this->territory->id,
        'team_id' => $this->team->id,
    ]);
    $this->customer = Customer::factory()->create(['territory_id' => $this->territory->id]);

    $this->product = Product::factory()->create(['name' => 'Amoxil 500mg']);
    $this->product->teams()->attach($this->team->id);

    $this->rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $this->position->id,
        'user_id' => $this->rep->id,
        'effective_to' => null,
    ]);
    $this->ops = User::factory()->withRole('platform_admin')->create();

    $this->actingAs($this->rep);

    // Seed a balance the honest way — through the ledger — so the recompute on the
    // next movement does not wipe it.
    $this->seedStock = function (string $quantity): void {
        $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->accepted()->create();
        $line = StockDispatchLine::factory()->forDispatch($dispatch)->create(['product_id' => $this->product->id, 'quantity' => $quantity]);

        app(StockLedger::class)->record($this->position, $this->product, $quantity, StockMovementType::DispatchAcceptance, $line, $this->ops, today());
    };

    $this->draftDistribution = function (string $quantity): Distribution {
        $distribution = Distribution::factory()->by($this->rep)->forPosition($this->position)->create([
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-09-10',
        ]);
        DistributionLine::factory()->forDistribution($distribution)->create([
            'product_id' => $this->product->id,
            'quantity' => $quantity,
        ]);

        return $distribution;
    };

    $this->balance = fn (): float => (float) PositionProductStock::query()
        ->where('position_id', $this->position->id)
        ->where('product_id', $this->product->id)
        ->value('quantity');
});

test('with consumption off, posting flips status but records no movement', function (): void {
    ($this->seedStock)('20.00');
    $distribution = ($this->draftDistribution)('5.00');

    livewire(ListDistributions::class)
        ->callAction(TestAction::make('post')->table($distribution))
        ->assertNotified('Distribution posted');

    expect($distribution->fresh()->status)->toBe(DistributionStatus::Posted)
        ->and(StockMovement::where('type', StockMovementType::Distribution)->count())->toBe(0)
        ->and(($this->balance)())->toBe(20.0);
});

test('with consumption on, posting records a negative movement per line dated to the invoice and lowers the balance', function (): void {
    app(StockSettings::class)->setConsumptionEnabled(true);
    ($this->seedStock)('20.00');
    $distribution = ($this->draftDistribution)('5.00');

    livewire(ListDistributions::class)
        ->callAction(TestAction::make('post')->table($distribution))
        ->assertNotified('Distribution posted');

    $movement = StockMovement::query()->where('type', StockMovementType::Distribution)->sole();

    expect($distribution->fresh()->status)->toBe(DistributionStatus::Posted)
        ->and((float) $movement->quantity_delta)->toBe(-5.0)
        ->and($movement->effective_date->toDateString())->toBe('2026-09-10')
        ->and($movement->caused_by_user_id)->toBe($this->rep->id)
        ->and($movement->source->is($distribution->lines->first()))->toBeTrue()
        ->and(($this->balance)())->toBe(15.0);
});

test('with block off, a short line is listed as a warning and the balance is allowed to go negative', function (): void {
    app(StockSettings::class)->setConsumptionEnabled(true);
    ($this->seedStock)('3.00');
    $distribution = ($this->draftDistribution)('8.00');

    $shortfalls = app(DistributionPostingService::class)->shortfalls($distribution);

    expect($shortfalls)->toBe([
        ['product' => 'Amoxil 500mg', 'available' => '3.00', 'requested' => '8.00'],
    ]);

    livewire(ListDistributions::class)
        ->callAction(TestAction::make('post')->table($distribution))
        ->assertNotified('Distribution posted');

    expect($distribution->fresh()->status)->toBe(DistributionStatus::Posted)
        ->and(($this->balance)())->toBe(-5.0);
});

test('with block on, a short line is refused: the distribution stays draft and nothing hits the ledger', function (): void {
    app(StockSettings::class)->setConsumptionEnabled(true);
    app(StockSettings::class)->setBlockNegativeStock(true);
    ($this->seedStock)('3.00');
    $distribution = ($this->draftDistribution)('8.00');

    livewire(ListDistributions::class)
        ->callAction(TestAction::make('post')->table($distribution))
        ->assertNotified('Insufficient stock');

    expect($distribution->fresh()->status)->toBe(DistributionStatus::Draft)
        ->and(StockMovement::where('type', StockMovementType::Distribution)->count())->toBe(0)
        ->and(($this->balance)())->toBe(3.0);
});

test('the service throws InsufficientStock carrying the shortfalls when block is on', function (): void {
    app(StockSettings::class)->setConsumptionEnabled(true);
    app(StockSettings::class)->setBlockNegativeStock(true);
    $distribution = ($this->draftDistribution)('8.00');

    try {
        app(DistributionPostingService::class)->post($distribution, $this->rep);
        $this->fail('Expected InsufficientStock');
    } catch (InsufficientStock $exception) {
        expect($exception->shortfalls)->toBe([
            ['product' => 'Amoxil 500mg', 'available' => '0', 'requested' => '8.00'],
        ]);
    }
});

test('a product with no balance row at all counts as zero on hand', function (): void {
    app(StockSettings::class)->setConsumptionEnabled(true);
    $distribution = ($this->draftDistribution)('2.00');

    expect(app(DistributionPostingService::class)->shortfalls($distribution))->toHaveCount(1);
});

test('shortfalls are empty while consumption is off, even when stock is short', function (): void {
    $distribution = ($this->draftDistribution)('8.00');

    expect(app(DistributionPostingService::class)->shortfalls($distribution))->toBe([]);
});

test('posting twice is rejected on the second attempt and records the movements only once', function (): void {
    app(StockSettings::class)->setConsumptionEnabled(true);
    ($this->seedStock)('20.00');
    $distribution = ($this->draftDistribution)('5.00');

    $service = app(DistributionPostingService::class);
    $service->post($distribution, $this->rep);

    expect(fn () => $service->post($distribution, $this->rep))->toThrow(InvalidStockTransition::class);

    expect(StockMovement::where('type', StockMovementType::Distribution)->count())->toBe(1)
        ->and(($this->balance)())->toBe(15.0);
});
