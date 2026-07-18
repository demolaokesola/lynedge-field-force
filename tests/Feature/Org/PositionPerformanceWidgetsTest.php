<?php

use App\Filament\Management\Resources\Positions\Widgets\PositionPerformanceChartWidget;
use App\Filament\Management\Resources\Positions\Widgets\PositionPerformanceOverviewWidget;
use App\Filament\Management\Resources\Positions\Widgets\PositionTopCustomersWidget;
use App\Filament\Management\Resources\Positions\Widgets\PositionTopProductsWidget;
use App\Models\Customer;
use App\Models\Cycle;
use App\Models\Deposit;
use App\Models\Distribution;
use App\Models\DistributionLine;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Product;
use App\Models\RepMonthlyTarget;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('management'));

    $this->lead = User::factory()->withRole('hq_lead')->create();
    $this->position = Position::factory()->create();

    $this->cycle = Cycle::factory()->current()->create([
        'starts_on' => now()->subMonth()->startOfMonth(),
        'ends_on' => now()->addMonths(6)->endOfMonth(),
    ]);

    $this->occupant = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $this->position->id,
        'user_id' => $this->occupant->id,
        'effective_to' => null,
    ]);

    $this->actingAs($this->lead);
});

describe('PositionPerformanceOverviewWidget', function (): void {
    it('sums target value, distribution value and deposits for the current occupant', function (): void {
        $product = Product::factory()->create(['unit_price' => 100]);

        RepMonthlyTarget::factory()->create([
            'cycle_id' => $this->cycle->id,
            'user_id' => $this->occupant->id,
            'product_id' => $product->id,
            'year_month' => now()->startOfMonth(),
            'target_qty' => 10,
        ]);

        $distribution = Distribution::factory()->forPosition($this->position)->posted()
            ->create(['invoice_date' => now()]);
        DistributionLine::create([
            'distribution_id' => $distribution->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        Deposit::factory()->by($this->occupant)->create(['amount' => 250, 'deposit_date' => now()]);

        livewire(PositionPerformanceOverviewWidget::class, ['record' => $this->position])
            ->assertSee('1,000.00') // target value: 10 * 100
            ->assertSee('500.00')   // distribution value: 5 * 100
            ->assertSee('250.00')   // deposits
            ->assertSee('50.0%');   // attainment: 500 / 1000
    });

    it('excludes a previous occupant\'s deposits after reassignment', function (): void {
        $previousOccupant = User::factory()->withRole('sales_rep')->create();
        Deposit::factory()->by($previousOccupant)->create(['amount' => 999]);

        livewire(PositionPerformanceOverviewWidget::class, ['record' => $this->position])
            ->assertDontSee('999.00');
    });

    it('falls back to placeholders when there is no current cycle', function (): void {
        $this->cycle->update(['is_current' => false]);

        livewire(PositionPerformanceOverviewWidget::class, ['record' => $this->position])
            ->assertSeeText('—');
    });
});

describe('PositionPerformanceChartWidget', function (): void {
    it('renders target, distribution and deposit values as chart data', function (): void {
        $product = Product::factory()->create(['unit_price' => 100]);

        RepMonthlyTarget::factory()->create([
            'cycle_id' => $this->cycle->id,
            'user_id' => $this->occupant->id,
            'product_id' => $product->id,
            'year_month' => now()->startOfMonth(),
            'target_qty' => 10,
        ]);

        $distribution = Distribution::factory()->forPosition($this->position)->posted()
            ->create(['invoice_date' => now()]);
        DistributionLine::create([
            'distribution_id' => $distribution->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        Deposit::factory()->by($this->occupant)->create(['amount' => 250, 'deposit_date' => now()]);

        $widget = new PositionPerformanceChartWidget;
        $widget->record = $this->position;

        $data = (fn () => $this->getData())->call($widget);

        expect($data['labels'])->toBe(['Target', 'Distribution Value', 'Deposits'])
            ->and($data['datasets'][0]['data'])->toBe([1000.0, 500.0, 250.0]);
    });
});

describe('PositionTopProductsWidget', function (): void {
    it('breaks down posted distribution quantity and value by product for this position', function (): void {
        $product = Product::factory()->create(['name' => 'Amoxicillin 500mg', 'unit_price' => 50]);

        $ownDistribution = Distribution::factory()->forPosition($this->position)->posted()->create();
        DistributionLine::create([
            'distribution_id' => $ownDistribution->id,
            'product_id' => $product->id,
            'quantity' => 15,
        ]);

        $otherPosition = Position::factory()->create();
        $foreignDistribution = Distribution::factory()->forPosition($otherPosition)->posted()->create();
        DistributionLine::create([
            'distribution_id' => $foreignDistribution->id,
            'product_id' => $product->id,
            'quantity' => 999,
        ]);

        livewire(PositionTopProductsWidget::class, ['record' => $this->position])
            ->assertSee('Amoxicillin 500mg')
            ->assertSee('15.00')
            ->assertDontSee('999.00');
    });
});

describe('PositionTopCustomersWidget', function (): void {
    it('ranks customers by deposit value for the current occupant', function (): void {
        $customer = Customer::factory()->create(['name' => 'Lagos Central Pharmacy']);

        Deposit::factory()->by($this->occupant)->forCustomer($customer)->create(['amount' => 300]);

        $previousOccupant = User::factory()->withRole('sales_rep')->create();
        Deposit::factory()->by($previousOccupant)->forCustomer($customer)->create(['amount' => 999]);

        livewire(PositionTopCustomersWidget::class, ['record' => $this->position])
            ->assertSee('Lagos Central Pharmacy')
            ->assertSee('300.00')
            ->assertDontSee('999.00');
    });
});
