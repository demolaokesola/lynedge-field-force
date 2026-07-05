<?php

use App\Models\Customer;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Region;
use App\Models\Territory;
use App\Models\User;

/**
 * Territory visibility scope — Customer::visibleTo($viewer).
 */
beforeEach(function (): void {
    $this->regionA = Region::factory()->create();
    $this->regionB = Region::factory()->create();
    $this->terrA = Territory::factory()->for($this->regionA)->create();
    $this->terrB = Territory::factory()->for($this->regionB)->create();

    $this->assignActivePosition = function (User $user, Territory $territory): Position {
        $position = Position::factory()->create(['territory_id' => $territory->id]);

        PositionAssignment::factory()->create([
            'position_id' => $position->id,
            'user_id' => $user->id,
            'effective_to' => null,
        ]);

        return $position;
    };
});

test('a sales_rep sees customers only in their own active position territory', function (): void {
    $rep = User::factory()->withRole('sales_rep')->create();
    ($this->assignActivePosition)($rep, $this->terrA);

    $inTerritory = Customer::factory()->create(['territory_id' => $this->terrA->id]);
    $outOfTerritory = Customer::factory()->create(['territory_id' => $this->terrB->id]);

    $visible = Customer::visibleTo($rep)->pluck('id')->all();

    expect($visible)->toContain($inTerritory->id)
        ->and($visible)->not->toContain($outOfTerritory->id);
});

test('a sales_rep with no active position sees nothing', function (): void {
    Customer::factory()->create(['territory_id' => $this->terrA->id]);

    $rep = User::factory()->withRole('sales_rep')->create();

    expect(Customer::visibleTo($rep)->count())->toBe(0);
});

test('a supervisor sees customers only in their own active position territory', function (): void {
    $supervisor = User::factory()->withRole('supervisor')->create();
    ($this->assignActivePosition)($supervisor, $this->terrA);

    $inTerritory = Customer::factory()->create(['territory_id' => $this->terrA->id]);
    $outOfTerritory = Customer::factory()->create(['territory_id' => $this->terrB->id]);

    $visible = Customer::visibleTo($supervisor)->pluck('id')->all();

    expect($visible)->toContain($inTerritory->id)
        ->and($visible)->not->toContain($outOfTerritory->id);
});

test('a regional_head sees every customer in their region and none outside it', function (): void {
    $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

    $inRegion = Customer::factory()->create(['territory_id' => $this->terrA->id]);
    $outOfRegion = Customer::factory()->create(['territory_id' => $this->terrB->id]);

    $visible = Customer::visibleTo($head)->pluck('id')->all();

    expect($visible)->toContain($inRegion->id)
        ->and($visible)->not->toContain($outOfRegion->id);
});

test('a regional_head with no region sees nothing (never falls through to all)', function (): void {
    Customer::factory()->create(['territory_id' => $this->terrA->id]);
    Customer::factory()->create(['territory_id' => $this->terrB->id]);

    $head = User::factory()->withRole('regional_head')->create(); // region_id null

    expect(Customer::visibleTo($head)->count())->toBe(0);
});

test('national roles see every customer', function (string $role): void {
    Customer::factory()->create(['territory_id' => $this->terrA->id]);
    Customer::factory()->create(['territory_id' => $this->terrB->id]);

    $user = User::factory()->withRole($role)->create();

    expect(Customer::visibleTo($user)->count())->toBe(2);
})->with([
    'superuser' => ['superuser'],
    'platform_admin' => ['platform_admin'],
    'hq_lead' => ['hq_lead'],
    'accountant' => ['accountant'],
]);
