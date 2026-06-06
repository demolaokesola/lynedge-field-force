<?php

use App\Models\Call;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Region;
use App\Models\Territory;
use App\Models\User;

/**
 * Transaction Visibility Scope A — Call::visibleTo($viewer).
 */
beforeEach(function (): void {
    $this->regionA = Region::factory()->create();
    $this->regionB = Region::factory()->create();
    $this->terrA = Territory::factory()->for($this->regionA)->create();
    $this->terrB = Territory::factory()->for($this->regionB)->create();

    $this->callIn = function (Territory $territory, ?User $by = null): Call {
        $position = Position::factory()->create(['territory_id' => $territory->id]);

        return Call::factory()
            ->by($by ?? User::factory()->create())
            ->forPosition($position)
            ->create();
    };
});

test('a sales_rep sees only their own calls', function (): void {
    $rep = User::factory()->withRole('sales_rep')->create();

    $own = ($this->callIn)($this->terrA, $rep);
    ($this->callIn)($this->terrA); // another rep, same territory

    expect(Call::visibleTo($rep)->pluck('id')->all())->toEqual([$own->id]);
});

test('a regional_head sees every call in their region and none outside it', function (): void {
    $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

    $inRegion = ($this->callIn)($this->terrA);
    $outOfRegion = ($this->callIn)($this->terrB);

    $visible = Call::visibleTo($head)->pluck('id')->all();

    expect($visible)->toContain($inRegion->id)
        ->and($visible)->not->toContain($outOfRegion->id);
});

test('a supervisor sees their region (derived from their open position), read of others included', function (): void {
    $supervisor = User::factory()->withRole('supervisor')->create();

    // Anchor the supervisor to region A via an open position there.
    $position = Position::factory()->create(['territory_id' => $this->terrA->id]);
    PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => $supervisor->id,
        'effective_to' => null,
    ]);

    $inRegion = ($this->callIn)($this->terrA); // logged by a different rep
    $outOfRegion = ($this->callIn)($this->terrB);

    $visible = Call::visibleTo($supervisor)->pluck('id')->all();

    expect($visible)->toContain($inRegion->id)
        ->and($visible)->not->toContain($outOfRegion->id);
});

test('national roles see every call', function (string $role): void {
    ($this->callIn)($this->terrA);
    ($this->callIn)($this->terrB);

    $user = User::factory()->withRole($role)->create();

    expect(Call::visibleTo($user)->count())->toBe(2);
})->with([
    'superuser' => ['superuser'],
    'platform_admin' => ['platform_admin'],
    'hq_lead' => ['hq_lead'],
    'accountant' => ['accountant'],
]);

test('a regional_head with no region sees nothing (never falls through to all)', function (): void {
    ($this->callIn)($this->terrA);
    ($this->callIn)($this->terrB);

    $head = User::factory()->withRole('regional_head')->create(); // region_id null

    expect(Call::visibleTo($head)->count())->toBe(0);
});
