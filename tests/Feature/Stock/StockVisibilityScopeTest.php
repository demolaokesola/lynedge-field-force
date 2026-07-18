<?php

use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionProductStock;
use App\Models\Region;
use App\Models\StockDispatch;
use App\Models\Territory;
use App\Models\User;

/**
 * Position-anchored visibility (ScopesToPosition — a variant of Scope A). Stock belongs
 * to the POSITION, not to whichever user authored the document, so the sales_rep branch
 * keys off position ownership (held or supervised) instead of user_id. Verified via
 * StockDispatch (the trait) and PositionProductStock (its own bespoke, live-joined
 * equivalent, since it has no denormalised territory_id).
 */
beforeEach(function (): void {
    $this->regionA = Region::factory()->create();
    $this->regionB = Region::factory()->create();
    $this->terrA = Territory::factory()->for($this->regionA)->create();
    $this->terrB = Territory::factory()->for($this->regionB)->create();

    $this->dispatchIn = function (Territory $territory): StockDispatch {
        $position = Position::factory()->create(['territory_id' => $territory->id]);

        return StockDispatch::factory()->forPosition($position)->create();
    };
});

test('a sales_rep sees only stock dispatches for positions they hold, not other positions in the same territory', function (): void {
    $rep = User::factory()->withRole('sales_rep')->create();
    $position = Position::factory()->create(['territory_id' => $this->terrA->id]);
    PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => $rep->id,
        'effective_to' => null,
    ]);

    $own = StockDispatch::factory()->forPosition($position)->create();
    ($this->dispatchIn)($this->terrA); // a different position, same territory

    expect(StockDispatch::visibleTo($rep)->pluck('id')->all())->toEqual([$own->id]);
});

test('a sales_rep also sees stock dispatches for positions they supervise, not unrelated positions', function (): void {
    $supervisor = User::factory()->withRole('sales_rep')->create();
    $supervisedPosition = Position::factory()->create([
        'territory_id' => $this->terrA->id,
        'supervisor_id' => $supervisor->id,
    ]);
    $supervised = StockDispatch::factory()->forPosition($supervisedPosition)->create();

    $unrelated = ($this->dispatchIn)($this->terrA);

    $visible = StockDispatch::visibleTo($supervisor)->pluck('id')->all();

    expect($visible)->toContain($supervised->id)
        ->and($visible)->not->toContain($unrelated->id);
});

test('a regional_head sees every stock dispatch in their region and none outside it', function (): void {
    $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

    $inRegion = ($this->dispatchIn)($this->terrA);
    $outOfRegion = ($this->dispatchIn)($this->terrB);

    $visible = StockDispatch::visibleTo($head)->pluck('id')->all();

    expect($visible)->toContain($inRegion->id)
        ->and($visible)->not->toContain($outOfRegion->id);
});

test('national roles see every stock dispatch', function (string $role): void {
    ($this->dispatchIn)($this->terrA);
    ($this->dispatchIn)($this->terrB);

    $user = User::factory()->withRole($role)->create();

    expect(StockDispatch::visibleTo($user)->count())->toBe(2);
})->with([
    'superuser' => ['superuser'],
    'platform_admin' => ['platform_admin'],
    'hq_lead' => ['hq_lead'],
    'accountant' => ['accountant'],
]);

test('a regional_head with no region sees no stock dispatches (never falls through to all)', function (): void {
    ($this->dispatchIn)($this->terrA);
    ($this->dispatchIn)($this->terrB);

    $head = User::factory()->withRole('regional_head')->create(); // region_id null

    expect(StockDispatch::visibleTo($head)->count())->toBe(0);
});

test('PositionProductStock scopes balances the same way, via a live join through position', function (): void {
    $rep = User::factory()->withRole('sales_rep')->create();
    $position = Position::factory()->create(['territory_id' => $this->terrA->id]);
    PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => $rep->id,
        'effective_to' => null,
    ]);

    $own = PositionProductStock::factory()->forPosition($position)->create();

    $otherPosition = Position::factory()->create(['territory_id' => $this->terrA->id]);
    PositionProductStock::factory()->forPosition($otherPosition)->create();

    expect(PositionProductStock::visibleTo($rep)->pluck('id')->all())->toEqual([$own->id]);

    $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();
    expect(PositionProductStock::visibleTo($head)->count())->toBe(2);

    $outOfRegionHead = User::factory()->withRole('regional_head')->inRegion($this->regionB)->create();
    expect(PositionProductStock::visibleTo($outOfRegionHead)->count())->toBe(0);
});
