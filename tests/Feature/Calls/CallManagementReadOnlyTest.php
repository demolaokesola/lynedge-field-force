<?php

use App\Filament\Shared\Resources\Calls\Pages\ListCalls;
use App\Models\Call;
use App\Models\Position;
use App\Models\Region;
use App\Models\Territory;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('management'));

    $this->regionA = Region::factory()->create();
    $this->regionB = Region::factory()->create();
    $this->terrA = Territory::factory()->for($this->regionA)->create();
    $this->terrB = Territory::factory()->for($this->regionB)->create();

    $this->callIn = function (Territory $territory): Call {
        $position = Position::factory()->create(['territory_id' => $territory->id]);

        return Call::factory()->forPosition($position)->create();
    };

    $this->head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();
    $this->actingAs($this->head);
});

test('the management list is region-scoped for a regional_head', function (): void {
    $inRegion = ($this->callIn)($this->terrA);
    $outOfRegion = ($this->callIn)($this->terrB);

    livewire(ListCalls::class)
        ->assertCanSeeTableRecords([$inRegion])
        ->assertCanNotSeeTableRecords([$outOfRegion]);
});

test('a regional_head is forbidden from the create page', function (): void {
    $this->get('/management/calls/create')->assertForbidden();
});

test('a regional_head cannot update a call in their region', function (): void {
    $call = ($this->callIn)($this->terrA);

    expect($this->head->can('update', $call))->toBeFalse()
        ->and($this->head->can('delete', $call))->toBeFalse();
});
