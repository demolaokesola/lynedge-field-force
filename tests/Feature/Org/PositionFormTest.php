<?php

use App\Enums\PositionStatus;
use App\Filament\Resources\Positions\Pages\CreatePosition;
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
            'territory_id' => $territory->id,
            'team_id' => $team->id,
            'code' => 'POS-A1',
            'label' => 'Rep slot',
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
            'territory_id' => $territory->id,
            'team_id' => $team->id,
            'code' => 'POS-B1',
            'label' => 'Rep slot',
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
            'territory_id' => $territory->id,
            'team_id' => $team->id,
            'code' => 'POS-C1',
            'label' => 'Duplicate slot',
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
            'territory_id' => $territory->id,
            'team_id' => $team->id,
            'code' => 'POS-D1',
            'label' => 'Valid slot',
            'status' => PositionStatus::Active->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('positions', [
        'territory_id' => $territory->id,
        'team_id' => $team->id,
        'code' => 'POS-D1',
        'enforce_team_uniqueness' => true,
    ]);
});
