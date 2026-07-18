<?php

use App\Models\Deposit;
use App\Models\DepositAllocation;
use App\Models\User;
use App\Policies\DepositAllocationPolicy;
use App\Policies\DepositPolicy;

/**
 * Policy-level gating for deposits and allocations.
 * Superuser bypass is handled by Shield's Gate::before and is not repeated here.
 */
test('sales_rep can create deposits', function (): void {
    $user = User::factory()->withRole('sales_rep')->create();
    $policy = new DepositPolicy;

    expect($policy->create($user))->toBeTrue();
});

test('accountant and platform_admin can create deposits', function (string $role): void {
    $user = User::factory()->withRole($role)->create();
    $policy = new DepositPolicy;

    expect($policy->create($user))->toBeTrue();
})->with([
    'accountant' => ['accountant'],
    'platform_admin' => ['platform_admin'],
]);

test('management roles cannot create deposits', function (string $role): void {
    $user = User::factory()->withRole($role)->create();
    $policy = new DepositPolicy;

    expect($policy->create($user))->toBeFalse();
})->with([
    'hq_lead' => ['hq_lead'],
    'regional_head' => ['regional_head'],
]);

test('sales_rep cannot update or delete a deposit', function (): void {
    $rep = User::factory()->withRole('sales_rep')->create();
    $deposit = Deposit::factory()->by($rep)->create();
    $policy = new DepositPolicy;

    expect($policy->update($rep, $deposit))->toBeFalse()
        ->and($policy->delete($rep, $deposit))->toBeFalse();
});

test('accountant can update and delete any deposit', function (): void {
    $accountant = User::factory()->withRole('accountant')->create();
    $deposit = Deposit::factory()->create();
    $policy = new DepositPolicy;

    expect($policy->update($accountant, $deposit))->toBeTrue()
        ->and($policy->delete($accountant, $deposit))->toBeTrue();
});

test('only accountant and platform_admin can create allocations', function (string $role, bool $expected): void {
    $user = User::factory()->withRole($role)->create();
    $policy = new DepositAllocationPolicy;

    expect($policy->create($user))->toBe($expected);
})->with([
    'accountant can' => ['accountant', true],
    'platform_admin can' => ['platform_admin', true],
    'sales_rep cannot' => ['sales_rep', false],
    'hq_lead cannot' => ['hq_lead', false],
    'regional_head cannot' => ['regional_head', false],
]);

test('only accountant and platform_admin can delete allocations', function (string $role, bool $expected): void {
    $user = User::factory()->withRole($role)->create();
    $allocation = DepositAllocation::factory()->create();
    $policy = new DepositAllocationPolicy;

    expect($policy->delete($user, $allocation))->toBe($expected);
})->with([
    'accountant can' => ['accountant', true],
    'platform_admin can' => ['platform_admin', true],
    'sales_rep cannot' => ['sales_rep', false],
    'hq_lead cannot' => ['hq_lead', false],
]);
