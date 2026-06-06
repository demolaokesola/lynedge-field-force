<?php

use App\Filament\Shared\Resources\Distributions\Pages\CreateDistribution;
use App\Filament\Shared\Resources\Distributions\Schemas\DistributionForm;
use App\Models\Customer;
use App\Models\Distribution;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Product;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use App\Services\RepScope;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The product guard: each line's product must belong to the position's team's catalogue.
 */
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

    $this->teamProduct = Product::factory()->create();
    $this->teamProduct->teams()->attach($this->team->id);

    $this->actingAs($this->rep);
});

test('a strict rep is blocked when a line contains a product outside their team', function (): void {
    $foreignTeam = Team::factory()->strict()->create();
    $foreignProduct = Product::factory()->create();
    $foreignProduct->teams()->attach($foreignTeam->id);

    livewire(CreateDistribution::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-GUARD-001',
            'invoice_date' => today()->toDateString(),
            'lines' => [
                [
                    'product_id' => $foreignProduct->id,
                    'quantity' => 10,
                    'unit_price' => 500,
                ],
            ],
        ])
        ->call('create');

    expect(Distribution::count())->toBe(0);
});

test('a strict rep succeeds when a line contains a product from their team', function (): void {
    livewire(CreateDistribution::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-GUARD-002',
            'invoice_date' => today()->toDateString(),
            'lines' => [
                [
                    'product_id' => $this->teamProduct->id,
                    'quantity' => 10,
                    'unit_price' => '500.00',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Distribution::count())->toBe(1);
});

test('a liberal rep can distribute any product in their liberal team catalogue', function (): void {
    $liberalTeam = Team::factory()->liberal()->create();
    $liberalTerritory = Territory::factory()->liberal()->create();
    $liberalPosition = Position::factory()->create([
        'territory_id' => $liberalTerritory->id,
        'team_id' => $liberalTeam->id,
    ]);
    $liberalCustomer = Customer::factory()->create(['territory_id' => $liberalTerritory->id]);

    $repB = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $liberalPosition->id,
        'user_id' => $repB->id,
        'effective_to' => null,
    ]);

    $productA = Product::factory()->create();
    $productB = Product::factory()->create();
    $productA->teams()->attach($liberalTeam->id);
    $productB->teams()->attach($liberalTeam->id);

    $this->actingAs($repB);

    livewire(CreateDistribution::class)
        ->fillForm([
            'position_id' => $liberalPosition->id,
            'customer_id' => $liberalCustomer->id,
            'invoice_number' => 'INV-LIB-001',
            'invoice_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $productA->id, 'quantity' => 5, 'unit_price' => '200.00'],
                ['product_id' => $productB->id, 'quantity' => 3, 'unit_price' => '150.00'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Distribution::count())->toBe(1);
});

test('RepScope::productsForPosition returns only the position team products, not others', function (): void {
    $otherTeam = Team::factory()->strict()->create();
    $otherProduct = Product::factory()->create();
    $otherProduct->teams()->attach($otherTeam->id);

    $allowed = app(RepScope::class)->productsForPosition($this->position)->pluck('id')->all();

    expect($allowed)->toContain($this->teamProduct->id)
        ->and($allowed)->not->toContain($otherProduct->id);
});

test('the product select options in the form are scoped to the position team', function (): void {
    $foreignTeam = Team::factory()->strict()->create();
    $foreignProduct = Product::factory()->create();
    $foreignProduct->teams()->attach($foreignTeam->id);

    $options = DistributionForm::productOptions($this->position->id);

    expect($options)->toHaveKey($this->teamProduct->id)
        ->and($options)->not->toHaveKey($foreignProduct->id);
});
