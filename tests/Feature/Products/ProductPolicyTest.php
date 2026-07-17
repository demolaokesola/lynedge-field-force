<?php

use App\Models\Product;
use App\Models\User;

test('sales_rep, supervisor and platform_admin may view products but other roles may not', function (): void {
    $product = Product::factory()->create();

    foreach (['sales_rep', 'supervisor', 'platform_admin'] as $role) {
        $user = User::factory()->withRole($role)->create();

        expect($user->can('viewAny', Product::class))->toBeTrue()
            ->and($user->can('view', $product))->toBeTrue();
    }

    foreach (['hq_lead', 'regional_head', 'accountant'] as $role) {
        $user = User::factory()->withRole($role)->create();

        expect($user->can('viewAny', Product::class))->toBeFalse()
            ->and($user->can('view', $product))->toBeFalse();
    }
});

test('sales_rep and supervisor cannot create, update or delete products', function (): void {
    $product = Product::factory()->create();

    foreach (['sales_rep', 'supervisor'] as $role) {
        $user = User::factory()->withRole($role)->create();

        expect($user->can('create', Product::class))->toBeFalse()
            ->and($user->can('update', $product))->toBeFalse()
            ->and($user->can('delete', $product))->toBeFalse();
    }
});
