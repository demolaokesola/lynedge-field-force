<?php

use App\Enums\CallType;
use App\Filament\Shared\Resources\Calls\Pages\CreateCall;
use App\Filament\Shared\Resources\Calls\Pages\ListCalls;
use App\Filament\Shared\Resources\Calls\Schemas\CallForm;
use App\Models\Call;
use App\Models\DemandCreator;
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

    $this->demandCreator = DemandCreator::factory()->create(['territory_id' => $this->territory->id]);
    $this->product = Product::factory()->create();
    $this->product->teams()->attach($this->team->id);

    $this->actingAs($this->rep);
});

test('a rep logs a call: user_id is forced and territory_id is derived from the position', function (): void {
    // A territory the rep tries to associate is irrelevant — territory comes from position.
    livewire(CreateCall::class)
        ->fillForm([
            'position_id' => $this->position->id,
            'called_at' => now(),
            'call_type' => CallType::PhysicalVisit->value,
            'demand_creator_id' => $this->demandCreator->id,
            'products' => [$this->product->id],
            'notes' => 'Detailed the product.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $call = Call::sole();

    expect($call->user_id)->toBe($this->rep->id)
        ->and($call->position_id)->toBe($this->position->id)
        ->and($call->territory_id)->toBe($this->territory->id)
        ->and($call->products->pluck('id')->all())->toEqual([$this->product->id]);
});

test('a rep with no active position cannot create a call', function (): void {
    $repWithoutPosition = User::factory()->withRole('sales_rep')->create();
    $this->actingAs($repWithoutPosition);

    livewire(CreateCall::class)
        ->fillForm([
            'called_at' => now(),
            'call_type' => CallType::PhoneCall->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['position_id']);

    expect(Call::count())->toBe(0);
});

test('the list shows the rep their own calls only', function (): void {
    $own = Call::factory()->by($this->rep)->forPosition($this->position)->create();

    $otherRep = User::factory()->create();
    $otherPosition = Position::factory()->create(['territory_id' => $this->territory->id]);
    $foreign = Call::factory()->by($otherRep)->forPosition($otherPosition)->create();

    livewire(ListCalls::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

test('the demand-creator options are scoped to the position territory', function (): void {
    $elsewhere = Territory::factory()->create();
    $foreignCreator = DemandCreator::factory()->create(['territory_id' => $elsewhere->id]);

    $options = CallForm::demandCreatorOptions($this->position->id);

    expect($options)->toHaveKey($this->demandCreator->id)
        ->and($options)->not->toHaveKey($foreignCreator->id);
});

test('the product options are scoped to the position team', function (): void {
    $otherTeam = Team::factory()->strict()->create();
    $foreignProduct = Product::factory()->create();
    $foreignProduct->teams()->attach($otherTeam->id);

    $productIds = CallForm::scopeProductsToTeam(Product::query(), $this->position->id)->pluck('id')->all();

    expect($productIds)->toContain($this->product->id)
        ->and($productIds)->not->toContain($foreignProduct->id);
});

test('a sales_rep may create calls but management roles may not', function (): void {
    expect($this->rep->can('create', Call::class))->toBeTrue();

    foreach (['hq_lead', 'regional_head', 'platform_admin', 'accountant'] as $role) {
        $user = User::factory()->withRole($role)->create();
        expect($user->can('create', Call::class))->toBeFalse();
    }
});
