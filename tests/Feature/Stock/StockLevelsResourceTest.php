<?php

use App\Filament\Field\Resources\StockLevels\Pages\ListStockLevels as FieldListStockLevels;
use App\Filament\Management\Resources\StockLevels\Pages\ListStockLevels as ManagementListStockLevels;
use App\Filament\Management\Widgets\RegionStockRollupWidget;
use App\Filament\Office\Resources\StockLevels\Pages\ListStockLevels as OfficeListStockLevels;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionProductStock;
use App\Models\Product;
use App\Models\Region;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The same balances table is exposed in all three panels; each one narrows rows through
 * PositionProductStock::visibleTo(). The Management dashboard additionally rolls the
 * balances up per region and product.
 */
beforeEach(function (): void {
    $this->regionA = Region::factory()->create(['name' => 'Region Alpha']);
    $this->regionB = Region::factory()->create(['name' => 'Region Beta']);
    // Liberal territories + one liberal team: two positions can share the team in a
    // territory, and a single product can sit in every position's catalogue (a
    // product may belong to at most one strict team).
    $this->terrA = Territory::factory()->liberal()->for($this->regionA)->create();
    $this->terrB = Territory::factory()->liberal()->for($this->regionB)->create();
    $team = Team::factory()->liberal()->create();
    $this->positionA = Position::factory()->create(['territory_id' => $this->terrA->id, 'team_id' => $team->id]);
    $this->positionA2 = Position::factory()->create(['territory_id' => $this->terrA->id, 'team_id' => $team->id]);
    $this->positionB = Position::factory()->create(['territory_id' => $this->terrB->id, 'team_id' => $team->id]);

    $this->product = Product::factory()->create(['name' => 'Widget Tablets']);
    $this->product->teams()->attach($team->id);

    $this->stockA = PositionProductStock::factory()->forPosition($this->positionA)->create(['product_id' => $this->product->id, 'quantity' => 30]);
    $this->stockA2 = PositionProductStock::factory()->forPosition($this->positionA2)->create(['product_id' => $this->product->id, 'quantity' => -5]);
    $this->stockB = PositionProductStock::factory()->forPosition($this->positionB)->create(['product_id' => $this->product->id, 'quantity' => 12]);
});

test('a rep sees only balances for positions they hold in the Field panel', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create(['position_id' => $this->positionA->id, 'user_id' => $rep->id, 'effective_to' => null]);
    $this->actingAs($rep);

    livewire(FieldListStockLevels::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->stockA])
        ->assertCanNotSeeTableRecords([$this->stockA2, $this->stockB]);
});

test('operations sees every balance in the Office panel and can filter to negatives', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('platform_admin')->create());

    livewire(OfficeListStockLevels::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->stockA, $this->stockA2, $this->stockB])
        ->filterTable('negative')
        ->assertCanSeeTableRecords([$this->stockA2])
        ->assertCanNotSeeTableRecords([$this->stockA, $this->stockB]);
});

test('the accountant can open Stock Levels in the Office panel', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('accountant')->create());

    livewire(OfficeListStockLevels::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->stockA, $this->stockB]);
});

test('a regional_head sees only their region\'s balances in the Management panel', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('management'));
    $this->actingAs(User::factory()->withRole('regional_head')->inRegion($this->regionA)->create());

    livewire(ManagementListStockLevels::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->stockA, $this->stockA2])
        ->assertCanNotSeeTableRecords([$this->stockB]);
});

test('hq_lead sees every balance in the Management panel', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('management'));
    $this->actingAs(User::factory()->withRole('hq_lead')->create());

    livewire(ManagementListStockLevels::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->stockA, $this->stockA2, $this->stockB]);
});

test('nobody can create, edit or delete a balance row', function (): void {
    $ops = User::factory()->withRole('platform_admin')->create();

    expect($ops->can('create', PositionProductStock::class))->toBeFalse()
        ->and($ops->can('update', $this->stockA))->toBeFalse()
        ->and($ops->can('delete', $this->stockA))->toBeFalse()
        ->and($ops->can('viewAny', PositionProductStock::class))->toBeTrue();
});

describe('RegionStockRollupWidget', function (): void {
    beforeEach(function (): void {
        Filament::setCurrentPanel(Filament::getPanel('management'));
    });

    it('is visible to management roles only', function (string $role, bool $visible): void {
        $this->actingAs(User::factory()->withRole($role)->create());

        expect(RegionStockRollupWidget::canView())->toBe($visible);
    })->with([
        'hq_lead' => ['hq_lead', true],
        'regional_head' => ['regional_head', true],
        'superuser' => ['superuser', true],
        'platform_admin' => ['platform_admin', false],
        'sales_rep' => ['sales_rep', false],
    ]);

    it('sums balances per region and product for hq_lead, counting positions in deficit', function (): void {
        $this->actingAs(User::factory()->withRole('hq_lead')->create());

        livewire(RegionStockRollupWidget::class)
            ->assertOk()
            ->assertSee('Region Alpha')
            ->assertSee('Region Beta')
            ->assertSee('Widget Tablets');

        $rows = livewire(RegionStockRollupWidget::class)->instance()->getTableRecords()->keyBy('region_name');

        expect((float) $rows['Region Alpha']->on_hand)->toBe(25.0)
            ->and((int) $rows['Region Alpha']->position_count)->toBe(2)
            ->and((int) $rows['Region Alpha']->negative_count)->toBe(1)
            ->and((float) $rows['Region Beta']->on_hand)->toBe(12.0)
            ->and((int) $rows['Region Beta']->negative_count)->toBe(0);
    });

    it('limits a regional_head to their own region', function (): void {
        $this->actingAs(User::factory()->withRole('regional_head')->inRegion($this->regionB)->create());

        livewire(RegionStockRollupWidget::class)
            ->assertSee('Region Beta')
            ->assertDontSee('Region Alpha');
    });
});
