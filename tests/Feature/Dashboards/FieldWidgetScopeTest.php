<?php

use App\Enums\DepositStatus;
use App\Filament\Field\Resources\Products\Widgets\ProductPerformanceOverviewWidget;
use App\Filament\Field\Resources\Products\Widgets\ProductPerformanceTrendWidget;
use App\Filament\Field\Widgets\CallSummaryWidget;
use App\Filament\Field\Widgets\OutstandingDepositsWidget;
use App\Filament\Field\Widgets\RecentDistributionsWidget;
use App\Filament\Field\Widgets\RepPerformanceOverviewWidget;
use App\Filament\Field\Widgets\YtdAttainmentWidget;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Cycle;
use App\Models\Deposit;
use App\Models\Distribution;
use App\Models\DistributionLine;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Product;
use App\Models\Region;
use App\Models\RepMonthlyTarget;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Support\Carbon;

use function Pest\Livewire\livewire;

/**
 * Field-panel widget scope: each widget shows only the authenticated rep's own data.
 */
beforeEach(function (): void {
    $this->region = Region::factory()->create();
    $this->territory = Territory::factory()->for($this->region)->create();
    $this->position = Position::factory()->create(['territory_id' => $this->territory->id]);
    $this->rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $this->position->id,
        'user_id' => $this->rep->id,
        'effective_from' => now()->subMonth(),
        'effective_to' => null,
    ]);
    $this->otherRep = User::factory()->withRole('sales_rep')->create();
});

describe('YtdAttainmentWidget', function (): void {
    it('shows the rep\'s own product targets and hides other reps\' products', function (): void {
        $cycle = Cycle::factory()->create(['is_current' => true]);
        $myProduct = Product::factory()->create(['name' => 'My Product Alpha']);
        $otherProduct = Product::factory()->create(['name' => 'Other Product Beta']);

        RepMonthlyTarget::factory()->create([
            'cycle_id' => $cycle->id,
            'user_id' => $this->rep->id,
            'product_id' => $myProduct->id,
            'year_month' => Carbon::now()->startOfMonth(),
            'target_qty' => 100,
        ]);

        RepMonthlyTarget::factory()->create([
            'cycle_id' => $cycle->id,
            'user_id' => $this->otherRep->id,
            'product_id' => $otherProduct->id,
            'year_month' => Carbon::now()->startOfMonth(),
            'target_qty' => 200,
        ]);

        $this->actingAs($this->rep);

        livewire(YtdAttainmentWidget::class)
            ->assertSee('My Product Alpha')
            ->assertDontSee('Other Product Beta');
    });
});

describe('RepPerformanceOverviewWidget', function (): void {
    it('totals target and actual across all of the rep\'s products, excluding other reps', function (): void {
        $cycle = Cycle::factory()->create([
            'is_current' => true,
            'starts_on' => now()->startOfMonth(),
            'ends_on' => now()->startOfMonth()->addYear()->subDay(),
        ]);
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        RepMonthlyTarget::factory()->create([
            'cycle_id' => $cycle->id,
            'user_id' => $this->rep->id,
            'product_id' => $productA->id,
            'year_month' => now()->startOfMonth(),
            'target_qty' => 40,
        ]);
        RepMonthlyTarget::factory()->create([
            'cycle_id' => $cycle->id,
            'user_id' => $this->rep->id,
            'product_id' => $productB->id,
            'year_month' => now()->startOfMonth(),
            'target_qty' => 60,
        ]);
        RepMonthlyTarget::factory()->create([
            'cycle_id' => $cycle->id,
            'user_id' => $this->otherRep->id,
            'product_id' => $productA->id,
            'year_month' => now()->startOfMonth(),
            'target_qty' => 999,
        ]);

        $myDistribution = Distribution::factory()->by($this->rep)->forPosition($this->position)->posted()
            ->create(['invoice_date' => now()]);
        DistributionLine::create([
            'distribution_id' => $myDistribution->id,
            'product_id' => $productA->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]);
        DistributionLine::create([
            'distribution_id' => $myDistribution->id,
            'product_id' => $productB->id,
            'quantity' => 40,
            'unit_price' => 100,
        ]);

        $otherDistribution = Distribution::factory()->by($this->otherRep)->forPosition($this->position)->posted()
            ->create(['invoice_date' => now()]);
        DistributionLine::create([
            'distribution_id' => $otherDistribution->id,
            'product_id' => $productA->id,
            'quantity' => 999,
            'unit_price' => 100,
        ]);

        $this->actingAs($this->rep);

        // Target 40+60=100, Actual 10+40=50 -> 50.0% attainment; the other rep's numbers must not leak in.
        livewire(RepPerformanceOverviewWidget::class)
            ->assertSee('100.00')
            ->assertSee('50.00')
            ->assertSee('50.0%')
            ->assertDontSee('999.00');
    });
});

describe('CallSummaryWidget', function (): void {
    it('counts only the authenticated rep\'s own calls this month', function (): void {
        Call::factory()->by($this->rep)->forPosition($this->position)->count(3)->create([
            'called_at' => now(),
        ]);
        Call::factory()->by($this->otherRep)->forPosition($this->position)->count(5)->create([
            'called_at' => now(),
        ]);

        $this->actingAs($this->rep);

        // The stat value "3" should appear; "5" and "8" (combined) should not be the total.
        livewire(CallSummaryWidget::class)
            ->assertSee('3');
    });
});

