<?php

use App\Enums\DepositStatus;
use App\Filament\Field\Resources\Customers\CustomerResource;
use App\Filament\Field\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Field\Resources\Customers\Widgets\CustomerActivityWidget;
use App\Filament\Field\Resources\Customers\Widgets\CustomerDepositsWidget;
use App\Filament\Field\Resources\Customers\Widgets\CustomerDistributionsWidget;
use App\Filament\Field\Resources\Customers\Widgets\CustomerReconciliationWidget;
use App\Filament\Field\Resources\Customers\Widgets\CustomerTopProductsWidget;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Distribution;
use App\Models\DistributionLine;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Product;
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

    $this->otherRep = User::factory()->withRole('sales_rep')->create();

    $this->customer = Customer::factory()->create(['territory_id' => $this->territory->id]);

    $this->actingAs($this->rep);
});

test('a rep can view a customer in their territory scope', function (): void {
    livewire(ViewCustomer::class, ['record' => $this->customer->id])
        ->assertSuccessful()
        ->assertSee($this->customer->name);
});

test('a rep cannot view a customer outside their territory scope', function (): void {
    $foreign = Customer::factory()->create(['territory_id' => Territory::factory()->create()->id]);

    $this->get(CustomerResource::getUrl('view', ['record' => $foreign]))
        ->assertNotFound();
});

test('the customers list links each row to its view page', function (): void {
    $listUrl = CustomerResource::getUrl('index');
    $viewUrl = CustomerResource::getUrl('view', ['record' => $this->customer]);

    $this->get($listUrl)
        ->assertOk()
        ->assertSee($viewUrl, false);
});

describe('CustomerReconciliationWidget', function (): void {
    it('totals only the viewing rep\'s posted distributions and deposits for this customer', function (): void {
        // unit_price is always re-derived from the product on save, so pin it here
        // rather than passing unit_price directly to DistributionLine::create().
        $product = Product::factory()->create(['unit_price' => 100]);
        $product->teams()->attach($this->team->id);

        $myDistribution = Distribution::factory()->by($this->rep)->forPosition($this->position)->posted()
            ->create(['customer_id' => $this->customer->id]);
        DistributionLine::create([
            'distribution_id' => $myDistribution->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        // Draft distribution must be excluded from the "distributed" total.
        Distribution::factory()->by($this->rep)->forPosition($this->position)
            ->create(['customer_id' => $this->customer->id]);

        $myDeposit = Deposit::factory()->forCustomer($this->customer)->by($this->rep)
            ->create(['amount' => 400, 'status' => DepositStatus::Reconciled]);

        // Another rep's activity against the same customer must not leak into the totals.
        $otherDistribution = Distribution::factory()->by($this->otherRep)->forPosition($this->position)->posted()
            ->create(['customer_id' => $this->customer->id]);
        DistributionLine::create([
            'distribution_id' => $otherDistribution->id,
            'product_id' => $product->id,
            'quantity' => 999,
        ]);
        Deposit::factory()->forCustomer($this->customer)->by($this->otherRep)
            ->create(['amount' => 99999]);

        livewire(CustomerReconciliationWidget::class, ['record' => $this->customer])
            ->assertSee('1,000.00') // distributed: 10 * 100
            ->assertSee('400.00')   // deposited
            ->assertSee('600.00')   // outstanding balance: 1000 - 400
            ->assertDontSee('99,999.00');
    });
});

describe('CustomerActivityWidget', function (): void {
    it('shows the most recent distribution and deposit dates for this customer', function (): void {
        // invoice_date/deposit_date are date-only business dates and can be backdated;
        // the "ago" display must reflect created_at (when the record was actually logged),
        // not midnight of the business date.
        $distribution = Distribution::factory()->by($this->rep)->forPosition($this->position)
            ->create(['customer_id' => $this->customer->id, 'invoice_date' => now()->subDays(10)]);
        $deposit = Deposit::factory()->forCustomer($this->customer)->by($this->rep)
            ->create(['deposit_date' => now()->subDays(3)]);

        livewire(CustomerActivityWidget::class, ['record' => $this->customer])
            ->assertSee($distribution->created_at->ago())
            ->assertSee($deposit->created_at->ago());
    });

    it('falls back to placeholders when the customer has no activity', function (): void {
        livewire(CustomerActivityWidget::class, ['record' => $this->customer])
            ->assertSeeText('—');
    });
});

describe('CustomerTopProductsWidget', function (): void {
    it('breaks down posted distribution quantity and value by product, scoped to the rep', function (): void {
        $product = Product::factory()->create(['name' => 'Amoxicillin 500mg', 'unit_price' => 50]);
        $product->teams()->attach($this->team->id);

        $myDistribution = Distribution::factory()->by($this->rep)->forPosition($this->position)->posted()
            ->create(['customer_id' => $this->customer->id]);
        DistributionLine::create([
            'distribution_id' => $myDistribution->id,
            'product_id' => $product->id,
            'quantity' => 15,
        ]);

        $otherDistribution = Distribution::factory()->by($this->otherRep)->forPosition($this->position)->posted()
            ->create(['customer_id' => $this->customer->id]);
        DistributionLine::create([
            'distribution_id' => $otherDistribution->id,
            'product_id' => $product->id,
            'quantity' => 999,
        ]);

        livewire(CustomerTopProductsWidget::class, ['record' => $this->customer])
            ->assertSee('Amoxicillin 500mg')
            ->assertSee('15')
            ->assertDontSee('999');
    });
});

describe('CustomerDistributionsWidget and CustomerDepositsWidget', function (): void {
    it('shows only the viewing rep\'s own distributions and deposits for this customer', function (): void {
        $myDist = Distribution::factory()->by($this->rep)->forPosition($this->position)
            ->create(['customer_id' => $this->customer->id, 'invoice_number' => 'INV-MINE-001']);
        $otherDist = Distribution::factory()->by($this->otherRep)->forPosition($this->position)
            ->create(['customer_id' => $this->customer->id, 'invoice_number' => 'INV-OTHER-001']);

        $myDeposit = Deposit::factory()->forCustomer($this->customer)->by($this->rep)->create();
        $otherDeposit = Deposit::factory()->forCustomer($this->customer)->by($this->otherRep)->create();

        livewire(CustomerDistributionsWidget::class, ['record' => $this->customer])
            ->assertCanSeeTableRecords([$myDist])
            ->assertCanNotSeeTableRecords([$otherDist]);

        livewire(CustomerDepositsWidget::class, ['record' => $this->customer])
            ->assertCanSeeTableRecords([$myDeposit])
            ->assertCanNotSeeTableRecords([$otherDeposit]);
    });
});
