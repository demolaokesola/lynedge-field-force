<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->withRole('platform_admin')->create());
});

test('the products form rejects a second strict team with a clean error', function (): void {
    $strictOne = Team::factory()->strict()->create();
    $strictTwo = Team::factory()->strict()->create();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Test Product',
            'sku' => 'TST-001',
            'pack_size' => '10 x 10 tablets',
            'teams' => [$strictOne->id, $strictTwo->id],
        ])
        ->call('create')
        ->assertHasFormErrors(['teams']);

    expect(Product::count())->toBe(0);
});

test('the products form accepts one strict team alongside liberal teams', function (): void {
    $strict = Team::factory()->strict()->create();
    $liberal = Team::factory()->liberal()->create();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Valid Product',
            'sku' => 'VAL-001',
            'pack_size' => '1 x 100ml',
            'unit_price' => 2500,
            'teams' => [$strict->id, $liberal->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::firstWhere('sku', 'VAL-001');

    expect($product)->not->toBeNull()
        ->and($product->teams()->count())->toBe(2);
});

test('the products edit page hydrates a money-priced product and saves', function (): void {
    $product = Product::factory()->create(['unit_price' => '1500.50']);

    livewire(EditProduct::class, ['record' => $product->id])
        ->assertSuccessful()
        ->fillForm(['name' => 'Renamed Product'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->name)->toBe('Renamed Product')
        ->and($product->fresh()->unit_price->amount)->toBe('1500.50');
});

test('the products list page renders a money-priced product', function (): void {
    $product = Product::factory()->create(['unit_price' => '1500.50']);

    livewire(ListProducts::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$product]);
});