describe('RecentDistributionsWidget', function (): void {
    it('shows only the rep\'s own distributions', function (): void {
        $myDist = Distribution::factory()->by($this->rep)->forPosition($this->position)
            ->create(['invoice_number' => 'INV-MINE-001']);
        $otherDist = Distribution::factory()->by($this->otherRep)->forPosition($this->position)
            ->create(['invoice_number' => 'INV-OTHER-001']);

        $this->actingAs($this->rep);

        livewire(RecentDistributionsWidget::class)
            ->assertCanSeeTableRecords([$myDist])
            ->assertCanNotSeeTableRecords([$otherDist]);
    });
});

describe('OutstandingDepositsWidget', function (): void {
    it('counts only the rep\'s own unreconciled deposits', function (): void {
        Deposit::factory()->forCustomer(
            Customer::factory()->create(['territory_id' => $this->territory->id])
        )->create([
            'user_id' => $this->rep->id,
            'status' => DepositStatus::Unreconciled,
            'amount' => 50000,
        ]);

        Deposit::factory()->forCustomer(
            Customer::factory()->create(['territory_id' => $this->territory->id])
        )->create([
            'user_id' => $this->otherRep->id,
            'status' => DepositStatus::Unreconciled,
            'amount' => 99999,
        ]);

        $this->actingAs($this->rep);

        // Own deposit is shown (count 1 and value 50,000); other rep's deposit is hidden.
        livewire(OutstandingDepositsWidget::class)
            ->assertSee('1')
            ->assertDontSee('99,999');
    });
});

describe('ProductPerformanceOverviewWidget', function (): void {
    it('shows current-cycle target, actual and attainment for the rep and product only', function (): void {
        $cycle = Cycle::factory()->create([
            'is_current' => true,
            'starts_on' => now()->startOfMonth(),
            'ends_on' => now()->startOfMonth()->addYear()->subDay(),
        ]);
        $product = Product::factory()->create();

        RepMonthlyTarget::factory()->create([
            'cycle_id' => $cycle->id,
            'user_id' => $this->rep->id,
            'product_id' => $product->id,
            'year_month' => now()->startOfMonth(),
            'target_qty' => 40,
        ]);
        RepMonthlyTarget::factory()->create([
            'cycle_id' => $cycle->id,
            'user_id' => $this->otherRep->id,
            'product_id' => $product->id,
            'year_month' => now()->startOfMonth(),
            'target_qty' => 999,
        ]);

        $myDistribution = Distribution::factory()->by($this->rep)->forPosition($this->position)->posted()
            ->create(['invoice_date' => now()]);
        DistributionLine::create([
            'distribution_id' => $myDistribution->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'unit_price' => 100,
        ]);

        $otherDistribution = Distribution::factory()->by($this->otherRep)->forPosition($this->position)->posted()
            ->create(['invoice_date' => now()]);
        DistributionLine::create([
            'distribution_id' => $otherDistribution->id,
            'product_id' => $product->id,
            'quantity' => 999,
            'unit_price' => 100,
        ]);

        $this->actingAs($this->rep);

        // 20/40 = 50.0% attainment for the rep; the other rep's 999s must not leak in.
        livewire(ProductPerformanceOverviewWidget::class, ['record' => $product])
            ->assertSee('40.00')
            ->assertSee('20.00')
            ->assertSee('50.0%')
            ->assertDontSee('999.00');
    });
});

describe('ProductPerformanceTrendWidget', function (): void {
    it('returns month-by-month target vs actual for the rep and product only', function (): void {
        $cycle = Cycle::factory()->create([
            'is_current' => true,
            'starts_on' => now()->startOfMonth()->subMonths(2),
            'ends_on' => now()->startOfMonth()->subMonths(2)->addYear()->subDay(),
        ]);
        $product = Product::factory()->create();

        RepMonthlyTarget::factory()->create([
            'cycle_id' => $cycle->id,
            'user_id' => $this->rep->id,
            'product_id' => $product->id,
            'year_month' => now()->startOfMonth(),
            'target_qty' => 40,
        ]);

        $distribution = Distribution::factory()->by($this->rep)->forPosition($this->position)->posted()
            ->create(['invoice_date' => now()]);
        DistributionLine::create([
            'distribution_id' => $distribution->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'unit_price' => 100,
        ]);

        $this->actingAs($this->rep);

        $widget = new ProductPerformanceTrendWidget;
        $widget->record = $product;

        $data = (function (): array {
            return $this->getData();
        })->call($widget);

        $currentMonthIndex = array_search(now()->format('M Y'), $data['labels'], true);

        expect($currentMonthIndex)->not->toBeFalse()
            ->and($data['datasets'][0]['label'])->toBe('Target')
            ->and($data['datasets'][0]['data'][$currentMonthIndex])->toBe(40.0)
            ->and($data['datasets'][1]['label'])->toBe('Actual')
            ->and($data['datasets'][1]['data'][$currentMonthIndex])->toBe(20.0);
    });
});
