<?php

use App\Enums\DistributionStatus;
use App\Filament\Shared\Resources\Distributions\DistributionResource;
use App\Filament\Shared\Resources\Distributions\Pages\CreateDistribution;
use App\Filament\Shared\Resources\Distributions\Pages\ListDistributions;
use App\Models\Customer;
use App\Models\Distribution;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Product;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

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

    $this->product = Product::factory()->create(['unit_price' => '1000.00']);
    $this->product->teams()->attach($this->team->id);

    $this->actingAs($this->rep);
});

test('user_id is forced and territory_id and team_id are derived server-side', function (): void {
    livewire(CreateDistribution::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-SERVER-001',
            'invoice_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => '1000.00'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $dist = Distribution::sole();

    expect($dist->user_id)->toBe($this->rep->id)
        ->and($dist->territory_id)->toBe($this->territory->id)
        ->and($dist->team_id)->toBe($this->team->id)
        ->and($dist->status)->toBe(DistributionStatus::Draft);
});

test('line_amount and total_amount are always recomputed server-side regardless of client input', function (): void {
    // The form has no line_amount field, but we pass a deliberately wrong quantity/unit_price combo.
    // The server must recompute: 3 * 400 = 1200, not whatever the client might send.
    livewire(CreateDistribution::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-TOTAL-001',
            'invoice_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => '400.00'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $dist = Distribution::sole();
    $line = $dist->lines->first();

    expect((float) $line->line_amount->amount)->toBe(1200.0)
        ->and((float) $dist->total_amount->amount)->toBe(1200.0);
});

test('total_amount is the sum of all line_amounts', function (): void {
    $productB = Product::factory()->create();
    $productB->teams()->attach($this->team->id);

    livewire(CreateDistribution::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-TOTAL-002',
            'invoice_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => '500.00'],  // 1000
                ['product_id' => $productB->id,      'quantity' => 4, 'unit_price' => '250.00'],  // 1000
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect((float) Distribution::sole()->total_amount->amount)->toBe(2000.0);
});

test('void distributions are excluded from the posted total', function (): void {
    // Three distributions in the same territory:
    //   posted × 2 (total_amount 500 each) → counted
    //   void × 1 (total_amount 300) → excluded
    $pos = $this->position;

    Distribution::factory()->forPosition($pos)->posted()->create(['total_amount' => 500]);
    Distribution::factory()->forPosition($pos)->posted()->create(['total_amount' => 500]);
    Distribution::factory()->forPosition($pos)->void()->create(['total_amount' => 300]);

    $postedSum = Distribution::where('status', DistributionStatus::Posted)
        ->sum('total_amount');

    expect((float) $postedSum)->toBe(1000.0);
});

test('only status=posted counts; draft and void do not', function (): void {
    $pos = $this->position;

    Distribution::factory()->forPosition($pos)->create(['total_amount' => 200]);            // draft
    Distribution::factory()->forPosition($pos)->posted()->create(['total_amount' => 600]);
    Distribution::factory()->forPosition($pos)->void()->create(['total_amount' => 400]);

    $postedSum = Distribution::where('status', DistributionStatus::Posted)->sum('total_amount');

    expect((float) $postedSum)->toBe(600.0);
});

test('a sales_rep sees only their own distributions in the list', function (): void {
    $own = Distribution::factory()->by($this->rep)->forPosition($this->position)->create();

    $otherRep = User::factory()->create();
    $otherPos = Position::factory()->create(['territory_id' => $this->territory->id]);
    $foreign = Distribution::factory()->by($otherRep)->forPosition($otherPos)->create();

    livewire(ListDistributions::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

test('sales_rep and supervisor may create; management roles may not', function (): void {
    expect($this->rep->can('create', Distribution::class))->toBeTrue();

    $supervisor = User::factory()->withRole('supervisor')->create();
    expect($supervisor->can('create', Distribution::class))->toBeTrue();

    foreach (['hq_lead', 'regional_head', 'platform_admin', 'accountant'] as $role) {
        $user = User::factory()->withRole($role)->create();
        expect($user->can('create', Distribution::class))->toBeFalse();
    }
});

test('the Distributions resource is registered in field and management panels, not office', function (): void {
    $field = Filament::getPanel('field')->getResources();
    $management = Filament::getPanel('management')->getResources();
    $office = Filament::getPanel('office')->getResources();

    expect($field)->toContain(DistributionResource::class)
        ->and($management)->toContain(DistributionResource::class)
        ->and($office)->not->toContain(DistributionResource::class);
});
