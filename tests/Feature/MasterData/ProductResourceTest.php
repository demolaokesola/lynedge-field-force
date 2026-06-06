<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
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
