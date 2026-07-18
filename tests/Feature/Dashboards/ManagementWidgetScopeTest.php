<?php

use App\Enums\DepositStatus;
use App\Enums\PositionStatus;
use App\Filament\Management\Resources\Positions\Widgets\PositionsSummaryWidget;
use App\Filament\Management\Widgets\AttainmentLeaderboardWidget;
use App\Filament\Management\Widgets\CallCoverageWidget;
use App\Filament\Management\Widgets\StrictCoverageGapsWidget;
use App\Filament\Management\Widgets\VacantPositionsWidget;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Cycle;
use App\Models\Deposit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Region;
use App\Models\RepMonthlyTarget;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Support\Carbon;

use function Pest\Livewire\livewire;

/**
 * Management-panel widget scope: regional_head sees their region only;
 * hq_lead sees everything.
 */
beforeEach(function (): void {
    $this->regionA = Region::factory()->create();
    $this->regionB = Region::factory()->create();
    $this->terrA = Territory::factory()->for($this->regionA)->create();
    $this->terrB = Territory::factory()->for($this->regionB)->create();
});

describe('AttainmentLeaderboardWidget', function (): void {
    it('regional_head sees only reps in their region', function (): void {
        $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

        $repA = User::factory()->withRole('sales_rep')->create(['name' => 'Rep In Region A']);
        $repB = User::factory()->withRole('sales_rep')->create(['name' => 'Rep In Region B']);

        $posA = Position::factory()->create(['territory_id' => $this->terrA->id]);
        $posB = Position::factory()->create(['territory_id' => $this->terrB->id]);

        PositionAssignment::factory()->create(['position_id' => $posA->id, 'user_id' => $repA->id, 'effective_to' => null]);
        PositionAssignment::factory()->create(['position_id' => $posB->id, 'user_id' => $repB->id, 'effective_to' => null]);

        $cycle = Cycle::factory()->create(['is_current' => true]);
        RepMonthlyTarget::factory()->create(['cycle_id' => $cycle->id, 'user_id' => $repA->id, 'year_month' => Carbon::now()->startOfMonth()]);
        RepMonthlyTarget::factory()->create(['cycle_id' => $cycle->id, 'user_id' => $repB->id, 'year_month' => Carbon::now()->startOfMonth()]);

        $this->actingAs($head);

        livewire(AttainmentLeaderboardWidget::class)
            ->assertSee('Rep In Region A')
            ->assertDontSee('Rep In Region B');
    });

    it('hq_lead sees all reps across all regions', function (): void {
        $lead = User::factory()->withRole('hq_lead')->create();

        $repA = User::factory()->withRole('sales_rep')->create(['name' => 'Alpha Rep']);
        $repB = User::factory()->withRole('sales_rep')->create(['name' => 'Beta Rep']);

        $posA = Position::factory()->create(['territory_id' => $this->terrA->id]);
        $posB = Position::factory()->create(['territory_id' => $this->terrB->id]);

        PositionAssignment::factory()->create(['position_id' => $posA->id, 'user_id' => $repA->id, 'effective_to' => null]);
        PositionAssignment::factory()->create(['position_id' => $posB->id, 'user_id' => $repB->id, 'effective_to' => null]);

        $cycle = Cycle::factory()->create(['is_current' => true]);
        RepMonthlyTarget::factory()->create(['cycle_id' => $cycle->id, 'user_id' => $repA->id, 'year_month' => Carbon::now()->startOfMonth()]);
        RepMonthlyTarget::factory()->create(['cycle_id' => $cycle->id, 'user_id' => $repB->id, 'year_month' => Carbon::now()->startOfMonth()]);

        $this->actingAs($lead);

        livewire(AttainmentLeaderboardWidget::class)
            ->assertSee('Alpha Rep')
            ->assertSee('Beta Rep');
    });
});

describe('CallCoverageWidget', function (): void {
    it('regional_head sees only territories in their region', function (): void {
        $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

        Call::factory()->create(['territory_id' => $this->terrA->id, 'called_at' => now()]);
        Call::factory()->create(['territory_id' => $this->terrB->id, 'called_at' => now()]);

        $this->actingAs($head);

        livewire(CallCoverageWidget::class)
            ->assertSee($this->terrA->name)
            ->assertDontSee($this->terrB->name);
    });
});

