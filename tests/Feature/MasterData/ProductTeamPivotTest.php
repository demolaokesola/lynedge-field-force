<?php

use App\Exceptions\StrictTeamConflictException;
use App\Models\Product;
use App\Models\Team;
use App\Support\Money;

test('a product can join multiple teams (one strict plus several liberal)', function (): void {
    $product = Product::factory()->create();
    $strict = Team::factory()->strict()->create();
    $liberalOne = Team::factory()->liberal()->create();
    $liberalTwo = Team::factory()->liberal()->create();

    $product->teams()->attach($strict->id);
    $product->teams()->attach([$liberalOne->id, $liberalTwo->id]);

    expect($product->teams()->count())->toBe(3);
});

test('a product is rejected from a second strict team', function (): void {
    $product = Product::factory()->create();
    $first = Team::factory()->strict()->create();
    $second = Team::factory()->strict()->create();

    $product->teams()->attach($first->id);

    expect(fn () => $product->teams()->attach($second->id))
        ->toThrow(StrictTeamConflictException::class);

    expect($product->teams()->count())->toBe(1)
        ->and($product->teams()->whereKey($second->id)->exists())->toBeFalse();
});

test('a product may join multiple liberal teams', function (): void {
    $product = Product::factory()->create();
    $liberals = Team::factory()->liberal()->count(4)->create();

    $product->teams()->attach($liberals->pluck('id')->all());

    expect($product->teams()->count())->toBe(4);
});

test('sync is also guarded against two strict teams at once', function (): void {
    $product = Product::factory()->create();
    $teams = Team::factory()->strict()->count(2)->create();

    expect(fn () => $product->teams()->sync($teams->pluck('id')->all()))
        ->toThrow(StrictTeamConflictException::class);

    expect($product->teams()->count())->toBe(0);
});

test('team exposes the inverse products relationship', function (): void {
    $team = Team::factory()->liberal()->create();
    $products = Product::factory()->count(2)->create();

    foreach ($products as $product) {
        $product->teams()->attach($team->id);
    }

    expect($team->products()->count())->toBe(2)
        ->and($team->products->pluck('id')->sort()->values()->all())
        ->toEqual($products->pluck('id')->sort()->values()->all());
});

test('unit price casts to a Money value object', function (): void {
    $product = Product::factory()->create(['unit_price' => '1500.50']);

    expect($product->unit_price)->toBeInstanceOf(Money::class)
        ->and($product->unit_price->amount)->toBe('1500.50');
});

test('a null unit price stays null', function (): void {
    $product = Product::factory()->create(['unit_price' => null]);

    expect($product->fresh()->unit_price)->toBeNull();
});
