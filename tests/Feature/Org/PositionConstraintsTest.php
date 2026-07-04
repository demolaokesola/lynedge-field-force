<?php

use App\Enums\AssignmentStatus;
use App\Enums\PositionStatus;
use App\Enums\TeamPolicy;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Database\QueryException;

test('a strict territory rejects a second active position for the same team (DB)', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->strict()->create();

    Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
        'status' => PositionStatus::Active,
    ]);

    expect(fn () => Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
        'status' => PositionStatus::Active,
    ]))->toThrow(QueryException::class);
});

test('a liberal territory allows two active positions for the same team', function (): void {
    $territory = Territory::factory()->liberal()->create();
    $team = Team::factory()->liberal()->create();

    $first = Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
        'status' => PositionStatus::Active,
    ]);
    $second = Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
        'status' => PositionStatus::Active,
    ]);

    expect($first->enforce_team_uniqueness)->toBeFalse()
        ->and($second->enforce_team_uniqueness)->toBeFalse()
        ->and(Position::count())->toBe(2)
        ->and($first->code)->toBe("{$territory->code}-{$team->code}")
        ->and($second->code)->toBe("{$territory->code}-{$team->code}-2")
        ->and($first->code)->not->toBe($second->code);
});

test('positions sharing a territory and team get distinct suffixed codes', function (): void {
    $territory = Territory::factory()->liberal()->create();
    $team = Team::factory()->liberal()->create();

    $positions = Position::factory()->count(3)->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
        'status' => PositionStatus::Active,
    ]);

    expect($positions->pluck('code')->all())->toBe([
        "{$territory->code}-{$team->code}",
        "{$territory->code}-{$team->code}-2",
        "{$territory->code}-{$team->code}-3",
    ])->and($positions->pluck('code')->unique())->toHaveCount(3);
});

test('renaming a territory code re-syncs its positions codes', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->strict()->create();
    $position = Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
    ]);

    expect($position->code)->toBe("{$territory->code}-{$team->code}");

    $territory->update(['code' => 'NEWCODE']);

    expect($position->fresh()->code)->toBe("NEWCODE-{$team->code}");
});

test('renaming a team code re-syncs its positions codes', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->strict()->create();
    $position = Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
    ]);

    expect($position->code)->toBe("{$territory->code}-{$team->code}");

    $team->update(['code' => 'NEWTEAMCODE']);

    expect($position->fresh()->code)->toBe("{$territory->code}-NEWTEAMCODE");
});

test('the observer sets enforce_team_uniqueness from the territory policy', function (): void {
    $strict = Position::factory()->strict()->create();
    $liberal = Position::factory()->liberal()->create();

    expect($strict->enforce_team_uniqueness)->toBeTrue()
        ->and($liberal->enforce_team_uniqueness)->toBeFalse();
});

test('flipping a territory policy re-syncs its positions enforce flag', function (): void {
    $territory = Territory::factory()->strict()->create();
    $position = Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => Team::factory()->strict()->create()->id,
    ]);

    expect($position->enforce_team_uniqueness)->toBeTrue();

    $territory->update(['team_policy' => TeamPolicy::Liberal]);

    expect($position->fresh()->enforce_team_uniqueness)->toBeFalse();
});

test('only one open assignment is allowed per position (DB)', function (): void {
    $position = Position::factory()->create();
    $rep = User::factory()->create();

    PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => $rep->id,
        'effective_to' => null,
    ]);

    expect(fn () => PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => User::factory()->create()->id,
        'effective_to' => null,
    ]))->toThrow(QueryException::class);
});

test('a position may be re-occupied once the open assignment is ended', function (): void {
    $position = Position::factory()->create();

    $first = PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'effective_to' => null,
    ]);

    $first->update(['effective_to' => today(), 'status' => AssignmentStatus::Ended]);

    $second = PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'effective_to' => null,
    ]);

    expect($position->openAssignment->is($second))->toBeTrue();
});
