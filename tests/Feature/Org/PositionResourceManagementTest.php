<?php

use App\Filament\Management\Resources\Positions\Pages\ListPositions;
use App\Filament\Management\Resources\Positions\Pages\PositionCalls;
use App\Filament\Management\Resources\Positions\Pages\PositionDistributions;
use App\Filament\Management\Resources\Positions\PositionResource;
use App\Models\Call;
use App\Models\Distribution;
use App\Models\Position;
use App\Models\Region;
use App\Models\Territory;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

test('the management Position resource is registered in management only', function (): void {
    $management = Filament::getPanel('management')->getResources();
    $office = Filament::getPanel('office')->getResources();
    $field = Filament::getPanel('field')->getResources();

    expect($management)->toContain(PositionResource::class)
        ->and($office)->not->toContain(PositionResource::class)
        ->and($field)->not->toContain(PositionResource::class);
});

describe('org scope', function (): void {
    beforeEach(function (): void {
        Filament::setCurrentPanel(Filament::getPanel('management'));

        $this->regionA = Region::factory()->create();
        $this->regionB = Region::factory()->create();
        $this->terrA = Territory::factory()->for($this->regionA)->create();
        $this->terrB = Territory::factory()->for($this->regionB)->create();
    });

    it('regional_head sees only positions in their region', function (): void {
        $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

        $posA = Position::factory()->create(['territory_id' => $this->terrA->id]);
        $posB = Position::factory()->create(['territory_id' => $this->terrB->id]);

        $this->actingAs($head);

        livewire(ListPositions::class)
            ->assertCanSeeTableRecords([$posA])
            ->assertCanNotSeeTableRecords([$posB]);
    });

    it('hq_lead sees positions across all regions', function (): void {
        $lead = User::factory()->withRole('hq_lead')->create();

        $posA = Position::factory()->create(['territory_id' => $this->terrA->id]);
        $posB = Position::factory()->create(['territory_id' => $this->terrB->id]);

        $this->actingAs($lead);

        livewire(ListPositions::class)
            ->assertCanSeeTableRecords([$posA, $posB]);
    });
});

describe('sub-navigation reachability', function (): void {
    beforeEach(function (): void {
        Filament::setCurrentPanel(Filament::getPanel('management'));

        $this->regionA = Region::factory()->create();
        $this->regionB = Region::factory()->create();
        $this->terrA = Territory::factory()->for($this->regionA)->create();
        $this->terrB = Territory::factory()->for($this->regionB)->create();

        $this->positionA = Position::factory()->create(['territory_id' => $this->terrA->id]);
        $this->positionB = Position::factory()->create(['territory_id' => $this->terrB->id]);
    });

    it('an in-scope regional_head can reach all 3 tabs', function (): void {
        $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();
        $this->actingAs($head);

        $this->get(PositionResource::getUrl('view', ['record' => $this->positionA]))->assertOk();
        $this->get(PositionResource::getUrl('distributions', ['record' => $this->positionA]))->assertOk();
        $this->get(PositionResource::getUrl('calls', ['record' => $this->positionA]))->assertOk();
    });

    it('a regional_head cannot reach a position outside their region', function (): void {
        $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();
        $this->actingAs($head);

        $this->get(PositionResource::getUrl('view', ['record' => $this->positionB]))->assertNotFound();
        $this->get(PositionResource::getUrl('distributions', ['record' => $this->positionB]))->assertNotFound();
        $this->get(PositionResource::getUrl('calls', ['record' => $this->positionB]))->assertNotFound();
    });
});

describe('PositionDistributions tab', function (): void {
    it('lists only this position\'s distributions, sorted by creation date descending', function (): void {
        Filament::setCurrentPanel(Filament::getPanel('management'));

        $lead = User::factory()->withRole('hq_lead')->create();
        $position = Position::factory()->create();
        $otherPosition = Position::factory()->create();

        $older = Distribution::factory()->forPosition($position)->create();
        $older->created_at = now()->subDays(5);
        $older->saveQuietly();

        $newer = Distribution::factory()->forPosition($position)->create();
        $newer->created_at = now();
        $newer->saveQuietly();

        $foreign = Distribution::factory()->forPosition($otherPosition)->create();

        $this->actingAs($lead);

        livewire(PositionDistributions::class, ['record' => $position->getKey()])
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true)
            ->assertCanNotSeeTableRecords([$foreign]);
    });
});

describe('PositionCalls tab', function (): void {
    it('lists only this position\'s calls', function (): void {
        Filament::setCurrentPanel(Filament::getPanel('management'));

        $lead = User::factory()->withRole('hq_lead')->create();
        $position = Position::factory()->create();
        $otherPosition = Position::factory()->create();

        $own = Call::factory()->forPosition($position)->create();
        $foreign = Call::factory()->forPosition($otherPosition)->create();

        $this->actingAs($lead);

        livewire(PositionCalls::class, ['record' => $position->getKey()])
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign]);
    });
});
