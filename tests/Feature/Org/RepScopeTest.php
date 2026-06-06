<?php

use App\Models\Position;
use App\Models\PositionAssignment;
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
