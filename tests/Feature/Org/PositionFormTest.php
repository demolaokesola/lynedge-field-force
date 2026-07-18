<?php

use App\Enums\PositionStatus;
use App\Filament\Office\Resources\Positions\Pages\CreatePosition;
use App\Models\Position;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->withRole('platform_admin')->create());
});

test('the form rejects a liberal team in a strict territory', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->liberal()->create();

    livewire(CreatePosition::class)
        ->fillForm([
            'region_id' => $territory->region_id,
            'territory_id' => $territory->id,
            'team_id' => $team->id,
            'status' => PositionStatus::Active->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['team_id']);
});

test('the form rejects a strict team in a liberal territory', function (): void {
    $territory = Territory::factory()->liberal()->create();
    $team = Team::factory()->strict()->create();

    livewire(CreatePosition::class)
        ->fillForm([
            'region_id' => $territory->region_id,
            'territory_id' => $territory->id,
            'team_id' => $team->id,
            'status' => PositionStatus::Active->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['team_id']);
});

test('the form rejects a second active position for the same team in a strict territory', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->strict()->create();

    Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
        'status' => PositionStatus::Active,
    ]);

    livewire(CreatePosition::class)
        ->fillForm([
            'region_id' => $territory->region_id,
            'territory_id' => $territory->id,
            'team_id' => $team->id,
            'status' => PositionStatus::Active->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['team_id']);
});

test('the form accepts a matching team and territory pair', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->strict()->create();

    livewire(CreatePosition::class)
        ->fillForm([
            'region_id' => $territory->region_id,
            'territory_id' => $territory->id,
            'team_id' => $team->id,
            'status' => PositionStatus::Active->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('positions', [
        'territory_id' => $territory->id,
        'team_id' => $team->id,
        'code' => "{$territory->code}-{$team->code}",
        'enforce_team_uniqueness' => true,
    ]);
});

test('the form accepts an optional supervisor and persists it', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->strict()->create();
    $supervisor = User::factory()->withRole('sales_rep')->create();

    livewire(CreatePosition::class)
        ->fillForm([
            'region_id' => $territory->region_id,
            'territory_id' => $territory->id,
            'team_id' => $team->id,
            'supervisor_id' => $supervisor->id,
            'status' => PositionStatus::Active->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('positions', [
        'territory_id' => $territory->id,
        'team_id' => $team->id,
        'supervisor_id' => $supervisor->id,
    ]);
});

test('the form leaves supervisor_id null when none is selected', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->strict()->create();

    livewire(CreatePosition::class)
        ->fillForm([
            'region_id' => $territory->region_id,
            'territory_id' => $territory->id,
            'team_id' => $team->id,
            'status' => PositionStatus::Active->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('positions', [
        'territory_id' => $territory->id,
        'team_id' => $team->id,
        'supervisor_id' => null,
    ]);
});
