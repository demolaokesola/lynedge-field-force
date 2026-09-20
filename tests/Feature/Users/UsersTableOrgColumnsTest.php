<?php

use App\Filament\Office\Resources\Users\Pages\ListUsers;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Region;
use App\Models\Territory;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('platform_admin')->create());
});

test('a sales rep shows the region and territory of their active position', function (): void {
    $region = Region::factory()->create(['name' => 'South West']);
    $territory = Territory::factory()->create(['region_id' => $region->id, 'name' => 'Ibadan North']);
    $position = Position::factory()->create(['territory_id' => $territory->id]);
    $rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create(['position_id' => $position->id, 'user_id' => $rep->id]);

    livewire(ListUsers::class)
        ->assertTableColumnStateSet('region.name', 'South West', $rep)
        ->assertTableColumnStateSet('activePositionAssignment.position.territory.name', 'Ibadan North', $rep);
});

test('a regional head shows their own region and no territory', function (): void {
    $region = Region::factory()->create(['name' => 'North Central']);
    $head = User::factory()->withRole('regional_head')->inRegion($region)->create();

    livewire(ListUsers::class)
        ->assertTableColumnStateSet('region.name', 'North Central', $head)
        ->assertTableColumnStateSet('activePositionAssignment.position.territory.name', null, $head);
});

test('a rep whose assignment has ended shows no region or territory', function (): void {
    $rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->ended()->create(['user_id' => $rep->id]);

    livewire(ListUsers::class)
        ->assertTableColumnStateSet('region.name', null, $rep)
        ->assertTableColumnStateSet('activePositionAssignment.position.territory.name', null, $rep);
});
