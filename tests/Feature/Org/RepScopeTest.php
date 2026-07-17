<?php

use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Product;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use App\Services\RepScope;

beforeEach(function (): void {
    $this->scope = new RepScope;
});

test('it returns the rep\'s active positions in the territory', function (): void {
    $territory = Territory::factory()->strict()->create();
    $rep = User::factory()->create();

    $position = Position::factory()->create(['territory_id' => $territory->id]);
    PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => $rep->id,
        'effective_from' => today()->subMonth(),
        'effective_to' => null,
    ]);

    $result = $this->scope->invoiceablePositions($rep, $territory->id);

    expect($result)->toHaveCount(1)
        ->and($result->first()->is($position))->toBeTrue();
});

test('it excludes positions in other territories', function (): void {
    $rep = User::factory()->create();
    $here = Territory::factory()->strict()->create();
    $elsewhere = Territory::factory()->strict()->create();

    $other = Position::factory()->create(['territory_id' => $elsewhere->id]);
    PositionAssignment::factory()->create([
        'position_id' => $other->id,
        'user_id' => $rep->id,
        'effective_to' => null,
    ]);

    expect($this->scope->invoiceablePositions($rep, $here->id))->toBeEmpty();
});

test('it excludes frozen positions', function (): void {
    $territory = Territory::factory()->strict()->create();
    $rep = User::factory()->create();

    $frozen = Position::factory()->frozen()->create(['territory_id' => $territory->id]);
    PositionAssignment::factory()->create([
        'position_id' => $frozen->id,
        'user_id' => $rep->id,
        'effective_to' => null,
    ]);

    expect($this->scope->invoiceablePositions($rep, $territory->id))->toBeEmpty();
});

test('it excludes positions whose assignment has ended', function (): void {
    $territory = Territory::factory()->strict()->create();
    $rep = User::factory()->create();

    $position = Position::factory()->create(['territory_id' => $territory->id]);
    PositionAssignment::factory()->ended(today()->subDay())->create([
        'position_id' => $position->id,
        'user_id' => $rep->id,
        'effective_from' => today()->subMonths(2),
    ]);

    expect($this->scope->invoiceablePositions($rep, $territory->id))->toBeEmpty();
});

test('it excludes positions assigned to a different rep', function (): void {
    $territory = Territory::factory()->strict()->create();
    $rep = User::factory()->create();
    $otherRep = User::factory()->create();

    $position = Position::factory()->create(['territory_id' => $territory->id]);
    PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => $otherRep->id,
        'effective_to' => null,
    ]);

    expect($this->scope->invoiceablePositions($rep, $territory->id))->toBeEmpty();
});

test('productsForUser returns only active products belonging to the rep\'s team', function (): void {
    $team = Team::factory()->strict()->create();
    $territory = Territory::factory()->strict()->create();
    $position = Position::factory()->create(['territory_id' => $territory->id, 'team_id' => $team->id]);

    $rep = User::factory()->create();
    PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => $rep->id,
        'effective_to' => null,
    ]);

    $ownActive = Product::factory()->create(['active' => true]);
    $ownActive->teams()->attach($team->id);

    $ownInactive = Product::factory()->create(['active' => false]);
    $ownInactive->teams()->attach($team->id);

    $otherTeam = Team::factory()->strict()->create();
    $foreign = Product::factory()->create(['active' => true]);
    $foreign->teams()->attach($otherTeam->id);

    $result = $this->scope->productsForUser($rep)->pluck('id')->all();

    expect($result)->toContain($ownActive->id)
        ->and($result)->not->toContain($ownInactive->id)
        ->and($result)->not->toContain($foreign->id);
});

test('productsForUser unions products across the rep\'s active positions in different teams', function (): void {
    $teamA = Team::factory()->strict()->create();
    $teamB = Team::factory()->liberal()->create();
    $territoryA = Territory::factory()->strict()->create();
    $territoryB = Territory::factory()->liberal()->create();

    $positionA = Position::factory()->create(['territory_id' => $territoryA->id, 'team_id' => $teamA->id]);
    $positionB = Position::factory()->create(['territory_id' => $territoryB->id, 'team_id' => $teamB->id]);

    $rep = User::factory()->create();
    PositionAssignment::factory()->create(['position_id' => $positionA->id, 'user_id' => $rep->id, 'effective_to' => null]);
    PositionAssignment::factory()->create(['position_id' => $positionB->id, 'user_id' => $rep->id, 'effective_to' => null]);

    $productA = Product::factory()->create(['active' => true]);
    $productA->teams()->attach($teamA->id);

    $productB = Product::factory()->create(['active' => true]);
    $productB->teams()->attach($teamB->id);

    $result = $this->scope->productsForUser($rep)->pluck('id')->all();

    expect($result)->toContain($productA->id)
        ->and($result)->toContain($productB->id);
});
