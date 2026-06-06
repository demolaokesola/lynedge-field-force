<?php

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\DemandCreator;
use App\Models\DemandCreatorType;
use App\Models\Product;
use App\Models\Territory;
use Database\Seeders\DemandCreatorTypeSeeder;
use Database\Seeders\MasterDataSeeder;

test('a customer belongs to a territory and casts its type', function (): void {
    $territory = Territory::factory()->create();
    $customer = Customer::factory()->for($territory)->create(['type' => CustomerType::Hospital]);

    expect($customer->type)->toBe(CustomerType::Hospital)
        ->and($customer->territory->is($territory))->toBeTrue();
});

test('a demand creator belongs to a type and a territory', function (): void {
    $type = DemandCreatorType::factory()->create();
    $territory = Territory::factory()->create();

    $creator = DemandCreator::factory()
        ->for($type, 'type')
        ->for($territory)
        ->create();

    expect($creator->type->is($type))->toBeTrue()
        ->and($creator->territory->is($territory))->toBeTrue();
});

test('a demand creator type has many demand creators', function (): void {
    $type = DemandCreatorType::factory()->create();
    DemandCreator::factory()->count(3)->for($type, 'type')->create();

    expect($type->demandCreators()->count())->toBe(3);
});

test('the demand creator type seeder is idempotent and seeds the six lookup values', function (): void {
    (new DemandCreatorTypeSeeder)->run();
    (new DemandCreatorTypeSeeder)->run();

    expect(DemandCreatorType::count())->toBe(count(DemandCreatorTypeSeeder::TYPES));
});

test('the master data seeder builds a valid disjoint strict partition', function (): void {
    (new DemandCreatorTypeSeeder)->run();
    (new MasterDataSeeder)->run();
    (new MasterDataSeeder)->run(); // idempotent re-run

    $productsInTwoStrictTeams = DB::table('product_team as pt')
        ->join('teams as t', 't.id', '=', 'pt.team_id')
        ->where('t.kind', 'strict')
        ->groupBy('pt.product_id')
        ->havingRaw('count(*) > 1')
        ->pluck('pt.product_id');

    expect(Product::count())->toBe(6)
        ->and($productsInTwoStrictTeams)->toBeEmpty();
});
