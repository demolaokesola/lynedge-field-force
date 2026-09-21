<?php

use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Region;
use App\Models\StockCount;
use App\Models\Territory;
use App\Models\User;

/**
 * StockCount uses ScopesToPosition, the same position-anchored scope as the other
 * stock documents: reps see counts for positions they hold or supervise, regional
 * heads their region, national roles everything.
 */
beforeEach(function (): void {
    $this->regionA = Region::factory()->create();
    $this->regionB = Region::factory()->create();
    $this->terrA = Territory::factory()->for($this->regionA)->create();
    $this->terrB = Territory::factory()->for($this->regionB)->create();

    $this->countIn = function (Territory $territory): StockCount {
        $position = Position::factory()->create(['territory_id' => $territory->id]);

        return StockCount::factory()->forPosition($position)->create();
    };
});

test('a sales_rep sees only counts for positions they hold or supervise', function (): void {
    $rep = User::factory()->withRole('sales_rep')->create();
    $held = Position::factory()->create(['territory_id' => $this->terrA->id]);
    PositionAssignment::factory()->create([
        'position_id' => $held->id,
        'user_id' => $rep->id,
        'effective_to' => null,
    ]);
    $supervised = Position::factory()->create(['territory_id' => $this->terrA->id, 'supervisor_id' => $rep->id]);

    $own = StockCount::factory()->forPosition($held)->create();
    $subordinate = StockCount::factory()->forPosition($supervised)->create();
    ($this->countIn)($this->terrA);

    expect(StockCount::visibleTo($rep)->pluck('id')->sort()->values()->all())
        ->toEqual(collect([$own->id, $subordinate->id])->sort()->values()->all());
});

test('a regional_head sees every count in their region and none outside it', function (): void {
    $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

    $inRegion = ($this->countIn)($this->terrA);
    ($this->countIn)($this->terrB);

    expect(StockCount::visibleTo($head)->pluck('id')->all())->toEqual([$inRegion->id]);
});

test('national roles see every count', function (string $role): void {
    $user = User::factory()->withRole($role)->create();

    ($this->countIn)($this->terrA);
    ($this->countIn)($this->terrB);

    expect(StockCount::visibleTo($user)->count())->toBe(2);
})->with(['superuser', 'platform_admin', 'hq_lead', 'accountant']);