describe('VacantPositionsWidget', function (): void {
    it('shows active positions with no open assignment', function (): void {
        $lead = User::factory()->withRole('hq_lead')->create();

        // Vacant: active position with no assignment.
        $vacant = Position::factory()->create([
            'territory_id' => $this->terrA->id,
            'status' => PositionStatus::Active,
        ]);

        // Occupied: active position WITH an open assignment.
        $occupied = Position::factory()->create([
            'territory_id' => $this->terrA->id,
            'status' => PositionStatus::Active,
        ]);
        PositionAssignment::factory()->create([
            'position_id' => $occupied->id,
            'user_id' => User::factory()->create()->id,
            'effective_to' => null,
        ]);

        $this->actingAs($lead);

        livewire(VacantPositionsWidget::class)
            ->assertCanSeeTableRecords([$vacant])
            ->assertCanNotSeeTableRecords([$occupied]);
    });

    it('regional_head sees only vacant positions in their region', function (): void {
        $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

        $vacantA = Position::factory()->create([
            'territory_id' => $this->terrA->id,
            'status' => PositionStatus::Active,
        ]);
        $vacantB = Position::factory()->create([
            'territory_id' => $this->terrB->id,
            'status' => PositionStatus::Active,
        ]);

        $this->actingAs($head);

        livewire(VacantPositionsWidget::class)
            ->assertCanSeeTableRecords([$vacantA])
            ->assertCanNotSeeTableRecords([$vacantB]);
    });
});

describe('StrictCoverageGapsWidget', function (): void {
    it('shows only vacant positions in strict territories', function (): void {
        $lead = User::factory()->withRole('hq_lead')->create();

        $strictTerr = Territory::factory()->for($this->regionA)->strict()->create();
        $liberalTerr = Territory::factory()->for($this->regionA)->liberal()->create();

        // Strict team for strict territory.
        $strictTeam = Team::factory()->strict()->create();
        $strictVacant = Position::factory()->create([
            'territory_id' => $strictTerr->id,
            'team_id' => $strictTeam->id,
            'status' => PositionStatus::Active,
        ]);

        // Liberal vacant position — should NOT appear.
        $liberalTeam = Team::factory()->liberal()->create();
        $liberalVacant = Position::factory()->create([
            'territory_id' => $liberalTerr->id,
            'team_id' => $liberalTeam->id,
            'status' => PositionStatus::Active,
        ]);

        $this->actingAs($lead);

        livewire(StrictCoverageGapsWidget::class)
            ->assertCanSeeTableRecords([$strictVacant])
            ->assertCanNotSeeTableRecords([$liberalVacant]);
    });
});

describe('PositionsSummaryWidget', function (): void {
    it('scopes position counts to the region for a regional_head', function (): void {
        $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

        // In region A: one vacant, one occupied -> Occupied % = 50.0.
        Position::factory()->create(['territory_id' => $this->terrA->id, 'status' => PositionStatus::Active]);
        $occupied = Position::factory()->create(['territory_id' => $this->terrA->id, 'status' => PositionStatus::Active]);
        PositionAssignment::factory()->create([
            'position_id' => $occupied->id,
            'user_id' => User::factory()->create()->id,
            'effective_to' => null,
        ]);

        // Out of region — must not affect the count.
        Position::factory()->count(5)->create(['territory_id' => $this->terrB->id, 'status' => PositionStatus::Active]);

        $this->actingAs($head);

        livewire(PositionsSummaryWidget::class)
            ->assertSee('50.0%');
    });
});

describe('ReconciliationStatusWidget', function (): void {
    it('counts deposits scoped to the viewer\'s region', function (): void {
        $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

        $customerA = Customer::factory()->create(['territory_id' => $this->terrA->id]);
        $customerB = Customer::factory()->create(['territory_id' => $this->terrB->id]);

        Deposit::factory()->forCustomer($customerA)->count(2)->create([
            'status' => DepositStatus::Unreconciled,
        ]);
        Deposit::factory()->forCustomer($customerB)->count(5)->create([
            'status' => DepositStatus::Unreconciled,
        ]);

        // The head's scope returns only terrA deposits (count = 2), not 7.
        $scoped = Deposit::visibleTo($head)->where('status', DepositStatus::Unreconciled->value)->count();
        expect($scoped)->toBe(2);
    });
});
