<?php

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Region;
use App\Models\Territory;
use App\Models\User;

/**
 * Transaction Visibility Scope A — Deposit::visibleTo($viewer).
 * Same trait and rules as Call / Distribution; only the user column is `user_id`.
 */
beforeEach(function (): void {
    $this->regionA = Region::factory()->create();
    $this->regionB = Region::factory()->create();
    $this->terrA = Territory::factory()->for($this->regionA)->create();
    $this->terrB = Territory::factory()->for($this->regionB)->create();

    // Helper: create a deposit anchored to a territory via its customer.
    $this->depositIn = function (Territory $territory, ?User $by = null): Deposit {
        $customer = Customer::factory()->create(['territory_id' => $territory->id]);

        return Deposit::factory()
            ->forCustomer($customer)
            ->by($by ?? User::factory()->create())
            ->create();
    };
});

test('a sales_rep sees only their own deposits', function (): void {
    $rep = User::factory()->withRole('sales_rep')->create();

    $own = ($this->depositIn)($this->terrA, $rep);
    ($this->depositIn)($this->terrA); // another rep, same territory — must be hidden

    expect(Deposit::visibleTo($rep)->pluck('id')->all())->toEqual([$own->id]);
});

test('a regional_head sees every deposit in their region and none outside it', function (): void {
    $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

    $inRegion = ($this->depositIn)($this->terrA);
    $outOfRegion = ($this->depositIn)($this->terrB);

    $visible = Deposit::visibleTo($head)->pluck('id')->all();

    expect($visible)->toContain($inRegion->id)
        ->and($visible)->not->toContain($outOfRegion->id);
});

test('a supervisor sees their derived region (from active position) and none outside it', function (): void {
    $supervisor = User::factory()->withRole('supervisor')->create();

    $position = Position::factory()->create(['territory_id' => $this->terrA->id]);
    PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => $supervisor->id,
        'effective_to' => null,
    ]);

    $inRegion = ($this->depositIn)($this->terrA);
    $outOfRegion = ($this->depositIn)($this->terrB);

    $visible = Deposit::visibleTo($supervisor)->pluck('id')->all();

    expect($visible)->toContain($inRegion->id)
        ->and($visible)->not->toContain($outOfRegion->id);
});

test('national roles see every deposit', function (string $role): void {
    ($this->depositIn)($this->terrA);
    ($this->depositIn)($this->terrB);

    $user = User::factory()->withRole($role)->create();

    expect(Deposit::visibleTo($user)->count())->toBe(2);
})->with([
    'superuser' => ['superuser'],
    'platform_admin' => ['platform_admin'],
    'hq_lead' => ['hq_lead'],
    'accountant' => ['accountant'],
]);

test('a regional_head with no region sees nothing (never falls through to all)', function (): void {
    ($this->depositIn)($this->terrA);
    ($this->depositIn)($this->terrB);

    $head = User::factory()->withRole('regional_head')->create(); // region_id null

    expect(Deposit::visibleTo($head)->count())->toBe(0);
});
