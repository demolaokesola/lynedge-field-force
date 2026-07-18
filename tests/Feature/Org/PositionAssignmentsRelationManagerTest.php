<?php

use App\Enums\AssignmentStatus;
use App\Filament\Office\Resources\Positions\Pages\EditPosition;
use App\Filament\Office\Resources\Positions\RelationManagers\AssignmentsRelationManager;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->withRole('platform_admin')->create());
});

test('the rep select only offers sales_rep users', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->strict()->create();
    $position = Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
    ]);

    $rep = User::factory()->withRole('sales_rep')->create();
    $admin = User::factory()->withRole('platform_admin')->create();

    livewire(AssignmentsRelationManager::class, [
        'ownerRecord' => $position,
        'pageClass' => EditPosition::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'user_id' => $rep->id,
            'effective_from' => today()->toDateString(),
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('position_assignments', [
        'position_id' => $position->id,
        'user_id' => $rep->id,
        'status' => AssignmentStatus::Active->value,
    ]);

    livewire(AssignmentsRelationManager::class, [
        'ownerRecord' => $position,
        'pageClass' => EditPosition::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'user_id' => $admin->id,
            'effective_from' => today()->toDateString(),
        ])
        ->assertHasTableActionErrors(['user_id']);
});

test('the rep select excludes a sales_rep who already occupies another position', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->strict()->create();
    $position = Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
    ]);

    $busyRep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'user_id' => $busyRep->id,
        'effective_to' => null,
    ]);

    livewire(AssignmentsRelationManager::class, [
        'ownerRecord' => $position,
        'pageClass' => EditPosition::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'user_id' => $busyRep->id,
            'effective_from' => today()->toDateString(),
        ])
        ->assertHasTableActionErrors(['user_id']);
});

test('editing an open assignment still offers its own current rep', function (): void {
    $territory = Territory::factory()->strict()->create();
    $team = Team::factory()->strict()->create();
    $position = Position::factory()->create([
        'territory_id' => $territory->id,
        'team_id' => $team->id,
    ]);

    $rep = User::factory()->withRole('sales_rep')->create();
    $assignment = PositionAssignment::factory()->create([
        'position_id' => $position->id,
        'user_id' => $rep->id,
        'effective_to' => null,
    ]);

    livewire(AssignmentsRelationManager::class, [
        'ownerRecord' => $position,
        'pageClass' => EditPosition::class,
    ])
        ->callAction(TestAction::make(EditAction::class)->table($assignment), [
            'user_id' => $rep->id,
            'effective_from' => $assignment->effective_from->toDateString(),
            'notes' => 'still the same rep',
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('position_assignments', [
        'id' => $assignment->id,
        'user_id' => $rep->id,
        'notes' => 'still the same rep',
    ]);
});
