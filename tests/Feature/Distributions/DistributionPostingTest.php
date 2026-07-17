<?php

use App\Enums\DistributionStatus;
use App\Filament\Shared\Resources\Distributions\DistributionResource;
use App\Filament\Shared\Resources\Distributions\Pages\EditDistribution;
use App\Filament\Shared\Resources\Distributions\Pages\ListDistributions;
use App\Models\Customer;
use App\Models\Distribution;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * Posting is the submit stage: it flips status Draft -> Posted and, from that point on,
 * freezes the record — DistributionPolicy denies update/delete/post once status !== Draft.
 */
beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));

    $this->team = Team::factory()->strict()->create();
    $this->territory = Territory::factory()->strict()->create();
    $this->position = Position::factory()->create([
        'territory_id' => $this->territory->id,
        'team_id' => $this->team->id,
    ]);
    $this->customer = Customer::factory()->create(['territory_id' => $this->territory->id]);

    $this->rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $this->position->id,
        'user_id' => $this->rep->id,
        'effective_to' => null,
    ]);

    $this->actingAs($this->rep);
});

test('a sales_rep can post their own draft distribution from the list', function (): void {
    $distribution = Distribution::factory()->by($this->rep)->forPosition($this->position)->create();

    expect($distribution->status)->toBe(DistributionStatus::Draft);

    livewire(ListDistributions::class)
        ->callAction(TestAction::make('post')->table($distribution))
        ->assertNotified();

    expect($distribution->fresh()->status)->toBe(DistributionStatus::Posted);
});

test('a sales_rep can post their own draft distribution from the edit page', function (): void {
    $distribution = Distribution::factory()->by($this->rep)->forPosition($this->position)->create();

    livewire(EditDistribution::class, ['record' => $distribution->id])
        ->callAction('post')
        ->assertNotified();

    expect($distribution->fresh()->status)->toBe(DistributionStatus::Posted);
});

test('a posted distribution can no longer be edited, deleted, or posted again', function (): void {
    $distribution = Distribution::factory()->by($this->rep)->forPosition($this->position)->posted()->create();

    expect($this->rep->can('update', $distribution))->toBeFalse()
        ->and($this->rep->can('delete', $distribution))->toBeFalse()
        ->and($this->rep->can('post', $distribution))->toBeFalse();

    $this->get(DistributionResource::getUrl('edit', ['record' => $distribution]))
        ->assertForbidden();

    livewire(ListDistributions::class)
        ->assertTableActionHidden('post', $distribution)
        ->assertTableActionHidden('edit', $distribution);
});

test('a void distribution can no longer be edited or posted', function (): void {
    $distribution = Distribution::factory()->by($this->rep)->forPosition($this->position)->void()->create();

    expect($this->rep->can('update', $distribution))->toBeFalse()
        ->and($this->rep->can('post', $distribution))->toBeFalse();
});

test('a sales_rep cannot post another rep\'s distribution', function (): void {
    $otherRep = User::factory()->withRole('sales_rep')->create();
    $otherPosition = Position::factory()->create(['territory_id' => $this->territory->id]);
    PositionAssignment::factory()->create([
        'position_id' => $otherPosition->id,
        'user_id' => $otherRep->id,
        'effective_to' => null,
    ]);
    $foreign = Distribution::factory()->by($otherRep)->forPosition($otherPosition)->create();

    expect($this->rep->can('post', $foreign))->toBeFalse();
});
