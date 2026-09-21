<?php

use App\Filament\Field\Resources\StockCounts\Pages\ListStockCounts as FieldListStockCounts;
use App\Filament\Field\Resources\StockDispatches\Pages\ListStockDispatches as FieldListStockDispatches;
use App\Filament\Office\Resources\StockDispatches\Pages\ListStockDispatches as OfficeListStockDispatches;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockDispatch;
use App\Models\StockDispatchLine;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use App\Notifications\StockCountSubmitted;
use App\Notifications\StockDispatchAccepted;
use App\Notifications\StockDispatchSent;
use App\Services\StockDispatchService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;

use function Pest\Livewire\livewire;

/**
 * Who is told what: the position's current occupant when a dispatch is sent to them;
 * every platform_admin when a dispatch is accepted or a count is submitted.
 */
beforeEach(function (): void {
    Notification::fake();

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
    $this->formerRep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $this->position->id,
        'user_id' => $this->formerRep->id,
        'effective_from' => today()->subYear(),
        'effective_to' => today()->subMonth(),
    ]);

    $this->ops = User::factory()->withRole('platform_admin')->create();
    $this->otherOps = User::factory()->withRole('platform_admin')->create();
    $this->accountant = User::factory()->withRole('accountant')->create();
});

test('sending a dispatch notifies the current occupant of the position, not former ones', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs($this->ops);

    $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->create();
    StockDispatchLine::factory()->forDispatch($dispatch)->create(['product_id' => $this->product->id]);

    livewire(OfficeListStockDispatches::class)
        ->callAction(TestAction::make('send')->table($dispatch))
        ->assertNotified('Dispatch sent');

    Notification::assertSentTo($this->rep, StockDispatchSent::class, fn (StockDispatchSent $n): bool => $n->dispatch->is($dispatch));
    Notification::assertNotSentTo($this->formerRep, StockDispatchSent::class);
    Notification::assertNotSentTo($this->ops, StockDispatchSent::class);
});

test('sending a dispatch to a vacant position notifies nobody but still sends', function (): void {
    $vacant = Position::factory()->create(['territory_id' => Territory::factory()->strict()->create()->id, 'team_id' => $this->team->id]);
    $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($vacant)->create();

    app(StockDispatchService::class)->send($dispatch);

    expect($dispatch->fresh()->status->value)->toBe('dispatched');
    Notification::assertNothingSent();
});

test('accepting a dispatch notifies every platform_admin and nobody else', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $this->actingAs($this->rep);

    $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->dispatched()->create();
    StockDispatchLine::factory()->forDispatch($dispatch)->create(['product_id' => $this->product->id, 'quantity' => 5]);

    livewire(FieldListStockDispatches::class)
        ->callAction(TestAction::make('accept')->table($dispatch))
        ->assertNotified('Stock accepted');

    Notification::assertSentTo([$this->ops, $this->otherOps], StockDispatchAccepted::class);
    Notification::assertNotSentTo([$this->rep, $this->accountant], StockDispatchAccepted::class);
});

test('submitting a count notifies every platform_admin', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $this->actingAs($this->rep);

    $count = StockCount::factory()->by($this->rep)->forPosition($this->position)->create();
    StockCountLine::factory()->forCount($count)->create(['product_id' => $this->product->id]);

    livewire(FieldListStockCounts::class)
        ->callAction(TestAction::make('submit')->table($count))
        ->assertNotified('Stock count submitted');

    Notification::assertSentTo([$this->ops, $this->otherOps], StockCountSubmitted::class, fn (StockCountSubmitted $n): bool => $n->count->is($count));
    Notification::assertNotSentTo([$this->rep, $this->accountant], StockCountSubmitted::class);
});

test('each notification renders a database payload with a link to the right panel', function (): void {
    $dispatch = StockDispatch::factory()->by($this->ops)->forPosition($this->position)->accepted($this->rep)->create();
    $count = StockCount::factory()->by($this->rep)->forPosition($this->position)->submitted()->create();

    $sent = (new StockDispatchSent($dispatch))->toDatabase($this->rep);
    $accepted = (new StockDispatchAccepted($dispatch))->toDatabase($this->ops);
    $submitted = (new StockCountSubmitted($count))->toDatabase($this->ops);

    expect($sent['title'])->toBe('Stock dispatched to you')
        ->and($sent['body'])->toContain($this->position->code)
        ->and($sent['actions'][0]['url'])->toContain('/field/')
        ->and($accepted['title'])->toBe('Dispatch accepted')
        ->and($accepted['body'])->toContain($this->rep->name)
        ->and($accepted['actions'][0]['url'])->toContain('/office/')
        ->and($submitted['title'])->toBe('Stock count submitted')
        ->and($submitted['actions'][0]['url'])->toContain('/office/')
        ->and($submitted['actions'][0]['url'])->toContain((string) $count->id);
});

test('the Field and Office panels have database notifications enabled', function (): void {
    expect(Filament::getPanel('field')->hasDatabaseNotifications())->toBeTrue()
        ->and(Filament::getPanel('office')->hasDatabaseNotifications())->toBeTrue();
});
