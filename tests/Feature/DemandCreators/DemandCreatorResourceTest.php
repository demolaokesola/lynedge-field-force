<?php

use App\Filament\Field\Resources\DemandCreators\DemandCreatorResource;
use App\Filament\Field\Resources\DemandCreators\Pages\CreateDemandCreator;
use App\Filament\Field\Resources\DemandCreators\Pages\ListDemandCreators;
use App\Models\DemandCreator;
use App\Models\DemandCreatorType;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Region;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));

    $this->region = Region::factory()->create();
    $this->territory = Territory::factory()->for($this->region)->create();
    $this->team = Team::factory()->strict()->create();
    $this->position = Position::factory()->create([
        'territory_id' => $this->territory->id,
        'team_id' => $this->team->id,
    ]);

    $this->rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $this->position->id,
        'user_id' => $this->rep->id,
        'effective_to' => null,
    ]);

    $this->type = DemandCreatorType::factory()->create();

    $this->actingAs($this->rep);
});

test('a rep creates a demand creator: created_by is forced and territory_id is derived from the position', function (): void {
    livewire(CreateDemandCreator::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'demand_creator_type_id' => $this->type->id,
            'name' => 'Dr. Adebayo',
            'affiliation' => 'City Clinic',
            'phone' => '08012345678',
            'address' => '1 Market Road',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $demandCreator = DemandCreator::sole();

    expect($demandCreator->created_by)->toBe($this->rep->id)
        ->and($demandCreator->territory_id)->toBe($this->territory->id)
        ->and($demandCreator->name)->toBe('Dr. Adebayo');
});

test('a rep with no active position cannot create a demand creator', function (): void {
    $repWithoutPosition = User::factory()->withRole('sales_rep')->create();
    $this->actingAs($repWithoutPosition);

    livewire(CreateDemandCreator::class)
        ->fillForm([
            'demand_creator_type_id' => $this->type->id,
            'name' => 'Dr. Adebayo',
        ])
        ->call('create')
        ->assertHasFormErrors(['position_id']);

    expect(DemandCreator::count())->toBe(0);
});

test('the list shows demand creators in the rep territory only', function (): void {
    $own = DemandCreator::factory()->create(['territory_id' => $this->territory->id]);

    $elsewhere = Territory::factory()->create();
    $foreign = DemandCreator::factory()->create(['territory_id' => $elsewhere->id]);

    livewire(ListDemandCreators::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

test('a rep can edit their own demand creator but not another rep\'s demand creator', function (): void {
    $own = DemandCreator::factory()->by($this->rep)->create(['territory_id' => $this->territory->id]);

    $otherRep = User::factory()->withRole('sales_rep')->create();
    $foreign = DemandCreator::factory()->by($otherRep)->create(['territory_id' => $this->territory->id]);

    expect($this->rep->can('update', $own))->toBeTrue()
        ->and($this->rep->can('update', $foreign))->toBeFalse();
});

test('the "new demand creator" button on the list page links to the create page instead of opening the default modal', function (): void {
    // Regression guard: the default header CreateAction is a bare modal that
    // fills DemandCreator directly and drops position_id (not a column), leaving
    // territory_id null. It must link to CreateDemandCreator's page instead, which
    // derives territory_id from the chosen position.
    $createUrl = DemandCreatorResource::getUrl('create');

    $this->get(DemandCreatorResource::getUrl('index'))
        ->assertOk()
        ->assertSee($createUrl, false);

    $this->get($createUrl)->assertOk();
});

test('sales_rep and platform_admin may create demand creators but other roles may not', function (): void {
    expect($this->rep->can('create', DemandCreator::class))->toBeTrue();

    $admin = User::factory()->withRole('platform_admin')->create();
    expect($admin->can('create', DemandCreator::class))->toBeTrue();

    foreach (['hq_lead', 'regional_head', 'accountant'] as $role) {
        $user = User::factory()->withRole($role)->create();
        expect($user->can('create', DemandCreator::class))->toBeFalse();
    }
});
