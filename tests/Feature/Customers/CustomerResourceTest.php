<?php

use App\Enums\CustomerType;
use App\Filament\Field\Resources\Customers\CustomerResource;
use App\Filament\Field\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Field\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\Position;
use App\Models\PositionAssignment;
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

    $this->actingAs($this->rep);
});

test('a rep creates a customer: created_by is forced and territory_id is derived from the position', function (): void {
    livewire(CreateCustomer::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'name' => 'Corner Pharmacy',
            'type' => CustomerType::Pharmacy->value,
            'phone' => '08012345678',
            'address' => '1 Market Road',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $customer = Customer::sole();

    expect($customer->created_by)->toBe($this->rep->id)
        ->and($customer->territory_id)->toBe($this->territory->id)
        ->and($customer->name)->toBe('Corner Pharmacy');
});

test('a rep with no active position cannot create a customer', function (): void {
    $repWithoutPosition = User::factory()->withRole('sales_rep')->create();
    $this->actingAs($repWithoutPosition);

    livewire(CreateCustomer::class)
        ->fillForm([
            'name' => 'Corner Pharmacy',
            'type' => CustomerType::Pharmacy->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['position_id']);

    expect(Customer::count())->toBe(0);
});

test('the list shows customers in the rep territory only', function (): void {
    $own = Customer::factory()->create(['territory_id' => $this->territory->id]);

    $elsewhere = Territory::factory()->create();
    $foreign = Customer::factory()->create(['territory_id' => $elsewhere->id]);

    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

test('a rep can edit their own customer but not another rep\'s customer', function (): void {
    $own = Customer::factory()->by($this->rep)->create(['territory_id' => $this->territory->id]);

    $otherRep = User::factory()->withRole('sales_rep')->create();
    $foreign = Customer::factory()->by($otherRep)->create(['territory_id' => $this->territory->id]);

    expect($this->rep->can('update', $own))->toBeTrue()
        ->and($this->rep->can('update', $foreign))->toBeFalse();
});

test('the "new customer" button on the list page links to the create page instead of opening the default modal', function (): void {
    // Regression guard: the default header CreateAction is a bare modal that
    // fills Customer directly and drops position_id (not a column), leaving
    // territory_id null. It must link to CreateCustomer's page instead, which
    // derives territory_id from the chosen position.
    $createUrl = CustomerResource::getUrl('create');

    $this->get(CustomerResource::getUrl('index'))
        ->assertOk()
        ->assertSee($createUrl, false);

    $this->get($createUrl)->assertOk();
});

test('sales_rep, supervisor and platform_admin may create customers but other roles may not', function (): void {
    expect($this->rep->can('create', Customer::class))->toBeTrue();

    foreach (['supervisor', 'platform_admin'] as $role) {
        $user = User::factory()->withRole($role)->create();
        expect($user->can('create', Customer::class))->toBeTrue();
    }

    foreach (['hq_lead', 'regional_head', 'accountant'] as $role) {
        $user = User::factory()->withRole($role)->create();
        expect($user->can('create', Customer::class))->toBeFalse();
    }
});
