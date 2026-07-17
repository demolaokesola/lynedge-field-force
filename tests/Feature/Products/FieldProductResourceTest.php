<?php

use App\Filament\Field\Resources\Products\Pages\ListProducts;
use App\Filament\Field\Resources\Products\Pages\ViewProduct;
use App\Filament\Field\Resources\Products\ProductResource;
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

    $this->rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create([
        'position_id' => $this->position->id,
        'user_id' => $this->rep->id,
        'effective_to' => null,
    ]);

    $this->actingAs($this->rep);
});

test('the list shows only active products belonging to the rep\'s team', function (): void {
    $own = Product::factory()->create(['active' => true]);
    $own->teams()->attach($this->team->id);

    $inactive = Product::factory()->create(['active' => false]);
    $inactive->teams()->attach($this->team->id);

    $foreignTeam = Team::factory()->strict()->create();
    $foreign = Product::factory()->create(['active' => true]);
    $foreign->teams()->attach($foreignTeam->id);

    livewire(ListProducts::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$inactive, $foreign]);
});

test('a rep can view a product in their team scope', function (): void {
    $product = Product::factory()->create(['active' => true]);
    $product->teams()->attach($this->team->id);

    livewire(ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful()
        ->assertSee($product->name);
});

test('a rep cannot view a product outside their team scope', function (): void {
    $foreignTeam = Team::factory()->strict()->create();
    $foreign = Product::factory()->create(['active' => true]);
    $foreign->teams()->attach($foreignTeam->id);

    $this->get(ProductResource::getUrl('view', ['record' => $foreign]))
        ->assertNotFound();
});

test('roles other than sales_rep/supervisor/platform_admin cannot access My Products', function (): void {
    $hqLead = User::factory()->withRole('hq_lead')->create();

    $this->actingAs($hqLead)
        ->get(ProductResource::getUrl('index'))
        ->assertForbidden();
});
