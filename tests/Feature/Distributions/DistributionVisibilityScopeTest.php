<?php

use App\Models\Distribution;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Region;
use App\Models\Territory;
use App\Models\User;

/**
 * Transaction Visibility Scope A — Distribution::visibleTo($viewer).
 * Mirrors the Call visibility tests exactly (same trait, same rules).
 */
beforeEach(function (): void {
    $this->regionA = Region::factory()->create();
    $this->regionB = Region::factory()->create();
    $this->terrA = Territory::factory()->for($this->regionA)->create();
    $this->terrB = Territory::factory()->for($this->regionB)->create();

    $this->distributionIn = function (Territory $territory, ?User $by = null): Distribution {
        $position = Position::factory()->create(['territory_id' => $territory->id]);

        return Distribution::factory()
            ->by($by ?? User::factory()->create())
            ->forPosition($position)
            ->create();
    };
});

test('a sales_rep sees only their own distributions', function (): void {
    $rep = User::factory()->withRole('sales_rep')->create();

    $own = ($this->distributionIn)($this->terrA, $rep);
    ($this->distributionIn)($this->terrA); // another rep, same territory

    expect(Distribution::visibleTo($rep)->pluck('id')->all())->toEqual([$own->id]);
});

test('a regional_head sees every distribution in their region and none outside it', function (): void {
    $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

    $inRegion = ($this->distributionIn)($this->terrA);
    $outOfRegion = ($this->distributionIn)($this->terrB);

    $visible = Distribution::visibleTo($head)->pluck('id')->all();

    expect($visible)->toContain($inRegion->id)
        ->and($visible)->not->toContain($outOfRegion->id);
});

test('a supervisor sees their region (derived from their open position), read of others included', function (): void {
    $supervisor = User::factory()->withRole('supervisor')->create();

    $position = Position::factory()->create(['territory_id' => $this->terrA->id]);
    PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => $supervisor->id,
        'effective_to' => null,
    ]);

    $inRegion = ($this->distributionIn)($this->terrA);
    $outOfRegion = ($this->distributionIn)($this->terrB);

    $visible = Distribution::visibleTo($supervisor)->pluck('id')->all();

    expect($visible)->toContain($inRegion->id)
        ->and($visible)->not->toContain($outOfRegion->id);
});

test('national roles see every distribution', function (string $role): void {
    ($this->distributionIn)($this->terrA);
    ($this->distributionIn)($this->terrB);

    $user = User::factory()->withRole($role)->create();

    expect(Distribution::visibleTo($user)->count())->toBe(2);
})->with([
    'superuser' => ['superuser'],
    'platform_admin' => ['platform_admin'],
    'hq_lead' => ['hq_lead'],
    'accountant' => ['accountant'],
]);

test('a regional_head with no region sees nothing (never falls through to all)', function (): void {
    ($this->distributionIn)($this->terrA);
    ($this->distributionIn)($this->terrB);

    $head = User::factory()->withRole('regional_head')->create(); // region_id null

    expect(Distribution::visibleTo($head)->count())->toBe(0);
});
